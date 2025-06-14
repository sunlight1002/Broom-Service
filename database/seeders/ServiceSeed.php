<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\Services;

class ServiceSeed extends Seeder
{
    public function run()
    {
        Services::insert([
            ['id' => 1, 'name' => 'Office Cleaning', 'heb_name' => 'ניקיון משרד', 'template' => 'office_cleaning', 'icon' => '🏢', 'status' => 1, 'order' => 9, 'color_code' => '#FFFFFF', 'created_at' => '2023-03-21 05:38:28', 'updated_at' => '2025-06-02 10:06:31'],
            ['id' => 2, 'name' => 'Cleaning After Renovation', 'heb_name' => 'ניקיון לאחר שיפוץ', 'template' => 'after_renovation', 'icon' => '👷', 'status' => 1, 'order' => 10, 'color_code' => '#FFFFFF', 'created_at' => '2023-03-21 05:38:28', 'updated_at' => '2025-06-02 10:06:41'],
            ['id' => 3, 'name' => 'Thorough Cleaning - Basic', 'heb_name' => 'בייסיק', 'template' => 'thorough_cleaning', 'icon' => '✨1️⃣', 'status' => 1, 'order' => 6, 'color_code' => '#FFFFFF', 'created_at' => '2023-03-21 05:38:28', 'updated_at' => '2025-06-02 09:58:32'],
            ['id' => 4, 'name' => 'window cleaning', 'heb_name' => 'ניקוי חלונות', 'template' => 'window_cleaning', 'icon' => '🪟', 'status' => 1, 'order' => 5, 'color_code' => '#FFFFFF', 'created_at' => '2023-03-21 05:38:28', 'updated_at' => '2025-06-02 09:58:10'],
            ['id' => 5, 'name' => 'Floor Polishing', 'heb_name' => 'פוליש\\ חידוש רצפות', 'template' => 'polish', 'icon' => '🧽', 'status' => 1, 'order' => 11, 'color_code' => '#FFFFFF', 'created_at' => '2023-03-21 05:38:28', 'updated_at' => '2025-06-02 10:06:48'],
            ['id' => 6, 'name' => '5 Star', 'heb_name' => '5 כוכבים', 'template' => 'regular', 'icon' => '5⭐', 'status' => 1, 'order' => 4, 'color_code' => '#FFFFFF', 'created_at' => '2023-03-21 05:38:28', 'updated_at' => '2025-06-02 09:57:50'],
            ['id' => 7, 'name' => '4 Star', 'heb_name' => '4 כוכבים', 'template' => 'regular', 'icon' => '4⭐', 'status' => 1, 'order' => 3, 'color_code' => '#FFFFFF', 'created_at' => '2023-03-21 05:38:28', 'updated_at' => '2025-06-02 09:57:42'],
            ['id' => 8, 'name' => '3 Star', 'heb_name' => '3 כוכבים', 'template' => 'regular', 'icon' => '3⭐', 'status' => 1, 'order' => 2, 'color_code' => '#FFFFFF', 'created_at' => '2023-03-21 05:38:28', 'updated_at' => '2025-06-02 09:57:35'],
            ['id' => 9, 'name' => '2 Star', 'heb_name' => '2 כוכבים', 'template' => 'regular', 'icon' => '2⭐', 'status' => 1, 'order' => 1, 'color_code' => '#FFFFFF', 'created_at' => '2023-03-21 05:38:28', 'updated_at' => '2025-06-02 09:57:27'],
            ['id' => 10, 'name' => 'Others', 'heb_name' => 'אחרים', 'template' => 'others', 'icon' => '✍', 'status' => 1, 'order' => 100, 'color_code' => '#FFFFFF', 'created_at' => '2023-03-21 05:38:28', 'updated_at' => '2025-06-02 10:08:09'],
            ['id' => 11, 'name' => 'Thorough Cleaning - Standard', 'heb_name' => 'סטנדרט', 'template' => 'thorough_cleaning', 'icon' => '✨2️⃣', 'status' => 1, 'order' => 7, 'color_code' => '#FFFFFF', 'created_at' => '2023-03-21 05:38:28', 'updated_at' => '2025-06-02 10:09:34'],
            ['id' => 12, 'name' => 'Thorough Cleaning - Premium', 'heb_name' => 'פרמיום', 'template' => 'thorough_cleaning', 'icon' => '✨3️⃣', 'status' => 1, 'order' => 8, 'color_code' => '#FFFFFF', 'created_at' => '2023-03-21 05:38:28', 'updated_at' => '2025-06-02 09:58:50'],
            ['id' => 13, 'name' => 'General Cleaning', 'heb_name' => 'ניקיון כללי', 'template' => 'regular', 'icon' => '🧹🪣', 'status' => 1, 'order' => 12, 'color_code' => '#FFFFFF', 'created_at' => null, 'updated_at' => '2025-06-02 10:06:59'],
            ['id' => 112, 'name' => 'Airbnb', 'heb_name' => 'Airbnb', 'template' => 'airbnb', 'icon' => '🏨', 'status' => 1, 'order' => 13, 'color_code' => '#FFFFFF', 'created_at' => '2025-01-08 18:09:36', 'updated_at' => '2025-06-02 10:07:11'],
        ]);
    }
}
