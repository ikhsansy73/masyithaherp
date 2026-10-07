<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class SchoolSettings extends Settings
{
    public string $school_name;

    public string $address;

    public string $phone;

    public string $email;

    public string $npsn;

    /** Kepala sekolah — appears on rapor, surat, and laporan signatures. */
    public string $headmaster_name;

    public string $headmaster_nip;

    /** Bendahara — appears on kwitansi signatures. */
    public string $treasurer_name;

    /** Day of month SPP invoices fall due. */
    public int $spp_due_day;

    public static function group(): string
    {
        return 'school';
    }
}
