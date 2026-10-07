<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('school.school_name', 'SD Masyithah');
        $this->migrator->add('school.address', '');
        $this->migrator->add('school.phone', '');
        $this->migrator->add('school.email', '');
        $this->migrator->add('school.npsn', '');
        $this->migrator->add('school.headmaster_name', '');
        $this->migrator->add('school.headmaster_nip', '');
        $this->migrator->add('school.treasurer_name', '');
        $this->migrator->add('school.spp_due_day', 10);
    }
};
