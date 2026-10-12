<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $staff_profile_id
 * @property string $locale
 * @property string $display_name
 * @property string|null $specialty
 * @property string|null $bio Markdown
 */
#[Fillable(['staff_profile_id', 'locale', 'display_name', 'specialty', 'bio'])]
class StaffProfileTranslation extends Model {}
