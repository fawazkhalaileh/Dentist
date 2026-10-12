<?php

namespace App\Livewire\Forms;

use App\Enums\PublicationStatus;
use App\Models\Testimonial;
use App\Support\Locales;
use Illuminate\Validation\Rule;
use Livewire\Form;

class TestimonialForm extends Form
{
    public ?int $testimonialId = null;

    public string $locale = 'en';

    public string $author_name = '';

    public string $body = '';

    public ?int $rating = null;

    public ?int $service_id = null;

    public bool $consent_confirmed = false;

    public string $status = 'draft';

    public int $sort_order = 0;

    public function load(?Testimonial $testimonial): void
    {
        $this->reset();
        $this->locale = Locales::default();

        if ($testimonial) {
            $this->testimonialId = $testimonial->id;
            $this->locale = $testimonial->locale;
            $this->author_name = $testimonial->author_name;
            $this->body = $testimonial->body;
            $this->rating = $testimonial->rating;
            $this->service_id = $testimonial->service_id;
            $this->consent_confirmed = $testimonial->consent_confirmed_at !== null;
            $this->status = $testimonial->status->value;
            $this->sort_order = $testimonial->sort_order;
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'locale' => ['required', Rule::in(Locales::supported())],
            'author_name' => ['required', 'string', 'max:100'],
            'body' => ['required', 'string', 'max:1500'],
            'rating' => ['nullable', 'integer', 'between:1,5'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'consent_confirmed' => ['boolean', 'accepted_if:status,published'],
            'status' => ['required', Rule::enum(PublicationStatus::class)],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return ['consent_confirmed.accepted_if' => __('Confirm the patient\'s consent before publishing.')];
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return [
            'locale' => $this->locale,
            'author_name' => $this->author_name,
            'body' => $this->body,
            'rating' => $this->rating ?: null,
            'service_id' => $this->service_id ?: null,
            'consent_confirmed' => $this->consent_confirmed,
            'status' => $this->status,
            'sort_order' => $this->sort_order,
        ];
    }
}
