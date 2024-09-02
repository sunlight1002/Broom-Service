<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'firstname',
        'lastname',
        'phone',
        'email',
        'address',
        'renewal_visa',
        'gender',
        'role',
        'payment_per_hour',
        'worker_id',
        'lng',
        'skill',
        'company_type',
        'manpower_company_id',
        'status',
        'passcode',
        'password',
        'is_afraid_by_cat',
        'is_afraid_by_dog',
        'country',
        'form_101',
        'form_insurance',
        'worker_contract',
        'safety_and_gear_form',
        'geo_address',
        'latitude',
        'longitude',
        'freeze_shift_start_time',
        'freeze_shift_end_time',
        'visa',
        'passport',
        'last_work_date',
        'is_exist',
        'form101',
        'contract',
        'saftey_and_gear',
        'insurance',
        'is_imported',
        'is_existing_worker',
        'first_date',
        'otp',
        'otp_expiry',
        'two_factor_enabled',
        'payment_type',
        'full_name',
        'bank_name',
        'bank_number',
        'branch_number',
        'account_number',
        'driving_fees'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_afraid_by_cat' => 'boolean',
        'is_afraid_by_dog' => 'boolean',
    ];
    

    public static function boot()
    {
        parent::boot();
        static::deleting(function ($model) {
            $forms = $model->forms()->get();
            foreach ($forms as $key => $form) {
                if ($form->pdf_name && Storage::drive('public')->exists('signed-docs/' . $form->pdf_name)) {
                    Storage::drive('public')->delete('signed-docs/' . $form->pdf_name);
                }

                $form->delete();
            }

            $documents = $model->documents()->get();
            foreach ($documents as $key => $document) {
                if ($document->file && Storage::drive('public')->exists('uploads/documents/' . $document->file)) {
                    Storage::drive('public')->delete('uploads/documents/' . $document->file);
                }

                $document->delete();
            }
        });
    }

    public function leadStatuses()
    {
        return $this->hasMany(LeadStatus::class, 'client_id');
    }

    public function setSkillAttribute($value)
    {
        $this->attributes['skill'] = json_encode($value);
    }

    public function getSkillAttribute($value)
    {
        return $this->attributes['skill'] = $value;
    }

    public function problems()
    {
        return $this->hasMany(Problems::class, 'worker_id');
    }

    public function jobs()
    {
        return $this->hasMany(Job::class, 'worker_id');
    }

    public function availabilities()
    {
        return $this->hasMany(WorkerAvailability::class);
    }

    public function notAvailableDates()
    {
        return $this->hasMany(WorkerNotAvailableDate::class);
    }

    public function documents()
    {
        return $this->morphMany(Document::class, 'userable')->orderBy('created_at', 'DESC');
    }

    public function forms()
    {
        return $this->morphMany(Form::class, 'user');
    }

    public function defaultAvailabilities()
    {
        return $this->hasMany(WorkerDefaultAvailability::class);
    }

    public function jobComments()
    {
        return $this->morphMany(JobComments::class, 'commenter');
    }

    public function freezeDates()
    {
        return $this->hasMany(WorkerFreezeDate::class);
    }

    public function setPhoneAttribute($value)
    {
        $this->attributes['phone'] = preg_replace('/\D/', '', $value);
    }

    public function tasks()
    {
        return $this->morphToMany(TaskManagement::class, 'assignable','task_workers','assignable_id', 'task_management_id');
    }

    public function tokens()
    {
        return $this->morphMany(DeviceToken::class, 'tokenable');
    }
}
