<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $service_id
 * @property string $locale
 * @property string $question
 * @property string $answer Markdown
 * @property int $sort_order
 */
#[Fillable(['service_id', 'locale', 'question', 'answer', 'sort_order'])]
class ServiceFaq extends Model {}
