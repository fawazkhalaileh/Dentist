<?php

namespace App\Models;

use App\Models\Concerns\Publishable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * General clinic FAQ in one language.
 *
 * @property int $id
 * @property string $locale
 * @property string $question
 * @property string $answer Markdown
 * @property int $sort_order
 */
#[Fillable(['locale', 'question', 'answer', 'status', 'published_at', 'sort_order'])]
class Faq extends Model
{
    use Publishable;
}
