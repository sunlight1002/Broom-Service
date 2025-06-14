<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddIndexesToTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Adding index for the 'clients' table
        if (!$this->indexExists('clients', 'idx_clients_geo_address')) {
        Schema::table('clients', function (Blueprint $table) {
            // $table->index('status', 'idx_clients_status');
            $table->index('email', 'idx_clients_email');
            $table->index('geo_address', 'idx_clients_geo_address');
        });
        }

        if (!$this->indexExists('clients', 'idx_clients_status')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->index('status', 'idx_clients_status');
            });
        }

        // Adding index for the 'users' table
        Schema::table('users', function (Blueprint $table) {
            $table->index('country', 'idx_users_country');
        });

        // Adding index for the 'leadstatus' table
        Schema::table('leadstatus', function (Blueprint $table) {
            $table->index('lead_status', 'idx_leadstatus_lead_status');
            $table->index('client_id', 'idx_leadstatus_client_id');
        });

        // Adding index for the 'schedules' table
        Schema::table('schedules', function (Blueprint $table) {
            $table->index('client_id', 'idx_schedules_client_id');
            $table->index('start_date', 'idx_schedules_start_date');
            $table->index('start_time', 'idx_schedules_start_time');
            $table->index('end_time', 'idx_schedules_end_time');
            $table->index('meet_via', 'idx_schedules_meet_via');
            $table->index('purpose', 'idx_schedules_purpose');
            $table->index('google_calendar_event_id', 'idx_schedules_google_calendar_event_id');
        });

        // Adding index for the 'services' table
        Schema::table('services', function (Blueprint $table) {
            $table->index('name', 'idx_services_name');
        });

        // Adding index for the 'offers' table
        Schema::table('offers', function (Blueprint $table) {
            $table->index('status', 'idx_offers_status');
            $table->index('client_id', 'idx_offers_client_id');
        });

        // Adding index for the 'contracts' table
        Schema::table('contracts', function (Blueprint $table) {
            $table->index('status', 'idx_contracts_status');
            $table->index('client_id', 'idx_contracts_client_id');
            $table->index('offer_id', 'idx_contracts_offer_id');
            $table->index('signed_at', 'idx_contracts_signed_at');
        });

        // Adding index for the 'jobs' table
        Schema::table('jobs', function (Blueprint $table) {
            $table->index('client_id', 'idx_jobs_client_id');
            $table->index('status', 'idx_jobs_status');
            $table->index('offer_id', 'idx_jobs_offer_id');
            $table->index('worker_id', 'idx_jobs_worker_id');
            $table->index('start_date', 'idx_jobs_start_date');
            $table->index('contract_id', 'idx_jobs_contract_id');
            $table->index('created_at', 'idx_jobs_created_at');
        });

        // Adding index for the 'job_services' table
        Schema::table('job_services', function (Blueprint $table) {
            $table->index('job_id', 'idx_job_services_job_id');
            $table->index('service_id', 'idx_job_services_service_id');
        });

        // Adding index for the 'client_property_addresses' table
        Schema::table('client_property_addresses', function (Blueprint $table) {
            $table->index('client_id', 'idx_client_property_addresses_client_id');
            $table->index('geo_address', 'idx_client_property_addresses_geo_address');
            $table->index('latitude', 'idx_client_property_addresses_latitude');
            $table->index('longitude', 'idx_client_property_addresses_longitude');
            $table->index('zipcode', 'idx_client_property_addresses_zipcode');
        });

        // Adding index for the 'worker_avialibilties' table
        Schema::table('worker_avialibilties', function (Blueprint $table) {
            $table->index('user_id', 'idx_worker_avialibilties_user_id');
            $table->index('date', 'idx_worker_avialibilties_date');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Dropping the indexes in reverse order
        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex('idx_clients_status');
            $table->dropIndex('idx_clients_email');
            $table->dropIndex('idx_clients_geo_address');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_address');
            $table->dropIndex('idx_users_country');
        });

        Schema::table('leadstatus', function (Blueprint $table) {
            $table->dropIndex('idx_leadstatus_lead_status');
            $table->dropIndex('idx_leadstatus_client_id');
        });

        Schema::table('schedules', function (Blueprint $table) {
            $table->dropIndex('idx_schedules_lead_status');
            $table->dropIndex('idx_schedules_client_id');
            $table->dropIndex('idx_schedules_start_date');
            $table->dropIndex('idx_schedules_start_time');
            $table->dropIndex('idx_schedules_end_time');
            $table->dropIndex('idx_schedules_meet_via');
            $table->dropIndex('idx_schedules_purpose');
            $table->dropIndex('idx_schedules_google_calendar_event_id');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropIndex('idx_services_name');
        });

        Schema::table('offers', function (Blueprint $table) {
            $table->dropIndex('idx_offers_status');
            $table->dropIndex('idx_offers_client_id');
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->dropIndex('idx_contracts_status');
            $table->dropIndex('idx_contracts_client_id');
            $table->dropIndex('idx_contracts_offer_id');
            $table->dropIndex('idx_contracts_signed_at');
        });

        Schema::table('jobs', function (Blueprint $table) {
            $table->dropIndex('idx_jobs_client_id');
            $table->dropIndex('idx_jobs_status');
            $table->dropIndex('idx_jobs_offer_id');
            $table->dropIndex('idx_jobs_worker_id');
            $table->dropIndex('idx_jobs_start_date');
            $table->dropIndex('idx_jobs_contract_id');
            $table->dropIndex('idx_jobs_created_at');
        });

        Schema::table('job_services', function (Blueprint $table) {
            $table->dropIndex('idx_job_services_job_id');
            $table->dropIndex('idx_job_services_service_id');
        });

        Schema::table('client_property_addresses', function (Blueprint $table) {
            $table->dropIndex('idx_client_property_addresses_client_id');
            $table->dropIndex('idx_client_property_addresses_geo_address');
            $table->dropIndex('idx_client_property_addresses_latitude');
            $table->dropIndex('idx_client_property_addresses_longitude');
            $table->dropIndex('idx_client_property_addresses_zipcode');
        });

        Schema::table('worker_avialibilties', function (Blueprint $table) {
            $table->dropIndex('idx_worker_avialibilties_user_id');
            $table->dropIndex('idx_worker_avialibilties_date');
        });
    }


    private function indexExists($table, $indexName)
    {
        $indexes = DB::select("SHOW INDEXES FROM `$table` WHERE Key_name = ?", [$indexName]);
        return !empty($indexes);
    }
}
