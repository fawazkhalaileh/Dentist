<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $service_id
 * @property string $locale
 * @property string $heading
 * @property string $body Markdown
 * @property int $sort_order
 */
#[Fillable(['service_id', 'locale', 'heading', 'body', 'sort_order'])]
class ServiceSection extends Model {}
