<?php

namespace Tests\Feature\Cms;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CmsAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string}>
     */
    public static function cmsRoutes(): array
    {
        return [
            'services' => ['clinic.content.services.index'],
            'new service' => ['clinic.content.services.create'],
            'blog' => ['clinic.content.blog.index'],
            'new article' => ['clinic.content.blog.create'],
            'categories' => ['clinic.content.blog.categories'],
            'pages' => ['clinic.content.pages.index'],
            'gallery' => ['clinic.content.gallery.index'],
            'new gallery item' => ['clinic.content.gallery.create'],
            'testimonials' => ['clinic.content.testimonials.index'],
            'faqs' => ['clinic.content.faqs.index'],
            'media' => ['clinic.content.media.index'],
            'clinic details' => ['clinic.content.clinic-details'],
            'messages' => ['clinic.content.messages.index'],
        ];
    }

    #[DataProvider('cmsRoutes')]
    public function test_content_staff_can_open_cms_screens(string $route): void
    {
        $this->actingAs(User::factory()->receptionist()->withTwoFactor()->create())
            ->get(route($route))
            ->assertOk();
    }

    #[DataProvider('cmsRoutes')]
    public function test_dentists_and_patients_cannot_open_cms_screens(string $route): void
    {
        $this->actingAs(User::factory()->dentist()->withTwoFactor()->create())->get(route($route))->assertForbidden();
        $this->actingAs(User::factory()->patient()->create())->get(route($route))->assertForbidden();
    }

    public function test_page_editor_rejects_unknown_page_keys(): void
    {
        $this->actingAs(User::factory()->admin()->withTwoFactor()->create())
            ->get(route('clinic.content.pages.edit', 'secret'))
            ->assertNotFound();
    }

    public function test_cms_components_check_permission_themselves(): void
    {
        // Even if a component were mounted outside its route, it refuses users without the permission.
        $this->actingAs(User::factory()->dentist()->withTwoFactor()->create());

        Livewire::test('pages::clinic.content.services.edit')->assertForbidden();
        Livewire::test('pages::clinic.content.media')->assertForbidden();
    }

    public function test_permission_is_rechecked_on_every_action(): void
    {
        $user = User::factory()->receptionist()->withTwoFactor()->create();
        $this->actingAs($user);

        $component = Livewire::test('pages::clinic.content.faqs');

        $user->syncRoles([]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $component->call('create')->assertForbidden();
    }
}
