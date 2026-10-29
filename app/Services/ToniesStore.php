<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Reads the us.tonies.com catalog from its public Shopify product feed.
 */
class ToniesStore
{
    private const PREORDER_TAGS = ['preorder', 'purple-dot-has-variant-on-preorder'];

    /**
     * Tonies in the store, limited to the product types in config('tonies.categories')
     * and leaving out those only in config('tonies.hidden_languages').
     *
     * @return list<array<string, mixed>>
     */
    public function tonies(): array
    {
        $categories = config('tonies.categories');
        $hiddenLanguages = config('tonies.hidden_languages', []);

        return collect($this->products())
            ->filter(fn (array $p) => isset($categories[$p['product_type']]))
            ->map(fn (array $p) => [$p, $this->tagValues($p['tags'])])
            ->reject(fn (array $pair) => isset($pair[1]['Language']) && ! array_diff($pair[1]['Language'], $hiddenLanguages))
            ->map(function (array $pair) use ($categories) {
                [$p, $tags] = $pair;
                $skus = array_values(array_unique(array_map(
                    fn (array $v) => preg_replace('/-PS$/', '', $v['sku']),
                    $p['variants'],
                )));
                // The feed reports pre-orders as unavailable; Purple Dot (the store's pre-order app) tags them
                $preorder = (bool) array_intersect(self::PREORDER_TAGS, $p['tags']);

                return [
                    'sku' => $skus[0],
                    'skus' => $skus,
                    'handle' => $p['handle'],
                    'title' => preg_replace('/\s+Tonie(?=\s*\(|$)/', '', $p['title']),
                    'category' => $categories[$p['product_type']],
                    'series' => $tags['Franchise'][0] ?? $tags['Characters'][0] ?? null,
                    'ages' => $tags['Age Range'] ?? [],
                    'price' => (float) $p['variants'][0]['price'],
                    'image' => $p['images'][0]['src'] ?? null,
                    'available' => $preorder || collect($p['variants'])->contains('available', true),
                    'preorder' => $preorder,
                    'release_date' => $preorder ? $this->publicationDate($p['tags']) : null,
                    'published_at' => isset($p['published_at']) ? substr($p['published_at'], 0, 10) : null,
                ];
            })
            ->unique('sku')
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function products(): array
    {
        $products = [];
        for ($page = 1; ; $page++) {
            if ($page > 1) {
                usleep(500_000); // stay under Shopify's rate limit
            }

            $batch = Http::timeout(30)
                ->withOptions(['version' => 2.0])
                ->withUserAgent('my-tonies wishlist sync')
                ->retry(
                    3,
                    fn (int $attempt, Throwable $e) => 1000 * (int) ($e->response?->header('Retry-After') ?: 10 * $attempt),
                    fn (Throwable $e) => $e instanceof RequestException && $e->response->status() === 429,
                )
                ->get(config('tonies.store_url').'/products.json', ['limit' => 250, 'page' => $page])
                ->throw()
                ->json('products', []);

            if (! $batch) {
                return $products;
            }
            array_push($products, ...$batch);
        }
    }

    /**
     * Release date from a "Publication Date: MM/DD/YYYY" tag, as Y-m-d.
     *
     * @param  list<string>  $tags
     */
    private function publicationDate(array $tags): ?string
    {
        foreach ($tags as $tag) {
            if (preg_match('#^Publication Date: (\d{2})/(\d{2})/(\d{4})$#', $tag, $m)) {
                return "$m[3]-$m[1]-$m[2]";
            }
        }

        return null;
    }

    /**
     * Collect "Key | Value" Shopify tags into [key => [values]].
     *
     * @param  list<string>  $tags
     * @return array<string, list<string>>
     */
    private function tagValues(array $tags): array
    {
        $out = [];
        foreach ($tags as $tag) {
            if (str_contains($tag, ' | ')) {
                [$key, $value] = explode(' | ', $tag, 2);
                $out[trim($key)][] = trim($value);
            }
        }

        return $out;
    }
}
