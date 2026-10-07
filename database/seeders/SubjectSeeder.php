<?php

namespace Database\Seeders;

use App\Enums\SubjectKelompok;
use App\Models\Subject;
use Illuminate\Database\Seeder;

/**
 * Standard SD Kurikulum Merdeka subject list (doc 02 §6; the actual list
 * is data and stays editable in the Subjects resource).
 */
class SubjectSeeder extends Seeder
{
    public function run(): void
    {
        $subjects = [
            ['code' => 'PKN', 'name' => 'Pendidikan Pancasila', 'kelompok' => SubjectKelompok::A, 'jp_per_week' => 4],
            ['code' => 'BIN', 'name' => 'Bahasa Indonesia', 'kelompok' => SubjectKelompok::A, 'jp_per_week' => 6],
            ['code' => 'BIG', 'name' => 'Bahasa Inggris', 'kelompok' => SubjectKelompok::B, 'jp_per_week' => 2],
            ['code' => 'MTK', 'name' => 'Matematika', 'kelompok' => SubjectKelompok::A, 'jp_per_week' => 5],
            ['code' => 'IPA', 'name' => 'IPAS (Sains dan Sosial)', 'kelompok' => SubjectKelompok::A, 'jp_per_week' => 4],
            ['code' => 'PAI', 'name' => 'Pendidikan Agama Islam', 'kelompok' => SubjectKelompok::B, 'jp_per_week' => 3],
            ['code' => 'PJOK', 'name' => 'PJOK', 'kelompok' => SubjectKelompok::B, 'jp_per_week' => 3],
            ['code' => 'SBDP', 'name' => 'Seni Budaya', 'kelompok' => SubjectKelompok::B, 'jp_per_week' => 2],
        ];

        foreach ($subjects as $subject) {
            Subject::query()->firstOrCreate(
                ['code' => $subject['code']],
                ['name' => $subject['name'], 'kelompok' => $subject['kelompok'], 'jp_per_week' => $subject['jp_per_week'], 'is_active' => true],
            );
        }
    }
}
