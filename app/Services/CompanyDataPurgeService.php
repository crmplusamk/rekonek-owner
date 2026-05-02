<?php

namespace App\Services;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * CompanyDataPurgeService
 *
 * Exports (backup ke .sql) lalu menghapus semua data milik sebuah company
 * berdasarkan `company_id`. Seluruh query berjalan pada koneksi `client`
 * (DB Retalk/CRM), bukan koneksi default backoffice.
 *
 * Tiga fase:
 *   1. PLAN   : susun daftar tabel + klausa WHERE (urutan child -> parent)
 *   2. BACKUP : stream INSERT statement ke file .sql (di luar transaction)
 *   3. DELETE : jalankan DELETE sesuai plan di dalam transaction
 *
 * Jika DELETE gagal, transaction di-rollback dan file backup sementara
 * dihapus supaya tidak meninggalkan file yang menyesatkan.
 */
class CompanyDataPurgeService
{
    private const CONNECTION = 'client';

    private const SCHEMA = 'public';

    private const USER_MODEL = 'App\\Models\\User\\User';

    /**
     * Tabel polymorphic yang punya pointer ke user.
     * Dihapus duluan karena user milik company ini akan ikut dihapus.
     */
    private const USER_POLYMORPHIC_TABLES = [
        ['table' => 'notifications',          'type_column' => 'notifiable_type', 'id_column' => 'notifiable_id'],
        ['table' => 'model_has_roles',        'type_column' => 'model_type',      'id_column' => 'model_id'],
        ['table' => 'model_has_permissions',  'type_column' => 'model_type',      'id_column' => 'model_id'],
        ['table' => 'personal_access_tokens', 'type_column' => 'tokenable_type',  'id_column' => 'tokenable_id'],
    ];

