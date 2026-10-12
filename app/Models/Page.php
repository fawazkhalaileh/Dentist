<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use App\Models\Concerns\Publishable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Editable fixed page (about, privacy, terms), identified by key.
 *
 * @property int $id
 * @property string $key
 */
#[Fillable(['key', 'status', 'published_at'])]
class Page extends Model
{
    use HasTranslations, Publishable;

    /** Keys of the pages the site expects, with their public route. */
    public const KEYS = [
        'about' => 'public.about',
        'privacy' => 'public.legal.privacy',
        'terms' => 'public.legal.terms',
    ];

    /**
     * @return HasMany<PageTranslation, $this>
     */
    public function translations(): HasMany
    {
        return $this->hasMany(PageTranslation::class);
    }
}
