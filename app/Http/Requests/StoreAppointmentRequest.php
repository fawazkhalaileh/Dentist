<?php

namespace App\Http\Requests;

use App\Models\AppointmentRequest;
use App\Models\Service;
use App\Models\StaffProfile;
use App\Support\ClinicTime;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+()\-\s.]{6,}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            // Only dentists and services that are shown publicly can be chosen.
            'dentist' => ['nullable', 'integer', Rule::in(StaffProfile::shownPublicly()->pluck('user_id')->all())],
            'service' => ['nullable', 'integer', Rule::in(Service::published()->pluck('id')->all())],
            'preferred_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:'.ClinicTime::today()],
            'preferred_time' => ['required', Rule::in(AppointmentRequest::PREFERRED_TIMES)],
            'message' => ['nullable', 'string', 'max:2000'],
            // Honeypot: real visitors never see or fill this field.
            'website' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['phone.regex' => __('Enter a phone number we can call you on.')];
    }
}
