<?php

namespace Tests\Feature;

use App\Models\CatalogTonie;
use App\Models\OwnedTonie;
use App\Services\TonieSync;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TonieSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['tonies.username' => 'parent@example.com', 'tonies.password' => 'secret', 'tonies.owned_skus' => ['900']]);
    }

    private array $ownedSalesIds = [];

    private array $products = [];

    /**
     * Fake the Toniecloud and store responses; later calls change what the fakes return.
     */
    private function fakeApis(array $ownedSalesIds, array $products): void
    {
        $this->ownedSalesIds = $ownedSalesIds;
        $this->products = $products;

        Http::fake([
            'login.tonies.com/*' => Http::response(['access_token' => 'token']),
            'api.tonie.cloud/v2/graphql' => fn () => Http::response(['data' => ['households' => [[
                'id' => 'h1',
                'name' => 'Home',
                'contentTonies' => array_map(fn ($id) => [
                    'id' => "cloud-$id", 'title' => "Owned $id", 'series' => null, 'imageUrl' => null, 'salesId' => $id,
                ], $this->ownedSalesIds),
            ]]]]),
            'us.tonies.com/products.json?limit=250&page=1' => fn () => Http::response(['products' => $this->products]),
            'us.tonies.com/products.json?limit=250&page=2' => Http::response(['products' => []]),
        ]);
    }

    private function product(string $sku, string $title, string $type = 'Tonie', bool $available = true, string $language = 'English'): array
    {
        return [
            'title' => "$title Tonie",
            'handle' => str($title)->slug()->toString(),
            'product_type' => $type,
            'tags' => ['Age Range | 3-4', 'Franchise | Test Series', "Language | $language"],
            'variants' => [['sku' => $sku, 'price' => '19.99', 'available' => $available]],
            'images' => [['src' => 'https://cdn.example.com/'.$sku.'.png?v=1']],
            'published_at' => '2026-01-02T00:00:00-05:00',
        ];
    }

    public function test_sync_flags_owned_tonies_by_sku_and_override(): void
    {
        $this->fakeApis(['100'], [
            $this->product('100', 'Owned One'),
            $this->product('200', 'Missing One'),
            $this->product('900', 'Owned Pack'),
            $this->product('300', 'A Toniebox', type: 'Toniebox'),
        ]);

        $result = app(TonieSync::class)->run();

        $this->assertSame(['owned' => 1, 'catalog' => 3, 'missing' => 1], $result);
        $this->assertTrue(CatalogTonie::where('sku', '100')->value('owned'));
        $this->assertTrue(CatalogTonie::where('sku', '900')->value('owned'));
        $this->assertFalse(CatalogTonie::where('sku', '200')->value('owned'));
        $this->assertSame('Missing One', CatalogTonie::where('sku', '200')->value('title'));
        $this->assertSame(1, OwnedTonie::count());
    }

    public function test_sync_treats_preorders_as_available(): void
    {
        $preorder = $this->product('500', 'Coming Soon', available: false);
        array_push($preorder['tags'], 'preorder', 'PreOrder Date: 10/01/2026', 'Publication Date: 12/01/2026');
        $this->fakeApis([], [$preorder, $this->product('501', 'Sold Out', available: false)]);

        app(TonieSync::class)->run();

        $tonie = CatalogTonie::where('sku', '500')->first();
        $this->assertTrue($tonie->available);
        $this->assertSame('Pre-order · releases Dec 1', $tonie->preorderLabel());
        $this->assertFalse(CatalogTonie::where('sku', '501')->value('available'));
    }

    public function test_sync_skips_spanish_only_tonies_but_keeps_bilingual(): void
    {
        $this->fakeApis([], [
            $this->product('400', 'Spanish', language: 'Español (Spanish)'),
            $this->product('401', 'Bilingual', language: 'English & Español (Spanish)'),
        ]);

        app(TonieSync::class)->run();

        $this->assertSame(['401'], CatalogTonie::pluck('sku')->all());
    }

    public function test_sync_keeps_wishlist_and_drops_tonies_once_owned(): void
    {
        $this->fakeApis([], [$this->product('200', 'Wanted')]);
        app(TonieSync::class)->run();
        CatalogTonie::where('sku', '200')->update(['wishlisted_at' => now()]);

        $this->products = [$this->product('200', 'Wanted', available: false)];
        app(TonieSync::class)->run();
        $this->assertSame(1, CatalogTonie::wishlist()->count());
        $this->assertFalse(CatalogTonie::where('sku', '200')->value('available'));

        $this->ownedSalesIds = ['200'];
        $this->products = [$this->product('200', 'Wanted')];
        app(TonieSync::class)->run();
        $this->assertSame(0, CatalogTonie::wishlist()->count());
    }

    public function test_failed_login_leaves_data_untouched(): void
    {
        OwnedTonie::create(['cloud_id' => 'x', 'sales_id' => '1', 'title' => 'Kept', 'household' => 'Home']);
        Http::fake(['login.tonies.com/*' => Http::response(['error_description' => 'Invalid user credentials'], 401)]);

        try {
            app(TonieSync::class)->run();
            $this->fail('Expected the sync to throw.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Invalid user credentials', $e->getMessage());
        }

        $this->assertSame(1, OwnedTonie::count());
    }
}
