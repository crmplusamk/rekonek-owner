<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use MongoDB\Collection as MongoCollection;
use RuntimeException;
use Throwable;

/**
 * CompanyMongoDataPurgeService
 *
 * Backup (ke file .jsonl) lalu menghapus koleksi MongoDB milik sebuah company.
 *
 * Format backup: JSON Lines (.jsonl) dengan MongoDB Extended JSON v2 (canonical)
 * sehingga tipe BSON (ObjectId, Date, Decimal128, dsb) tetap terjaga dan file
 * bisa langsung di-restore dengan `mongoimport`:
 *
 *   mongoimport --uri="$MONGODB_URI" \
 *               --db=$MONGODB_DATABASE \
 *               --collection=conversation_messages_<company_id> \
 *               --file=company-<id>-mongo-<ts>.jsonl
 *
 * Pola koleksi per-company yang diketahui saat ini:
 *   - conversation_messages_{company_id}
 *
 * Tambahkan entry baru pada {@see self::PER_COMPANY_COLLECTION_PREFIXES} jika
 * ada pola koleksi per-company lain di masa depan.
 */
class CompanyMongoDataPurgeService
{
    private const CONNECTION = 'mongodb';

    /**
     * Prefix koleksi yang diikuti oleh `{company_id}`.
     * Nama koleksi final = prefix + company_id.
     *
     * @var array<int,string>
     */
    private const PER_COMPANY_COLLECTION_PREFIXES = [
        'conversation_messages_',
    ];

    /**
     * Backup + drop semua koleksi MongoDB yang terkait company.
     *
     * @return array{
     *   company_id:string,
     *   backup_path:?string,
     *   collections:array<int,array{name:string,backed_up:int,dropped:bool}>,
     *   total_backed_up:int
     * }
     */
    public function purge(string $companyId, ?string $backupDirectory = null): array
    {
        $this->assertValidCompanyId($companyId);
        $this->assertMongoDriverLoaded();

        $existingCollections = $this->resolveCompanyCollections($companyId);

        if (empty($existingCollections)) {
            return [
                'company_id'      => $companyId,
                'backup_path'     => null,
                'collections'     => [],
                'total_backed_up' => 0,
            ];
        }

        $backupDirectory = $backupDirectory ?: storage_path('app/company-backups');
        $this->ensureDirectoryExists($backupDirectory);

        $finalPath = $this->buildBackupPath($backupDirectory, $companyId);
        $tempPath  = $finalPath . '.tmp';

        $backedUpCounts = [];
        try {
            $backedUpCounts = $this->writeBackupToFile($tempPath, $companyId, $existingCollections);
        } catch (Throwable $exception) {
            if (File::exists($tempPath)) {
                File::delete($tempPath);
            }
            throw $exception;
        }

        File::move($tempPath, $finalPath);

        $report = [];
        foreach ($existingCollections as $collectionName) {
            $report[] = [
                'name'      => $collectionName,
                'backed_up' => $backedUpCounts[$collectionName] ?? 0,
                'dropped'   => $this->dropCollection($collectionName),
            ];
        }

        return [
            'company_id'      => $companyId,
            'backup_path'     => $finalPath,
            'collections'     => $report,
            'total_backed_up' => array_sum($backedUpCounts),
        ];
    }

    /* ----------------------------- VALIDATION ----------------------------- */

    private function assertValidCompanyId(string $companyId): void
    {
        $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';
        if (!preg_match($pattern, $companyId)) {
            throw new InvalidArgumentException("company_id must be a valid UUID, got: {$companyId}");
        }
    }

    private function assertMongoDriverLoaded(): void
    {
        if (!extension_loaded('mongodb')) {
            throw new RuntimeException(
                'PHP extension "mongodb" is not loaded. Install it before running MongoDB purge.'
            );
        }

        if (!class_exists(\MongoDB\BSON\ObjectId::class)) {
            throw new RuntimeException(
                'MongoDB BSON classes are unavailable. The ext-mongodb extension appears '
                . 'to be installed but incomplete. Reinstall/upgrade ext-mongodb.'
            );
        }
    }

    /* -------------------------- COLLECTION DISCOVERY ----------------------- */

