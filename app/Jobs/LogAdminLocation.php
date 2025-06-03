<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class LogAdminLocation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $adminId;
    protected $ip;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($adminId, $ip)
    {
        $this->adminId = $adminId;
        $this->ip = $ip;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $country = 'Unknown';

        try {
            $raw = Http::get("http://ip-api.com/php/{$this->ip}")->body();
            $geo = @unserialize($raw);

            if ($geo && is_array($geo) && ($geo['status'] ?? '') === 'success') {
                $country = $geo['country'] ?? 'Unknown';
            }
        } catch (\Exception $e) {
            // Optional: Log the error
        }

        \App\Models\Admin::where('id', $this->adminId)->update([
            'ip' => $this->ip,
            'country' => $country,
            'last_activity_date' => now(),
        ]);
    }
}
