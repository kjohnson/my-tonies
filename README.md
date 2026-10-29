# My Tonies

A small Laravel app that shares our Tonies wishlist.

- `/`: public wishlist, with links to each Tonie on us.tonies.com
- `/already-have`: public list of the Tonies we own, so nobody doubles up
- `/admin`: log in to pick wishlist Tonies from everything in the US store we don't have yet

Owned Tonies come from the (unofficial) Toniecloud API, and the catalog comes from the us.tonies.com Shopify feed. `php artisan tonies:sync` refreshes both. It runs daily on the scheduler, and the admin page has a **Sync now** button. A Tonie leaves the wishlist on its own once it shows up in our Toniecloud.

## Local setup

```sh
composer install
cp .env.example .env    # fill in TONIES_USERNAME and TONIES_PASSWORD
php artisan key:generate
php artisan migrate
php artisan user:create # the account that manages the wishlist
php artisan tonies:sync
php artisan serve
```

Pack overrides (multi-packs we own, and notes on cards) live in `config/tonies.php`.

## Deploying to Laravel Cloud

1. Push this repo to GitHub, GitLab or Bitbucket and create a Laravel Cloud application from it.
2. Attach a database (MySQL or Postgres). Cloud's filesystem is wiped on each deploy, so SQLite won't keep the wishlist.
3. Environment variables: `APP_KEY` (from `php artisan key:generate --show`), `TONIES_USERNAME`, `TONIES_PASSWORD`, and optionally `PAGE_TITLE`.
4. Build command: `composer install --no-dev`. Deploy command: `php artisan migrate --force`.
5. Turn on the **scheduler** for the app's compute, so the daily `tonies:sync` runs.
6. After the first deploy, run `php artisan user:create` and `php artisan tonies:sync` from Cloud's command runner.
