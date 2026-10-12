<?php

namespace App\Models;

use Database\Factories\ProcedureFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Internal catalogue entry (e.g. composite filling, extraction).
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $name_ar
 * @property string|null $category
 * @property string|null $description
 * @property int $default_duration_minutes
 * @property string $scope tooth|surface|mouth
 * @property bool $is_active
 */
#[Fillable(['code', 'name', 'name_ar', 'category', 'description', 'default_duration_minutes', 'scope', 'is_active'])]
class Procedure extends Model
{
    /** @use HasFactory<ProcedureFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
