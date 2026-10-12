<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $service_id
 * @property string $locale
 * @property string $slug
 * @property string $title
 * @property string|null $summary
 * @property string|null $procedure_information
 * @property string|null $seo_title
 * @property string|null $meta_description
 */
#[Fillable(['service_id', 'locale', 'slug', 'title', 'summary', 'procedure_information', 'seo_title', 'meta_description'])]
class ServiceTranslation extends Model
{
    /**
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
