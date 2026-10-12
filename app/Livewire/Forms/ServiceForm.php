<?php

namespace App\Livewire\Forms;

use App\Enums\PublicationStatus;
use App\Livewire\Forms\Concerns\TranslatedFields;
use App\Models\Service;
use App\Models\ServiceFaq;
use App\Models\ServiceSection;
use App\Models\User;
use App\Support\Locales;
use Illuminate\Validation\Rule;
use Livewire\Form;

class ServiceForm extends Form
{
    use TranslatedFields;

    private const FIELDS = ['title', 'slug', 'summary', 'procedure_information', 'seo_title', 'meta_description'];

    public ?int $serviceId = null;

    public string $status = 'draft';

    public bool $is_bookable = true;

    public int $duration_minutes = 30;

    public int $sort_order = 0;

    public ?int $featured_media_id = null;

    /** @var list<int> */
    public array $dentist_ids = [];

    /** @var array<string, array<string, ?string>> */
    public array $translations = [];

    /** @var array<string, list<array{heading: string, body: string}>> */
    public array $sections = [];

    /** @var array<string, list<array{question: string, answer: string}>> */
    public array $faqs = [];

    public function load(?Service $service): void
    {
        $this->serviceId = $service?->id;
        $this->translations = $this->blankOrExistingTranslations(self::FIELDS, $service?->translations);

        foreach (Locales::supported() as $locale) {
            $this->sections[$locale] = $service
                ? array_values($service->sections->where('locale', $locale)->map(fn (ServiceSection $s) => ['heading' => $s->heading, 'body' => $s->body])->all())
                : [];
            $this->faqs[$locale] = $service
                ? array_values($service->faqs->where('locale', $locale)->map(fn (ServiceFaq $f) => ['question' => $f->question, 'answer' => $f->answer])->all())
                : [];
        }

        if ($service) {
            $this->status = $service->status->value;
            $this->is_bookable = $service->is_bookable;
            $this->duration_minutes = $service->duration_minutes;
            $this->sort_order = $service->sort_order;
            $this->featured_media_id = $service->featured_media_id;
            $this->dentist_ids = array_values($service->dentists->map(fn (User $d) => $d->id)->all());
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $rules = [
            'status' => ['required', Rule::enum(PublicationStatus::class)],
            'is_bookable' => ['boolean'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:480'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
            'featured_media_id' => ['nullable', 'integer', 'exists:media,id'],
            'dentist_ids' => ['array'],
            'dentist_ids.*' => ['integer', 'exists:users,id'],
        ];

        foreach (Locales::supported() as $l) {
            $rules["translations.{$l}.title"] = [$this->titleRule($l), 'string', 'max:255'];
            $rules["translations.{$l}.slug"] = $this->slugRules('service_translations', $l, 'service_id', $this->serviceId);
            $rules["translations.{$l}.summary"] = ['nullable', 'string', 'max:500'];
            $rules["translations.{$l}.procedure_information"] = ['nullable', 'string', 'max:20000'];
            $rules["translations.{$l}.seo_title"] = ['nullable', 'string', 'max:255'];
            $rules["translations.{$l}.meta_description"] = ['nullable', 'string', 'max:320'];
            $rules["sections.{$l}"] = ['array', 'max:20'];
            $rules["sections.{$l}.*.heading"] = ['required', 'string', 'max:255'];
            $rules["sections.{$l}.*.body"] = ['required', 'string', 'max:20000'];
            $rules["faqs.{$l}"] = ['array', 'max:30'];
            $rules["faqs.{$l}.*.question"] = ['required', 'string', 'max:255'];
            $rules["faqs.{$l}.*.answer"] = ['required', 'string', 'max:5000'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return $this->translatedAttributeNames(self::FIELDS);
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return [
            'status' => $this->status,
            'is_bookable' => $this->is_bookable,
            'duration_minutes' => $this->duration_minutes,
            'sort_order' => $this->sort_order,
            'featured_media_id' => $this->featured_media_id,
            'dentist_ids' => array_map('intval', $this->dentist_ids),
            'translations' => $this->translations,
            'sections' => $this->sections,
            'faqs' => $this->faqs,
        ];
    }
}
