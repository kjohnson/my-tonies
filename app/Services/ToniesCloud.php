<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Client for the (unofficial) Toniecloud API that the Tonies app uses.
 */
class ToniesCloud
{
    private const CONTENT_TONIES_QUERY = <<<'GQL'
    query {
      households {
        id
        name
        contentTonies { id title series { name } imageUrl salesId }
      }
    }
    GQL;

    /**
     * Every Content Tonie in the account's households.
     *
     * @return list<array{cloud_id: string, sales_id: ?string, title: string, series: ?string, image: ?string, household: string}>
     */
    public function contentTonies(): array
    {
        $response = Http::withToken($this->token())
            ->timeout(30)
            ->post(config('tonies.api_url').'/graphql', ['query' => self::CONTENT_TONIES_QUERY])
            ->throw();

        if ($errors = $response->json('errors')) {
            throw new RuntimeException('Toniecloud GraphQL error: '.json_encode($errors));
        }

        $tonies = [];
        foreach ($response->json('data.households') as $household) {
            foreach ($household['contentTonies'] ?? [] as $tonie) {
                $tonies[] = [
                    'cloud_id' => $tonie['id'],
                    'sales_id' => $tonie['salesId'],
                    'title' => $tonie['title'],
                    'series' => $tonie['series']['name'] ?? null,
                    'image' => $tonie['imageUrl'],
                    'household' => $household['name'],
                ];
            }
        }

        return $tonies;
    }

    private function token(): string
    {
        if (! config('tonies.username') || ! config('tonies.password')) {
            throw new RuntimeException('Set TONIES_USERNAME and TONIES_PASSWORD in the environment.');
        }

        $response = Http::asForm()->timeout(30)->post(config('tonies.token_url'), [
            'grant_type' => 'password',
            'client_id' => config('tonies.client_id'),
            'scope' => 'openid',
            'username' => config('tonies.username'),
            'password' => config('tonies.password'),
        ]);

        if ($response->failed()) {
            throw new RuntimeException("Tonies login failed ({$response->status()}): ".$response->json('error_description', $response->body()));
        }

        return $response->json('access_token');
    }
}
