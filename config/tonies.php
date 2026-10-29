<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Toniecloud account
    |--------------------------------------------------------------------------
    |
    | Used by `php artisan tonies:sync` to read the Tonies your household owns.
    | REST endpoints are documented at https://api.tonie.cloud/v2/doc/ ; Content
    | Tonies have no REST list endpoint, so they come from the GraphQL API.
    |
    */

    'username' => env('TONIES_USERNAME'),
    'password' => env('TONIES_PASSWORD'),

    'token_url' => 'https://login.tonies.com/auth/realms/tonies/protocol/openid-connect/token',
    'client_id' => 'my-tonies',
    'api_url' => 'https://api.tonie.cloud/v2',

    // Heading on the public wishlist page
    'page_title' => env('PAGE_TITLE', 'Our Tonies wishlist'),

    /*
    |--------------------------------------------------------------------------
    | Store catalog
    |--------------------------------------------------------------------------
    |
    | The US store's Shopify product feed. Only these product types are synced,
    | keyed by Shopify product_type with the label shown on the site.
    |
    */

    'store_url' => 'https://us.tonies.com',

    'categories' => [
        'Tonie' => 'Tonies',
        'Clever Tonie' => 'Clever Tonies',
        'My First Tonie' => 'My First Tonies',
        'Audiobook' => 'Book Tonies',
    ],

    // Store "Language | ..." tags to leave out. Bilingual Tonies are tagged
    // "English & Español (Spanish)"; add that here to hide them too.
    'hidden_languages' => [
        'Español (Spanish)',
    ],

    /*
    |--------------------------------------------------------------------------
    | Overrides
    |--------------------------------------------------------------------------
    |
    | Owned Tonies are matched to the store by SKU (the Toniecloud salesId).
    | Multi-packs can't be matched that way because each Tonie in the pack has
    | its own ID, so list pack SKUs you own here. Notes are shown on a card.
    |
    */

    'owned_skus' => [
        '11003683', // The Chronicles of Narnia Books 1-4 pack (own all four)
        '11002329', // Magic Tree House 4 pack (own all four)
    ],

    'notes' => [
        '11000859' => 'We already have Pizza Maker and Rocket Scientist from this pack',
    ],

];
