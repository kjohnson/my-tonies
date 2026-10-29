<?php

namespace Tests\Feature;

use App\Models\CatalogTonie;
use App\Models\OwnedTonie;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WishlistTest extends TestCase
{
    use RefreshDatabase;

    private function tonie(array $attributes = []): CatalogTonie
    {
        static $sku = 1000;
        $sku++;

        return CatalogTonie::create(array_merge([
            'sku' => (string) $sku, 'skus' => [(string) $sku], 'handle' => "tonie-$sku", 'title' => "Tonie $sku",
            'category' => 'Tonies', 'series' => null, 'ages' => ['3-4'], 'price' => 19.99, 'image' => null,
            'available' => true, 'owned' => false,
        ], $attributes));
    }

    public function test_public_wishlist_shows_only_picked_tonies(): void
    {
        $this->tonie(['title' => 'Picked'])->forceFill(['wishlisted_at' => now()])->save();
        $this->tonie(['title' => 'Not picked']);

        $this->get('/')->assertOk()->assertSee('Picked')->assertDontSee('Not picked');
    }

    public function test_already_have_page_lists_owned_tonies_once(): void
    {
        OwnedTonie::create(['cloud_id' => 'a', 'sales_id' => '1', 'title' => 'Moana', 'household' => 'Home']);
        OwnedTonie::create(['cloud_id' => 'b', 'sales_id' => '1', 'title' => 'Moana', 'household' => 'Home']);

        $response = $this->get('/already-have')->assertOk();
        $this->assertSame(1, substr_count($response->getContent(), '<h2>Moana</h2>'));
    }

    public function test_admin_requires_login(): void
    {
        $this->get('/admin')->assertRedirect('/login');
        $this->postJson('/admin/wishlist/1')->assertUnauthorized();
    }

    public function test_login_and_toggle_wishlist(): void
    {
        $user = User::factory()->create(['password' => 'correct-horse']);
        $tonie = $this->tonie();

        $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->post('/login', ['email' => $user->email, 'password' => 'correct-horse'])->assertRedirect('/admin');

        $this->get('/admin')->assertOk()->assertSee($tonie->title);

        $this->postJson("/admin/wishlist/{$tonie->id}")->assertJson(['wishlisted' => true]);
        $this->assertNotNull($tonie->fresh()->wishlisted_at);

        $this->postJson("/admin/wishlist/{$tonie->id}")->assertJson(['wishlisted' => false]);
        $this->assertNull($tonie->fresh()->wishlisted_at);
    }
}
