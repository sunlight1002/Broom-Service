<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WhatsappTemplatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('whatsapp_templates')->insert([
            [
                'key' => 'NOTIFY_MONDAY_CLIENT_AND_WORKER_FOR_SCHEDULE',
                'message_en' => 'Welcome to our service!',
                'message_heb' => 'ברוכים הבאים לשירות שלנו!',
                'message_spa' => '¡Bienvenido a nuestro servicio!',
                'message_rus' => 'Добро пожаловать в наш сервис!',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}
