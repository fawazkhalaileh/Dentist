<?php

namespace App\Livewire\Forms\Concerns;

use App\Support\Locales;
use App\Support\Slug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

/**
 * Helpers for forms with a `translations` array keyed by locale.
 *
 * @property array<string, array<string, ?string>> $translations
 */
trait TranslatedFields
{
    /**
     * @param  list<string>  $fields
     * @param  Collection<int, covariant Model>|null  $existing
     * @return array<string, array<string, ?string>>
     */
    protected function blankOrExistingTranslations(array $fields, ?Collection $existing = null): array
    {
        $translations = [];

        foreach (Locales::supported() as $locale) {
            $row = $existing?->firstWhere('locale', $locale);
            foreach ($fields as $field) {
                $value = $row?->getAttribute($field);
                $translations[$locale][$field] = $value === null ? '' : (string) $value;
            }
        }

        return $translations;
    }

    /**
     * Fill empty slugs from the title so uniqueness is validated on the real value.
     */
    public function prepareSlugs(string $titleKey = 'title'): void
    {
        foreach (Locales::supported() as $locale) {
            $title = trim((string) ($this->translations[$locale][$titleKey] ?? ''));
            $slug = trim((string) ($this->translations[$locale]['slug'] ?? ''));

            $this->translations[$locale]['slug'] = $slug === '' && $title !== '' ? Slug::make($title) : Slug::make($slug);
        }
    }

    /**
     * Slug rules: valid format and unique per locale within the translation table.
     *
     * @return list<mixed>
     */
    protected function slugRules(string $table, string $locale, string $parentColumn, ?int $parentId, string $titleKey = 'title'): array
    {
        return [
            "required_with:translations.{$locale}.{$titleKey}",
            'nullable', 'string', 'max:255', 'regex:'.Slug::PATTERN,
            Rule::unique($table, 'slug')->where('locale', $locale)->ignore($parentId, $parentColumn),
        ];
    }

    /**
     * The default locale is required; other locales are optional, but complete once started.
     */
    protected function titleRule(string $locale): string
    {
        return $locale === Locales::default() ? 'required' : 'nullable';
    }

    /**
     * Human-readable attribute names, e.g. "title (Arabic)".
     *
     * @param  list<string>  $fields
     * @return array<string, string>
     */
    protected function translatedAttributeNames(array $fields): array
    {
        $names = [];

        foreach (Locales::supported() as $locale) {
            $language = __(config("clinic.locales.{$locale}.name"));
            foreach ($fields as $field) {
                $names["translations.{$locale}.{$field}"] = __(str_replace('_', ' ', $field)).' ('.$language.')';
            }
        }

        return $names;
    }
}
