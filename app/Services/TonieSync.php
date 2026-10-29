<?php

namespace App\Services;

use App\Models\CatalogTonie;
use App\Models\OwnedTonie;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Refreshes owned Tonies from the Toniecloud and the catalog from the store,
 * then flags which catalog Tonies we own. Wishlist picks are kept.
 */
class TonieSync
{
    public const SYNCED_AT_KEY = 'tonies.synced_at';

    public function __construct(
        private ToniesCloud $cloud,
        private ToniesStore $store,
    ) {}

    /**
     * @return array{owned: int, catalog: int, missing: int}
     */
    public function run(): array
    {
        // Fetch everything before writing, so a failed request leaves the data as it was
        $owned = $this->cloud->contentTonies();
        $catalog = $this->store->tonies();

        $ownedSkus = array_flip(array_merge(
            array_filter(array_column($owned, 'sales_id')),
            config('tonies.owned_skus'),
        ));

        DB::transaction(function () use ($owned, $catalog, $ownedSkus) {
            OwnedTonie::query()->delete();
            foreach ($owned as $tonie) {
                OwnedTonie::create($tonie);
            }

            // Tonies that dropped out of the feed stay (wishlist picks may point at them) but are unavailable
            CatalogTonie::query()->whereNotIn('sku', array_column($catalog, 'sku'))->update(['available' => false]);

            foreach ($catalog as $tonie) {
                $tonie['owned'] = (bool) array_intersect_key(array_flip($tonie['skus']), $ownedSkus);
                CatalogTonie::updateOrCreate(['sku' => $tonie['sku']], $tonie);
            }
        });

        Cache::forever(self::SYNCED_AT_KEY, now()->toIso8601String());

        return [
            'owned' => count($owned),
            'catalog' => count($catalog),
            'missing' => CatalogTonie::where('owned', false)->where('available', true)->count(),
        ];
    }
}
