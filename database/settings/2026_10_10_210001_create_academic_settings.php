<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('academic.formative_weight', 40);
        $this->migrator->add('academic.summative_weight', 60);
        $this->migrator->add('academic.threshold_sb', 90);
        $this->migrator->add('academic.threshold_bsh', 80);
        $this->migrator->add('academic.threshold_mb', 70);
        $this->migrator->add('academic.tp_mastery_threshold', 75);
    }
};
