<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Faculty;
use App\Models\StudyProgram;
use App\Models\AcademicClass;
use App\Models\Category;
use App\Models\SystemConfig;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Master Roles
        $roles = [
            ['name' => 'CUSTOMER', 'label' => 'Mahasiswa Customer'],
            ['name' => 'ADMIN', 'label' => 'Admin Penyedia Layanan Terverifikasi'],
            ['name' => 'SYSADMIN', 'label' => 'Administrator Utama Sistem'],
        ];
        foreach ($roles as $r) {
            Role::firstOrCreate(['name' => $r['name']], ['label' => $r['label']]);
        }

        // 2. Seed Konfigurasi Sistem Default
        if (SystemConfig::count() === 0) {
            SystemConfig::create([
                'platform_name' => 'JastipKampus UPNYK',
                'allow_cross_faculty_orders' => true,
                'require_ktm_for_admin' => true,
                'max_ktm_size_mb' => 2,
            ]);
        }

        // 3. Seed Kategori Layanan Dinamis Default
        $defaultCategories = [
            [
                'name' => 'Cetak & Jilid Tugas / Skripsi',
                'description' => 'Menitipkan file (PDF) untuk dicetakkan di fotokopi sekitar kampus dan diantarkan langsung ke customer',
            ],
            [
                'name' => 'Pengurusan Dokumen Akademik & KTM',
                'description' => 'Jasa pengambilan KTM, legalisir ijazah/transkrip di BAAK atau loket fakultas',
            ],
            [
                'name' => 'Pembelian Perlengkapan Kuliah & Modul',
                'description' => 'Jasa titip beli buku modul praktikum, jas lab, dan perlengkapan kuliah di koperasi kampus',
            ],
        ];
        foreach ($defaultCategories as $cat) {
            Category::firstOrCreate(
                ['name' => $cat['name']],
                ['description' => $cat['description'], 'is_active' => true]
            );
        }

        // 4. Seed Fakultas & Program Studi Resmi UPN "Veteran" Yogyakarta (upnyk.ac.id)
        $upnykData = [
            [
                'code' => 'FTI',
                'name' => 'Fakultas Teknik Industri',
                'programs' => [
                    ['name' => 'Informatika', 'degree' => 'S1', 'classes' => ['Informatika A', 'Informatika B', 'Informatika C']],
                    ['name' => 'Sistem Informasi', 'degree' => 'S1', 'classes' => ['Sistem Informasi A', 'Sistem Informasi B']],
                    ['name' => 'Teknik Industri', 'degree' => 'S1', 'classes' => ['Teknik Industri A', 'Teknik Industri B']],
                    ['name' => 'Teknik Kimia', 'degree' => 'S1', 'classes' => ['Teknik Kimia A', 'Teknik Kimia B']],
                ],
            ],
            [
                'code' => 'FTME',
                'name' => 'Fakultas Teknologi Mineral dan Energi',
                'programs' => [
                    ['name' => 'Teknik Pertambangan', 'degree' => 'S1', 'classes' => ['Teknik Pertambangan A', 'Teknik Pertambangan B']],
                    ['name' => 'Teknik Perminyakan', 'degree' => 'S1', 'classes' => ['Teknik Perminyakan A', 'Teknik Perminyakan B']],
                    ['name' => 'Teknik Geologi', 'degree' => 'S1', 'classes' => ['Teknik Geologi A', 'Teknik Geologi B']],
                    ['name' => 'Teknik Geofisika', 'degree' => 'S1', 'classes' => ['Teknik Geofisika A']],
                    ['name' => 'Teknik Geomatika', 'degree' => 'S1', 'classes' => ['Teknik Geomatika A']],
                    ['name' => 'Teknik Lingkungan', 'degree' => 'S1', 'classes' => ['Teknik Lingkungan A']],
                    ['name' => 'Teknik Metalurgi', 'degree' => 'S1', 'classes' => ['Teknik Metalurgi A']],
                ],
            ],
            [
                'code' => 'FEB',
                'name' => 'Fakultas Ekonomi dan Bisnis',
                'programs' => [
                    ['name' => 'Manajemen', 'degree' => 'S1', 'classes' => ['Manajemen A', 'Manajemen B']],
                    ['name' => 'Akuntansi', 'degree' => 'S1', 'classes' => ['Akuntansi A', 'Akuntansi B']],
                    ['name' => 'Ekonomi Pembangunan', 'degree' => 'S1', 'classes' => ['Ekonomi Pembangunan A']],
                ],
            ],
            [
                'code' => 'FISIP',
                'name' => 'Fakultas Ilmu Sosial dan Ilmu Politik',
                'programs' => [
                    ['name' => 'Ilmu Komunikasi', 'degree' => 'S1', 'classes' => ['Ilmu Komunikasi A', 'Ilmu Komunikasi B']],
                    ['name' => 'Ilmu Administrasi Bisnis', 'degree' => 'S1', 'classes' => ['Administrasi Bisnis A']],
                    ['name' => 'Hubungan Internasional', 'degree' => 'S1', 'classes' => ['Hubungan Internasional A']],
                    ['name' => 'Hubungan Masyarakat', 'degree' => 'S1', 'classes' => ['Hubungan Masyarakat A']],
                ],
            ],
            [
                'code' => 'FP',
                'name' => 'Fakultas Pertanian',
                'programs' => [
                    ['name' => 'Agroteknologi', 'degree' => 'S1', 'classes' => ['Agroteknologi A', 'Agroteknologi B']],
                    ['name' => 'Agribisnis', 'degree' => 'S1', 'classes' => ['Agribisnis A']],
                    ['name' => 'Ilmu Tanah', 'degree' => 'S1', 'classes' => ['Ilmu Tanah A']],
                ],
            ],
        ];

        foreach ($upnykData as $facItem) {
            $faculty = Faculty::firstOrCreate(
                ['code' => $facItem['code']],
                ['name' => $facItem['name'], 'is_active' => true]
            );

            foreach ($facItem['programs'] as $prdItem) {
                $program = StudyProgram::firstOrCreate(
                    ['faculty_id' => $faculty->id, 'name' => $prdItem['name']],
                    ['degree' => $prdItem['degree'], 'is_active' => true]
                );

                foreach ($prdItem['classes'] as $clsName) {
                    AcademicClass::firstOrCreate(
                        ['study_program_id' => $program->id, 'name' => $clsName],
                        ['cohort_year' => '2024', 'is_active' => true]
                    );
                }
            }
        }
    }
}