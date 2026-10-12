<?php

namespace App\Actions\Content\Concerns;

use App\Enums\PublicationStatus;
use App\Support\Locales;
use App\Support\Slug;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Date;

trait SavesTranslatedContent
{
    /**
     * Upsert one translation row per locale that has a title; remove locales left empty.
     *
     * @param  HasMany<covariant Model, covariant Model>  $relation
     * @param  array<string, array<string, mixed>>  $translations  keyed by locale
     */
    protected function syncTranslations(HasMany $relation, array $translations, string $titleKey = 'title', bool $withSlug = true): void
    {
        // Fresh queries each time: calling where() on $relation would stack constraints across locales.
        $query = fn () => $relation->getRelated()->newQuery()->where($relation->getForeignKeyName(), $relation->getParentKey());

        foreach (Locales::supported() as $locale) {
            $fields = $translations[$locale] ?? [];
            $title = trim((string) ($fields[$titleKey] ?? ''));

            if ($title === '') {
                $query()->where('locale', $locale)->delete();

                continue;
            }

            $values = array_map(fn ($v) => is_string($v) ? (trim($v) === '' ? null : trim($v)) : $v, $fields);
            $values[$titleKey] = $title;

            if ($withSlug) {
                $values['slug'] = Slug::make((string) ($values['slug'] ?? '') ?: $title);
            }

            $query()->updateOrCreate(
                [$relation->getForeignKeyName() => $relation->getParentKey(), 'locale' => $locale],
                $values,
            );
        }
    }

    /**
     * Replace a parent's per-locale child rows (sections, FAQs) with the given lists.
     *
     * @param  HasMany<covariant Model, covariant Model>  $relation
     * @param  array<string, list<array<string, mixed>>>  $itemsByLocale
     */
    protected function replaceLocalizedChildren(HasMany $relation, array $itemsByLocale, string $requiredKey): void
    {
        $relation->delete();

        foreach (Locales::supported() as $locale) {
            $position = 0;

            foreach ($itemsByLocale[$locale] ?? [] as $item) {
                if (trim((string) ($item[$requiredKey] ?? '')) === '') {
                    continue;
                }

                $relation->create([...array_map(fn ($v) => is_string($v) ? trim($v) : $v, $item), 'locale' => $locale, 'sort_order' => $position++]);
            }
        }
    }

    /**
     * First publication sets published_at; an explicit date (scheduling) wins.
     */
    protected function publishedAt(PublicationStatus $status, ?CarbonInterface $current, mixed $requested = null): ?CarbonInterface
    {
        if (filled($requested)) {
            return Date::parse($requested);
        }

        if ($status === PublicationStatus::Published) {
            return $current ?? now();
        }

        return $current;
    }
}