    /**
     * Return daftar koleksi company yang BENAR-BENAR ada di MongoDB.
     *
     * @return array<int,string>
     */
    private function resolveCompanyCollections(string $companyId): array
    {
        $candidates = array_map(
            fn (string $prefix) => $prefix . $companyId,
            self::PER_COMPANY_COLLECTION_PREFIXES
        );

        $existing = [];
        foreach ($candidates as $name) {
            if ($this->collectionExists($name)) {
                $existing[] = $name;
            }
        }

        return $existing;
    }

    private function collectionExists(string $name): bool
    {
        $db = $this->mongoDatabase();

        /** @var iterable<string> $names */
        $names = $db->listCollectionNames(['filter' => ['name' => $name]]);

        foreach ($names as $_) {
            return true;
        }

        return false;
    }

    /* -------------------------------- BACKUP ------------------------------- */

    /**
     * Streaming backup semua koleksi ke satu file .jsonl.
     * Format per-baris:
     *   __meta__: { "__collection__": "<name>" }   (marker sebelum dokumen2 tiap koleksi)
     *   dokumen: <MongoDB Extended JSON v2 canonical>
     *
     * @param array<int,string> $collectionNames
     * @return array<string,int> nama koleksi => jumlah dokumen yang dibackup
     */
    private function writeBackupToFile(string $path, string $companyId, array $collectionNames): array
    {
        $handle = @fopen($path, 'wb');
        if ($handle === false) {
            throw new RuntimeException("Unable to open backup file for writing: {$path}");
        }

        $counts = [];

        try {
            $header = json_encode([
                '__header__'   => true,
                'company_id'   => $companyId,
                'generated_at' => now()->toIso8601String(),
                'collections'  => $collectionNames,
                'format'       => 'jsonl+ejsonv2',
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            fwrite($handle, $header . PHP_EOL);

            foreach ($collectionNames as $collectionName) {
                $marker = json_encode(
                    ['__collection__' => $collectionName],
                    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
                );
                fwrite($handle, $marker . PHP_EOL);

                $counts[$collectionName] = $this->streamCollectionDocuments($handle, $collectionName);
            }
        } finally {
            fclose($handle);
        }

        return $counts;
    }

    /**
     * @param resource $handle
     *
     * Dokumen di-fetch sebagai array PHP (typeMap=array). Nilai-nilai leaf
     * bertipe BSON (ObjectId, UTCDateTime, Decimal128, Binary, Regex, dsb)
     * tetap berupa objek dari namespace MongoDB\BSON, dan semua class tersebut
     * sudah meng-implement JsonSerializable dengan format MongoDB Extended JSON
     * (relaxed). Jadi `json_encode()` langsung menghasilkan baris JSONL yang
     * siap di-restore oleh `mongoimport`.
     */
    private function streamCollectionDocuments($handle, string $collectionName): int
    {
        $collection = $this->mongoCollection($collectionName);

        $cursor = $collection->find(
            [],
            [
                'typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array'],
            ]
        );

        $count = 0;
        foreach ($cursor as $document) {
            $ejson = json_encode(
                $document,
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
            );
            fwrite($handle, $ejson . PHP_EOL);
            $count++;
        }

        return $count;
    }

    /* -------------------------------- DELETE ------------------------------- */

    private function dropCollection(string $collectionName): bool
    {
        try {
            $this->mongoCollection($collectionName)->drop();
            return true;
        } catch (Throwable $exception) {
            return false;
        }
    }

    /* ------------------------------- HELPERS ------------------------------- */

    private function mongoCollection(string $name): MongoCollection
    {
        return $this->mongoDatabase()->selectCollection($name);
    }

    private function mongoDatabase(): \MongoDB\Database
    {
        /** @var \MongoDB\Laravel\Connection $connection */
        $connection = DB::connection(self::CONNECTION);

        return $connection->getMongoDB();
    }

    private function ensureDirectoryExists(string $directory): void
    {
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }
    }

    private function buildBackupPath(string $directory, string $companyId): string
    {
        $filename = sprintf('company-%s-mongo-%s.jsonl', $companyId, now()->format('Ymd_His'));

        return rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
    }
}
