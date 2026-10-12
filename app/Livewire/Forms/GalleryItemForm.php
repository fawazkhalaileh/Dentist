<?php

namespace App\Livewire\Forms;

use App\Enums\PublicationStatus;
use App\Livewire\Forms\Concerns\TranslatedFields;
use App\Models\GalleryItem;
use App\Support\Locales;
use Illuminate\Validation\Rule;
use Livewire\Form;

class GalleryItemForm extends Form
{
    use TranslatedFields;

    private const FIELDS = ['title', 'description'];

    public string $status = 'draft';

    public ?int $service_id = null;

    public ?int $before_media_id = null;

    public ?int $after_media_id = null;

    public bool $consent_confirmed = false;

    public int $sort_order = 0;

    /** @var array<string, array<string, ?string>> */
    public array $translations = [];

    public function load(?GalleryItem $item): void
    {
        $this->translations = $this->blankOrExistingTranslations(self::FIELDS, $item?->translations);

        if ($item) {
            $this->status = $item->status->value;
            $this->service_id = $item->service_id;
            $this->before_media_id = $item->before_media_id;
            $this->after_media_id = $item->after_media_id;
            $this->consent_confirmed = $item->consent_confirmed_at !== null;
            $this->sort_order = $item->sort_order;
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $rules = [
            'status' => ['required', Rule::enum(PublicationStatus::class)],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'before_media_id' => ['required', 'integer', 'exists:media,id'],
            'after_media_id' => ['required', 'integer', 'exists:media,id', 'different:before_media_id'],
            'consent_confirmed' => ['boolean', 'accepted_if:status,published'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
        ];

        foreach (Locales::supported() as $l) {
            $rules["translations.{$l}.title"] = [$this->titleRule($l), 'string', 'max:255'];
            $rules["translations.{$l}.description"] = ['nullable', 'string', 'max:1000'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'consent_confirmed.accepted_if' => __('Confirm the patient\'s written consent before publishing.'),
            'before_media_id.required' => __('Upload a "before" photo.'),
            'after_media_id.required' => __('Upload an "after" photo.'),
        ];
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
            'service_id' => $this->service_id ?: null,
            'before_media_id' => (int) $this->before_media_id,
            'after_media_id' => (int) $this->after_media_id,
            'consent_confirmed' => $this->consent_confirmed,
            'sort_order' => $this->sort_order,
            'translations' => $this->translations,
        ];
    }
}
