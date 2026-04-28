<?php

/*
|--------------------------------------------------------------------------
| Grace Period Touchpoints
|--------------------------------------------------------------------------
|
| Konfigurasi 11 touchpoint drip campaign yang dikirim selama grace period
| (30 hari setelah subscription expired). Tambah/ubah/disable touchpoint
| cukup edit file ini — tidak perlu bikin command baru.
|
| Per-touchpoint options:
|   - day_offset    : hari relatif ke expired_at (positif = setelah)
|   - phase         : 1 (salvage) | 2 (win-back) | 3 (urgency)
|   - channels      : ['wa', 'email'] — channel mana saja dikirim
|   - email_template: nama view blade (null kalau tidak pakai email)
|   - email_subject : subjek email (null jika email_template null)
|   - wa_template   : nama view blade untuk WA plain text (null jika tidak pakai)
|   - handler       : 'enter' (EnterGraceCommand), 'drip' (DripGraceCommand),
|                     atau 'terminate' (TerminateGraceCommand)
|   - enabled       : false untuk disable sementara tanpa hapus config
|   - triggers_deletion    : (opsional, default false) saat touchpoint ini diproses,
|                            dispatch DeleteCompanyDataJob + DeleteCompanyMongoDataJob.
|   - deletion_delay_hours : (opsional, default 0) delay sebelum job delete dieksekusi
|                            worker. Set 24 supaya eksekusi 24 jam setelah touchpoint.
|
*/

return [

    'touchpoints' => [

        // ============================================================
        // FASE 1 — Salvage & Transaksi (Minggu 1)
        // ============================================================

        'H+1' => [
            'day_offset' => 1,
            'phase' => 1,
            'channels' => ['wa', 'email'],
            'email_template' => 'emails.grace.h-plus-1',
            'email_subject' => 'Masa Langganan Anda Telah Berakhir — Data Aman 30 Hari',
            'wa_template' => 'emails.grace.wa.h-plus-1',
            'handler' => 'enter',
            'enabled' => true,
        ],

        'H+3' => [
            'day_offset' => 3,
            'phase' => 1,
            'channels' => ['email'],
            'email_template' => 'emails.grace.h-plus-3',
            'email_subject' => 'Ada Kendala Pembayaran? Tim Kami Siap Bantu',
            'wa_template' => null,
            'handler' => 'drip',
            'enabled' => true,
        ],

        'H+7' => [
            'day_offset' => 7,
            'phase' => 1,
            'channels' => ['wa'],
            'email_template' => null,
            'email_subject' => null,
            'wa_template' => 'emails.grace.wa.h-plus-7',
            'handler' => 'drip',
            'enabled' => true,
        ],

        // ============================================================
        // FASE 2 — Edukasi & Win-Back (Minggu 2–3)
        // ============================================================

        'H+10' => [
            'day_offset' => 10,
            'phase' => 2,
            'channels' => ['email'],
            'email_template' => 'emails.grace.h-plus-10',
            'email_subject' => 'Boleh Minta Feedback dari Anda?',
            'wa_template' => null,
            'handler' => 'drip',
            'enabled' => true,
        ],

        'H+14' => [
            'day_offset' => 14,
            'phase' => 2,
            'channels' => ['wa'],
            'email_template' => null,
            'email_subject' => null,
            'wa_template' => 'emails.grace.wa.h-plus-14',
            'handler' => 'drip',
            'enabled' => true,
        ],

        'H+17' => [
            'day_offset' => 17,
            'phase' => 2,
            'channels' => ['wa'],
            'email_template' => null,
            'email_subject' => null,
            'wa_template' => 'emails.grace.wa.h-plus-17',
            'handler' => 'drip',
            'enabled' => true,
        ],

        'H+21' => [
            'day_offset' => 21,
            'phase' => 2,
            'channels' => ['email'],
            'email_template' => 'emails.grace.h-plus-21',
            'email_subject' => 'Data Anda Masih Tersimpan Aman di Gudang Data Kami',
            'wa_template' => null,
            'handler' => 'drip',
            'enabled' => true,
        ],

        // ============================================================
        // FASE 3 — Urgensi & Final Call (Minggu 4)
        // ============================================================

        'H+24' => [
            'day_offset' => 24,
            'phase' => 3,
            'channels' => ['wa', 'email'],
            'email_template' => 'emails.grace.h-plus-24',
            'email_subject' => '9 Hari Lagi Sebelum Data Anda Dihapus',
            'wa_template' => 'emails.grace.wa.h-plus-24',
            'handler' => 'drip',
            'enabled' => true,
        ],

        'H+27' => [
            'day_offset' => 27,
            'phase' => 3,
            'channels' => ['wa', 'email'],
            'email_template' => 'emails.grace.h-plus-27',
            'email_subject' => '72 Jam Tersisa — Amankan Database Pelanggan Anda',
            'wa_template' => 'emails.grace.wa.h-plus-27',
            'handler' => 'drip',
            'enabled' => true,
        ],

        'H+29' => [
            'day_offset' => 29,
            'phase' => 3,
            'channels' => ['wa', 'email'],
            'triggers_deletion' => true,
            'deletion_delay_hours' => 0,
            'email_template' => 'emails.grace.h-plus-29',
            'email_subject' => 'Hari Terakhir — Data Dijadwalkan Dihapus dalam 24 Jam',
            'wa_template' => 'emails.grace.wa.h-plus-29',
            'handler' => 'drip',
            'enabled' => true,
        ],

        'H+31' => [
            'day_offset' => 31,
            'phase' => 3,
            'channels' => ['wa', 'email'],
            'email_template' => 'emails.grace.h-plus-31',
            'email_subject' => 'Akun Anda Telah Kami Nonaktifkan',
            'wa_template' => 'emails.grace.wa.h-plus-31',
            'handler' => 'terminate',
            'enabled' => true,
        ],

    ],
];
