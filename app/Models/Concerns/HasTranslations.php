<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\App;

/**
 * For models whose text lives in a `*_translations` table (one row per locale).
 * The model must define translations(): HasMany.
 */
trait HasTranslations
{
    /**
     * @return HasMany<covariant Model, $this>
     */
    abstract public function translations(): HasMany;

    /**
     * Translation for the locale (current locale by default), or null if missing.
     */
    public function translation(?string $locale = null): ?Model
    {
        $locale ??= App::getLocale();

        return $this->translations->firstWhere('locale', $locale);
    }

    /**
     * Translation for the locale, falling back to any available one. For staff
     * screens; public pages must use translation() so nothing shows in the wrong language.
     */
    public function translationOrFirst(?string $locale = null): ?Model
    {
        return $this->translation($locale) ?? $this->translations->first();
    }

    /**
     * @return list<string>
     */
    public function translatedLocales(): array
    {
        $locales = [];

        foreach ($this->translations as $translation) {
            $locale = $translation->getAttribute('locale');
            if (is_string($locale)) {
                $locales[] = $locale;
            }
        }

        return $locales;
    }

    /**
     * Only records translated into the locale.
     *
     * @param  Builder<static>  $query
     */
    public function scopeTranslatedIn(Builder $query, ?string $locale = null): void
    {
        $locale ??= App::getLocale();

        $query->whereHas('translations', fn (Builder $t) => $t->where('locale', $locale));
    }
}