    /**
     * Backup dan hapus seluruh data milik company tertentu.
     *
     * @return array{
     *   company_id:string,
     *   backup_path:?string,
     *   plan:array<int,array{table:string,where:string,bindings:array}>,
     *   deleted_rows:array<string,int>,
     *   total_deleted:int
     * }
     */
    public function purge(string $companyId, ?string $backupDirectory = null, bool $withBackup = true): array
    {
        $this->assertValidCompanyId($companyId);
        $this->assertCompanyExists($companyId);

        $plan = $this->buildDeletionPlan($companyId);

        if ($withBackup) {
            $backupDirectory = $backupDirectory ?: storage_path('app/company-backups');
            $this->ensureDirectoryExists($backupDirectory);

            $finalPath = $this->buildBackupPath($backupDirectory, $companyId);
            $tempPath  = $finalPath . '.tmp';

            try {
                $this->writeBackupToFile($tempPath, $companyId, $plan);

                $deletedRows = $this->db()->transaction(function () use ($plan) {
                    return $this->executeDeletions($plan);
                });

                File::move($tempPath, $finalPath);

                return [
                    'company_id'    => $companyId,
                    'backup_path'   => $finalPath,
                    'plan'          => $plan,
                    'deleted_rows'  => $deletedRows,
                    'total_deleted' => array_sum($deletedRows),
                ];
            } catch (Throwable $exception) {
                if (File::exists($tempPath)) {
                    File::delete($tempPath);
                }
                throw $exception;
            }
        }

        $deletedRows = $this->db()->transaction(function () use ($plan) {
            return $this->executeDeletions($plan);
        });

        return [
            'company_id'    => $companyId,
            'backup_path'   => null,
            'plan'          => $plan,
            'deleted_rows'  => $deletedRows,
            'total_deleted' => array_sum($deletedRows),
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

    private function assertCompanyExists(string $companyId): void
    {
        $sql = 'SELECT 1 FROM ' . $this->qualifyTable('companies') . ' WHERE "id" = ? LIMIT 1';
        if (empty($this->db()->select($sql, [$companyId]))) {
            throw new RuntimeException("Company with id {$companyId} was not found.");
        }
    }

    /* ------------------------------ PLANNING ------------------------------ */

    /**
     * Susun rencana hapus. Urutan entry penting:
     *   - polymorphic pointer ke user
     *   - tabel child (tanpa company_id, FK ke tabel ber-company_id)
     *   - tabel ber-company_id (topo order: child -> parent)
     *   - companies (terakhir)
     *
     * @return array<int,array{table:string,where:string,bindings:array}>
     */
    private function buildDeletionPlan(string $companyId): array
    {
        $plan = [];

        $companyTables = $this->getTablesWithCompanyId();
        $childMap      = $this->getSingleHopChildTables($companyTables);
        $fkGraph       = $this->getForeignKeyGraph();

        $userIdType = $this->getColumnType('users', 'id');

        foreach (self::USER_POLYMORPHIC_TABLES as $polymorphic) {
            if (!$this->tableExists($polymorphic['table'])) {
                continue;
            }

            $idColumnType = $this->getColumnType($polymorphic['table'], $polymorphic['id_column']);
            if ($idColumnType === null || $idColumnType !== $userIdType) {
                continue;
            }

            $plan[] = [
                'table'    => $polymorphic['table'],
                'where'    => sprintf(
                    '%s = ? AND %s IN (SELECT "id" FROM %s WHERE "company_id" = ?)',
                    $this->quoteIdentifier($polymorphic['type_column']),
                    $this->quoteIdentifier($polymorphic['id_column']),
                    $this->qualifyTable('users')
                ),
                'bindings' => [self::USER_MODEL, $companyId],
            ];
        }

        foreach ($childMap as $childTable => $parents) {
            $plan[] = [
                'table'    => $childTable,
                'where'    => $this->buildChildWhereClause($parents),
                'bindings' => array_fill(0, count($parents), $companyId),
            ];
        }

        foreach ($this->topoSortCompanyTables($companyTables, $fkGraph) as $table) {
            $plan[] = [
                'table'    => $table,
                'where'    => '"company_id" = ?',
                'bindings' => [$companyId],
            ];
        }

        $plan[] = [
            'table'    => 'companies',
            'where'    => '"id" = ?',
            'bindings' => [$companyId],
        ];

        return $plan;
    }

    /**
     * @param array<int,array{fk_column:string,parent_table:string,parent_column:string}> $parents
     */
    private function buildChildWhereClause(array $parents): string
    {
        $clauses = [];
        foreach ($parents as $parent) {
            $clauses[] = sprintf(
                '%s IN (SELECT %s FROM %s WHERE "company_id" = ?)',
                $this->quoteIdentifier($parent['fk_column']),
                $this->quoteIdentifier($parent['parent_column']),
                $this->qualifyTable($parent['parent_table'])
            );
        }

        return implode(' OR ', $clauses);
    }

    /* -------------------------- SCHEMA DISCOVERY -------------------------- */

    /**
     * @return array<int,string>
     */
    private function getTablesWithCompanyId(): array
    {
        $rows = $this->db()->select(
            "SELECT c.table_name
             FROM information_schema.columns c
             JOIN information_schema.tables t
               ON t.table_schema = c.table_schema
              AND t.table_name   = c.table_name
             WHERE c.table_schema = ?
               AND t.table_type   = 'BASE TABLE'
               AND c.column_name  = 'company_id'
             ORDER BY c.table_name",
            [self::SCHEMA]
        );

        return array_map(fn ($row) => $row->table_name, $rows);
    }

    /**
     * Cari tabel yang TIDAK punya company_id tapi punya FK ke tabel ber-company_id.
     * Return: [childTable => [ ['fk_column' => ..., 'parent_table' => ..., 'parent_column' => ...], ... ]]
     *
     * @param array<int,string> $companyTables
     * @return array<string,array<int,array{fk_column:string,parent_table:string,parent_column:string}>>
     */
    private function getSingleHopChildTables(array $companyTables): array
    {
        $companyTableSet = array_fill_keys($companyTables, true);

        $rows = $this->db()->select(
            "SELECT
                 child.relname  AS child_table,
                 parent.relname AS parent_table,
                 child_attr.attname  AS fk_column,
                 parent_attr.attname AS parent_column
             FROM pg_constraint con
             JOIN pg_class child        ON child.oid  = con.conrelid
             JOIN pg_namespace child_ns ON child_ns.oid = child.relnamespace
             JOIN pg_class parent       ON parent.oid = con.confrelid
             JOIN LATERAL generate_subscripts(con.conkey, 1) AS idx(n) ON TRUE
             JOIN pg_attribute child_attr
                  ON child_attr.attrelid = child.oid
                 AND child_attr.attnum   = con.conkey[idx.n]
             JOIN pg_attribute parent_attr
                  ON parent_attr.attrelid = parent.oid
                 AND parent_attr.attnum   = con.confkey[idx.n]
             WHERE con.contype    = 'f'
               AND child_ns.nspname = ?",
            [self::SCHEMA]
        );

        $result = [];
        foreach ($rows as $row) {
            if (!isset($companyTableSet[$row->parent_table])) {
                continue;
            }
            if (isset($companyTableSet[$row->child_table])) {
                continue;
            }
            if ($row->child_table === $row->parent_table) {
                continue;
            }

            $result[$row->child_table][] = [
                'fk_column'     => $row->fk_column,
                'parent_table'  => $row->parent_table,
                'parent_column' => $row->parent_column,
            ];
        }

        ksort($result);
        return $result;
    }

    /**
     * FK graph antar tabel ber-company_id (untuk urutan DELETE).
     * Return: [childTable => [parentTable => true]]
     *
     * @return array<string,array<string,bool>>
     */
    private function getForeignKeyGraph(): array
    {
        $rows = $this->db()->select(
            "SELECT DISTINCT
                 child.relname  AS child_table,
                 parent.relname AS parent_table
             FROM pg_constraint con
             JOIN pg_class child        ON child.oid  = con.conrelid
             JOIN pg_namespace child_ns ON child_ns.oid = child.relnamespace
             JOIN pg_class parent       ON parent.oid = con.confrelid
             WHERE con.contype    = 'f'
               AND child_ns.nspname = ?",
            [self::SCHEMA]
        );

        $graph = [];
        foreach ($rows as $row) {
            if ($row->child_table === $row->parent_table) {
                continue;
            }
            $graph[$row->child_table][$row->parent_table] = true;
        }

        return $graph;
    }

    /**
     * Topological sort: child dulu, parent kemudian.
     * `companies` di-exclude (ditangani terpisah).
     *
     * @param array<int,string> $companyTables
     * @param array<string,array<string,bool>> $fkGraph
     * @return array<int,string>
     */
    private function topoSortCompanyTables(array $companyTables, array $fkGraph): array
    {
        $nodes = array_values(array_filter($companyTables, fn ($t) => $t !== 'companies'));
        $nodeSet = array_fill_keys($nodes, true);

        $indegree  = array_fill_keys($nodes, 0);
        $adjacency = array_fill_keys($nodes, []);

        foreach ($fkGraph as $child => $parents) {
            if (!isset($nodeSet[$child])) {
                continue;
            }
            foreach (array_keys($parents) as $parent) {
                if (!isset($nodeSet[$parent])) {
                    continue;
                }
                $adjacency[$child][$parent] = true;
                $indegree[$parent]++;
            }
        }

        $queue = [];
        foreach ($indegree as $table => $degree) {
            if ($degree === 0) {
                $queue[] = $table;
            }
        }
        sort($queue);

        $ordered = [];
        while (!empty($queue)) {
            $table = array_shift($queue);
            $ordered[] = $table;

            $parents = array_keys($adjacency[$table]);
            sort($parents);
            foreach ($parents as $parent) {
                if (--$indegree[$parent] === 0) {
                    $queue[] = $parent;
                    sort($queue);
                }
            }
        }

        if (count($ordered) !== count($nodes)) {
            $remaining = array_values(array_diff($nodes, $ordered));
            sort($remaining);
            $ordered = array_merge($ordered, $remaining);
        }

        return $ordered;
    }

    private function tableExists(string $table): bool
    {
        $rows = $this->db()->select(
            "SELECT 1
             FROM information_schema.tables
             WHERE table_schema = ? AND table_name = ? AND table_type = 'BASE TABLE'
             LIMIT 1",
            [self::SCHEMA, $table]
        );

        return !empty($rows);
    }

    private function getColumnType(string $table, string $column): ?string
    {
        $rows = $this->db()->select(
            "SELECT data_type
             FROM information_schema.columns
             WHERE table_schema = ? AND table_name = ? AND column_name = ?
             LIMIT 1",
            [self::SCHEMA, $table, $column]
        );

        return empty($rows) ? null : $rows[0]->data_type;
    }

    /**
     * @return array<int,string>
     */
    private function getTableColumns(string $table): array
    {
        $rows = $this->db()->select(
            "SELECT column_name
             FROM information_schema.columns
             WHERE table_schema = ? AND table_name = ?
             ORDER BY ordinal_position",
            [self::SCHEMA, $table]
        );

        return array_map(fn ($row) => $row->column_name, $rows);
    }

    /* -------------------------------- BACKUP ------------------------------- */

    /**
     * Tulis backup .sql streaming (dibuka sekali, ditutup sekali).
     * Urutan file: parent dulu, child belakangan -> reverse dari urutan DELETE.
     *
     * @param array<int,array{table:string,where:string,bindings:array}> $plan
     */
    private function writeBackupToFile(string $path, string $companyId, array $plan): void
    {
        $handle = @fopen($path, 'wb');
        if ($handle === false) {
            throw new RuntimeException("Unable to open backup file for writing: {$path}");
        }

        try {
            fwrite($handle, "-- Retalk company backup" . PHP_EOL);
            fwrite($handle, "-- Company ID: {$companyId}" . PHP_EOL);
            fwrite($handle, "-- Generated at: " . now()->toDateTimeString() . PHP_EOL);
            fwrite($handle, "BEGIN;" . PHP_EOL);
            fwrite($handle, "SET CONSTRAINTS ALL DEFERRED;" . PHP_EOL . PHP_EOL);

            foreach (array_reverse($plan) as $entry) {
                $this->streamTableBackup($handle, $entry);
            }

            fwrite($handle, "COMMIT;" . PHP_EOL);
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param resource $handle
     * @param array{table:string,where:string,bindings:array} $entry
     */
    private function streamTableBackup($handle, array $entry): void
    {
        $table   = $entry['table'];
        $columns = $this->getTableColumns($table);

        if (empty($columns)) {
            return;
        }

        fwrite($handle, "-- Table: {$table}" . PHP_EOL);

        $selectSql = sprintf(
            'SELECT * FROM %s WHERE %s',
            $this->qualifyTable($table),
            $entry['where']
        );

        $columnList = implode(', ', array_map(fn ($c) => $this->quoteIdentifier($c), $columns));

        foreach ($this->db()->cursor($selectSql, $entry['bindings']) as $row) {
            $values = [];
            foreach ($columns as $column) {
                $value    = property_exists($row, $column) ? $row->{$column} : null;
                $values[] = $this->toSqlLiteral($value);
            }

            $insert = sprintf(
                'INSERT INTO %s (%s) VALUES (%s) ON CONFLICT DO NOTHING;' . PHP_EOL,
                $this->qualifyTable($table),
                $columnList,
                implode(', ', $values)
            );

            fwrite($handle, $insert);
        }

        fwrite($handle, PHP_EOL);
    }

    /* ------------------------------- DELETE -------------------------------- */

    /**
     * @param array<int,array{table:string,where:string,bindings:array}> $plan
     * @return array<string,int>
     */
    private function executeDeletions(array $plan): array
    {
        $deletedRows = [];

        foreach ($plan as $entry) {
            $sql = sprintf(
                'DELETE FROM %s WHERE %s',
                $this->qualifyTable($entry['table']),
                $entry['where']
            );

            $deletedRows[$entry['table']] = $this->db()->affectingStatement($sql, $entry['bindings']);
        }

        return $deletedRows;
    }

    /* ------------------------------- HELPERS ------------------------------- */

    private function db(): Connection
    {
        return DB::connection(self::CONNECTION);
    }

    private function toSqlLiteral($value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? 'TRUE' : 'FALSE';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return $this->db()->getPdo()->quote((string) $value);
    }

    private function ensureDirectoryExists(string $directory): void
    {
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }
    }

    private function buildBackupPath(string $directory, string $companyId): string
    {
        $filename = sprintf('company-%s-%s.sql', $companyId, now()->format('Ymd_His'));

        return rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
    }

    private function qualifyTable(string $table): string
    {
        return $this->quoteIdentifier(self::SCHEMA) . '.' . $this->quoteIdentifier($table);
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '"' . str_replace('"', '""', $identifier) . '"';
    }
}
