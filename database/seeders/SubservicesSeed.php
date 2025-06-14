<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\subservices;

class SubservicesSeed extends Seeder
{
    public function run()
    {
        $data = [
            ['id' => 1, 'name_en' => 'Check in cleaning', 'name_heb' => "ניקיון צ'ק אין", 'apartment_size' => 'studio up to 25sqm', 'hours' => '1', 'price' => '200', 'service_id' => 112, 'created_at' => '2025-01-08 18:10:39', 'updated_at' => '2025-01-08 18:10:39'],
            ['id' => 2, 'name_en' => 'Check in cleaning', 'name_heb' => "ניקיון צ'ק אין", 'apartment_size' => '1bd up to 45sqm', 'hours' => '2', 'price' => '269', 'service_id' => 112, 'created_at' => '2025-01-08 18:11:18', 'updated_at' => '2025-01-08 18:11:18'],
            ['id' => 3, 'name_en' => 'Check in cleaning', 'name_heb' => "ניקיון צ'ק אין", 'apartment_size' => '2bd up to 65sqm', 'hours' => '3', 'price' => '369', 'service_id' => 112, 'created_at' => '2025-01-08 18:11:46', 'updated_at' => '2025-01-08 18:11:46'],
            ['id' => 4, 'name_en' => 'Check in cleaning', 'name_heb' => "ניקיון צ'ק אין", 'apartment_size' => '3bd up to 85sqm', 'hours' => '4', 'price' => '449', 'service_id' => 112, 'created_at' => '2025-01-08 18:12:11', 'updated_at' => '2025-01-08 18:12:11'],
            ['id' => 5, 'name_en' => 'Check in cleaning', 'name_heb' => "ניקיון צ'ק אין", 'apartment_size' => '4bd up to 110sqm', 'hours' => '4.5', 'price' => '499', 'service_id' => 112, 'created_at' => '2025-01-08 18:12:34', 'updated_at' => '2025-01-08 18:12:34'],
            ['id' => 6, 'name_en' => 'Check in cleaning', 'name_heb' => "ניקיון צ'ק אין", 'apartment_size' => '5bd up to 140sqm', 'hours' => '5', 'price' => '569', 'service_id' => 112, 'created_at' => '2025-01-08 18:12:59', 'updated_at' => '2025-01-08 18:12:59'],
            ['id' => 7, 'name_en' => 'All windows cleaning', 'name_heb' => 'חלונות יסודי', 'apartment_size' => 'studio up to 25sqm', 'hours' => '1', 'price' => '150', 'service_id' => 112, 'created_at' => '2025-01-08 18:14:05', 'updated_at' => '2025-01-08 18:14:05'],
            ['id' => 8, 'name_en' => 'All windows cleaning', 'name_heb' => 'חלונות יסודי', 'apartment_size' => '1bd up to 45sqm', 'hours' => '2', 'price' => '259', 'service_id' => 112, 'created_at' => '2025-01-08 18:14:30', 'updated_at' => '2025-01-08 18:14:30'],
            ['id' => 9, 'name_en' => 'All windows cleaning', 'name_heb' => 'חלונות יסודי', 'apartment_size' => '2bd up to 65sqm', 'hours' => '3', 'price' => '339', 'service_id' => 112, 'created_at' => '2025-01-08 18:15:13', 'updated_at' => '2025-01-08 18:15:13'],
            ['id' => 10, 'name_en' => 'All windows cleaning', 'name_heb' => 'חלונות יסודי', 'apartment_size' => '3bd up to 85sqm', 'hours' => '3.5', 'price' => '399', 'service_id' => 112, 'created_at' => '2025-01-08 18:15:55', 'updated_at' => '2025-01-08 18:15:55'],
            ['id' => 11, 'name_en' => 'All windows cleaning', 'name_heb' => 'חלונות יסודי', 'apartment_size' => '4bd up to 110sqm', 'hours' => '4.5', 'price' => '469', 'service_id' => 112, 'created_at' => '2025-01-08 18:16:25', 'updated_at' => '2025-01-08 18:16:35'],
            ['id' => 12, 'name_en' => 'All windows cleaning', 'name_heb' => 'חלונות יסודי', 'apartment_size' => '5bd up to 140sqm', 'hours' => '5.5', 'price' => '469', 'service_id' => 112, 'created_at' => '2025-01-08 18:17:05', 'updated_at' => '2025-01-08 18:17:05'],
            ['id' => 13, 'name_en' => 'Cleaning', 'name_heb' => 'ניקיון', 'apartment_size' => '', 'hours' => null, 'price' => '400', 'service_id' => 112, 'created_at' => '2025-01-08 18:10:39', 'updated_at' => '2025-01-08 18:10:39'],
            ['id' => 14, 'name_en' => 'Windows', 'name_heb' => 'חלונות', 'apartment_size' => '', 'hours' => null, 'price' => '150', 'service_id' => 112, 'created_at' => '2025-01-08 18:10:39', 'updated_at' => '2025-01-08 18:10:39'],
        ];

        subservices::insert($data);
    }
}
