<?php

namespace Tests\Feature\Clinic;

use App\Actions\Procedures\AddStarterProcedures;
use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\Procedure;
use App\Models\TreatmentItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProcedureCatalogueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
    }

    public function test_production_starts_with_an_empty_catalogue_that_admins_fill_with_the_starter_list(): void
    {
        $this->assertSame(0, Procedure::count(), 'Migrations alone must not add procedures.');

        Livewire::test('pages::clinic.procedures.index')
            ->assertSee('The catalogue is empty')
            ->call('addStarter')
            ->assertSee('Root canal treatment');

        $this->assertSame(count(AddStarterProcedures::PROCEDURES), Procedure::count());
        $this->assertTrue(AuditLog::where('action', 'procedure.starter_added')->exists());
    }

    public function test_adding_the_starter_list_again_never_overwrites_the_clinics_edits(): void
    {
        app(AddStarterProcedures::class)->handle();
        Procedure::where('code', 'FILL')->update(['name' => 'Composite filling', 'default_duration_minutes' => 60]);

        $this->assertSame(0, app(AddStarterProcedures::class)->handle());
        $this->assertSame('Composite filling', Procedure::where('code', 'FILL')->value('name'));
        $this->assertSame(60, Procedure::where('code', 'FILL')->value('default_duration_minutes'));
    }

    public function test_a_procedure_is_created_with_an_uppercase_unique_code(): void
    {
        Livewire::test('pages::clinic.procedures.index')
            ->call('create')
            ->set('form.code', 'whiten')
            ->set('form.name', 'Teeth whitening')
            ->set('form.name_ar', 'تبييض الأسنان')
            ->set('form.category', 'cosmetic')
            ->set('form.scope', 'mouth')
            ->set('form.default_duration_minutes', 60)
            ->call('save')
            ->assertHasNoErrors();

        $procedure = Procedure::sole();
        $this->assertSame('WHITEN', $procedure->code);
        $this->assertSame('تبييض الأسنان', $procedure->localizedName('ar'));

        Livewire::test('pages::clinic.procedures.index')
            ->call('create')
            ->set('form.code', 'WHITEN')
            ->set('form.name', 'Duplicate')
            ->call('save')
            ->assertHasErrors(['form.code']);
    }

    public function test_invalid_codes_and_durations_are_rejected(): void
    {
        Livewire::test('pages::clinic.procedures.index')
            ->call('create')
            ->set('form.code', 'has space')
            ->set('form.name', 'X')
            ->set('form.default_duration_minutes', 0)
            ->set('form.scope', 'galaxy')
            ->call('save')
            ->assertHasErrors(['form.code', 'form.default_duration_minutes', 'form.scope']);
    }

    public function test_procedures_used_in_treatments_are_deactivated_not_deleted(): void
    {
        $used = Procedure::factory()->create();
        TreatmentItem::factory()->create(['procedure_id' => $used->id]);
        $unused = Procedure::factory()->create();

        Livewire::test('pages::clinic.procedures.index')
            ->call('delete', $used->id)
            ->call('delete', $unused->id);

        $this->assertModelExists($used);
        $this->assertModelMissing($unused);

        Livewire::test('pages::clinic.procedures.index')
            ->call('edit', $used->id)
            ->set('form.is_active', false)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertFalse($used->fresh()?->is_active);
    }

    public function test_a_tampered_form_id_cannot_redirect_the_save(): void
    {
        $target = Procedure::factory()->create(['code' => 'AAA', 'name' => 'Target']);
        $other = Procedure::factory()->create(['code' => 'BBB', 'name' => 'Other']);

        Livewire::test('pages::clinic.procedures.index')
            ->call('edit', $target->id)
            ->set('form.procedureId', $other->id)
            ->set('form.name', 'Changed')
            ->call('save');

        $this->assertSame('Changed', $target->fresh()?->name);
        $this->assertSame('Other', $other->fresh()?->name);
    }

    public function test_only_administrators_manage_the_catalogue(): void
    {
        foreach (['dentist', 'receptionist'] as $role) {
            $this->actingAs(User::factory()->withRole(Role::from($role))->withTwoFactor()->create());
            $this->get(route('clinic.procedures.index'))->assertForbidden();
            Livewire::test('pages::clinic.procedures.index')->assertForbidden();
        }
    }
}
