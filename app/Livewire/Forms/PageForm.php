<?php

namespace App\Livewire\Forms;

use App\Enums\PublicationStatus;
use App\Livewire\Forms\Concerns\TranslatedFields;
use App\Models\Page;
use App\Support\Locales;
use Illuminate\Validation\Rule;
use Livewire\Form;

class PageForm extends Form
{
    use TranslatedFields;

    private const FIELDS = ['title', 'body', 'seo_title', 'meta_description'];

    public string $status = 'draft';

    /** @var array<string, array<string, ?string>> */
    public array $translations = [];

    public function load(Page $page): void
    {
        // A page that has never been saved has no status yet.
        $this->status = $page->exists ? $page->status->value : PublicationStatus::Draft->value;
        $this->translations = $this->blankOrExistingTranslations(self::FIELDS, $page->translations);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $rules = ['status' => ['required', Rule::enum(PublicationStatus::class)]];

        foreach (Locales::supported() as $l) {
            $rules["translations.{$l}.title"] = [$this->titleRule($l), 'string', 'max:255'];
            $rules["translations.{$l}.body"] = ['nullable', 'string', 'max:50000'];
            $rules["translations.{$l}.seo_title"] = ['nullable', 'string', 'max:255'];
            $rules["translations.{$l}.meta_description"] = ['nullable', 'string', 'max:320'];
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
        return ['status' => $this->status, 'translations' => $this->translations];
    }
}
