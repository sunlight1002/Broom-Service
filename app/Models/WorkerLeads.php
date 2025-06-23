<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkerLeads extends Model
{
    use HasFactory;

    protected $fillable = [
        'firstname',
        'lastname',
        'phone',
        'email',
        'gender',
        'address',
        'country',
        'latitude',
        'longitude',
        'id_number',
        'visa',
        'renewal_visa',
        'passport',
        'id_card',
        'passport_card',
        'lng',
        'status',
        'sub_status',
        'reason',
        'role',
        'hourly_rate',
        'company_type',
        'manpower_company_id',
        'is_afraid_by_cat',
        'is_afraid_by_dog',
        'experience_in_house_cleaning',
        'you_have_valid_work_visa',
        'first_date',
        'source'
    ];

    public function forms()
    {
        return $this->morphMany(Form::class, 'user');
    }

    public function hasCompletedAllForms()
    {
        $requiredForms = [
            \App\Enums\WorkerFormTypeEnum::FORM101,
            \App\Enums\WorkerFormTypeEnum::CONTRACT,
            \App\Enums\WorkerFormTypeEnum::SAFTEY_AND_GEAR,
            \App\Enums\WorkerFormTypeEnum::INSURANCE,
        ];
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



