<?php

namespace App\Livewire\Forms;

use App\Livewire\Forms\Concerns\TranslatedFields;
use App\Models\BlogCategory;
use App\Support\Locales;
use Livewire\Form;

class BlogCategoryForm extends Form
{
    use TranslatedFields;

    private const FIELDS = ['name', 'slug'];

    public ?int $categoryId = null;

    public int $sort_order = 0;

    /** @var array<string, array<string, ?string>> */
    public array $translations = [];

    public function load(?BlogCategory $category): void
    {
        $this->resetValidation();
        $this->categoryId = $category?->id;
        $this->sort_order = $category->sort_order ?? 0;
        $this->translations = $this->blankOrExistingTranslations(self::FIELDS, $category?->translations);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $rules = ['sort_order' => ['required', 'integer', 'min:0', 'max:65535']];

        foreach (Locales::supported() as $l) {
            $rules["translations.{$l}.name"] = [$this->titleRule($l), 'string', 'max:100'];
            $rules["translations.{$l}.slug"] = $this->slugRules('blog_category_translations', $l, 'blog_category_id', $this->categoryId, 'name');
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
        return ['sort_order' => $this->sort_order, 'translations' => $this->translations];
    }
}
