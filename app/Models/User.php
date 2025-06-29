<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Notifications\CustomResetPassword;
use App\Models\DocumentType;

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
        'birth_date',
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
        'id_card',
        'passport_card',
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
        'driving_fees',
        'employment_type',
        'salary',
        'contactId',
        'step',
        'id_number',
        'stop_last_message',
        'has_input_one'
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
        'birth_date' => 'date',
        'is_afraid_by_cat' => 'boolean',
        'is_afraid_by_dog' => 'boolean',
    ];

    public function getFullnameAttribute()
    {
        return $this->firstname . ' ' . $this->lastname;
    }

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

    public function scheduleChanges()
    {
        return $this->morphMany(ScheduleChange::class, 'user');
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

    public function hearingInvitations()
    {
        return $this->hasMany(HearingInvitation::class);
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

    public function googleContacts()
    {
        return $this->hasMany(UserGoogleContact::class);
    }
    
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new CustomResetPassword($token, "user"));
    }

    public function conflicts()
    {
        return $this->hasMany(Conflict::class, 'worker_id');
    }

    public function documentType()
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id');
    }

    public function hasCompletedAllForms()
    {
        $requiredForms = [
            \App\Enums\WorkerFormTypeEnum::FORM101,
            \App\Enums\WorkerFormTypeEnum::CONTRACT,
            \App\Enums\WorkerFormTypeEnum::SAFTEY_AND_GEAR,
            \App\Enums\WorkerFormTypeEnum::INSURANCE,
        ];
        // For manpower company, require MANPOWER_SAFTEY instead of the others
        if ($this->company_type === 'manpower') {
            $requiredForms = [\App\Enums\WorkerFormTypeEnum::MANPOWER_SAFTEY];
        }
        $forms = $this->forms()->whereIn('type', $requiredForms)->get();
        foreach ($requiredForms as $type) {
            $form = $forms->where('type', $type)->first();
            if (!$form || !$form->submitted_at) {
                return false;
            }
        }
        return true;
    }
}
