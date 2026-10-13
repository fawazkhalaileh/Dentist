<?php

namespace App\Livewire\Forms;

use App\Models\Procedure;
use Illuminate\Validation\Rule;
use Livewire\Form;

class ProcedureForm extends Form
{
    public ?int $procedureId = null;

    public string $code = '';

    public string $name = '';

    public string $name_ar = '';

    public string $category = '';

    public string $description = '';

    public int $default_duration_minutes = 30;

    public string $scope = 'tooth';

    public string $color = '';

    public bool $is_active = true;

    public function load(?Procedure $procedure): void
    {
        $this->reset();

        if ($procedure) {
            $this->procedureId = $procedure->id;
            $this->code = $procedure->code;
            $this->name = $procedure->name;
            $this->name_ar = (string) $procedure->name_ar;
            $this->category = (string) $procedure->category;
            $this->description = (string) $procedure->description;
            $this->default_duration_minutes = $procedure->default_duration_minutes;
            $this->scope = $procedure->scope;
            $this->color = (string) $procedure->color;
            $this->is_active = $procedure->is_active;
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:30', 'regex:/^[A-Z0-9][A-Z0-9_-]*$/', Rule::unique('procedures', 'code')->ignore($this->procedureId)],
            'name' => ['required', 'string', 'max:255'],
            'name_ar' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', Rule::in(Procedure::CATEGORIES)],
            'description' => ['nullable', 'string', 'max:2000'],
            'default_duration_minutes' => ['required', 'integer', 'min:5', 'max:480'],
            'scope' => ['required', Rule::in(Procedure::SCOPES)],
            'color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return ['code.regex' => __('Use capital letters, digits, - and _ only, for example FILL-2.')];
    }

    public function prepare(): void
    {
        $this->code = strtoupper(trim($this->code));
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        $nullable = fn (string $v) => trim($v) === '' ? null : trim($v);

        return [
            'code' => $this->code,
            'name' => trim($this->name),
            'name_ar' => $nullable($this->name_ar),
            'category' => $nullable($this->category),
            'description' => $nullable($this->description),
            'default_duration_minutes' => $this->default_duration_minutes,
            'scope' => $this->scope,
            'color' => $this->color !== '' ? strtolower($this->color) : null,
            'is_active' => $this->is_active,
        ];
    }
}
