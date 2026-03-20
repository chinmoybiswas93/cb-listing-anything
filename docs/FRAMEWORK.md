# CB Listing Anything — Architecture & Framework Guide

This document covers the full plugin architecture, the CrocoDevs framework integration, and practical guidance for extending the plugin with new features.

---

## Table of Contents

1. [High-Level Architecture](#high-level-architecture)
2. [Directory Structure](#directory-structure)
3. [Bootstrap Flow](#bootstrap-flow)
4. [Configuration System](#configuration-system)
5. [Service Container & Providers](#service-container--providers)
6. [Controllers](#controllers)
7. [REST API Layer](#rest-api-layer)
8. [Router Integration](#router-integration)
9. [Query Builder](#query-builder)
10. [Validation](#validation)
11. [Models & Helpers](#models--helpers)
12. [Config Quick Reference](#config-quick-reference)
13. [Blocks](#blocks)
14. [Views & Partials](#views--partials)
15. [Hooks System](#hooks-system)
16. [How-To: Add a New Feature](#how-to-add-a-new-feature)

---

## High-Level Architecture

```
┌─────────────────────────────────────────────────────┐
│  cb-listing-anything.php (entry point)              │
│  ↓ defines constants, loads autoloader              │
│  ↓ requires src/Core/Bootstrap.php                  │
├─────────────────────────────────────────────────────┤
│  Bootstrap.php                                      │
│  ↓ plugins_loaded → Framework::bootstrap()          │
│  ↓                → Plugin::instance()->run()       │
├─────────────────────────────────────────────────────┤
│  CrocoDevs Framework                                │
│  ├─ Framework::config() (configuration)             │
│  ├─ ServiceManager    (container)                   │
│  ├─ ServiceProviderManager (lifecycle)              │
│  ├─ Router            (REST route collector)        │
│  ├─ QueryBuilder      (fluent WP_Query)             │
│  └─ Validator         (input validation)            │
├─────────────────────────────────────────────────────┤
│  Plugin Layer (CBListingAnything\)                   │
│  ├─ Providers\    ListingServiceProvider             │
│  ├─ Controllers\  PostType, Taxonomy, MetaBox,       │
│  │                Block, Settings, CategoryImage,    │
│  │                Media, UserDashboard               │
│  ├─ Rest\         SearchController, TermController,  │
│  │                ListingController, AbstractRest     │
│  ├─ Models\       ListingMeta                        │
│  ├─ Helpers\      ListingHelper, ArchiveHelper       │
│  ├─ Hooks\        HookNames (constants)              │
│  ├─ Views\        Admin meta boxes, partials         │
│  └─ blocks\       Gutenberg block render files       │
└─────────────────────────────────────────────────────┘
```

---

## Directory Structure

```
cb-listing-anything/
├── cb-listing-anything.php      # Main plugin file (constants + autoload)
├── composer.json                # Autoloading config
├── config/
│   ├── app.php                  # App name, API prefix, router toggle, providers
│   ├── post_type.php            # Post type slug and supports
│   └── taxonomies.php           # Taxonomy slugs (category, tag)
├── routes/
│   └── api.php                  # REST route definitions (Router)
├── src/
│   ├── Core/
│   │   ├── Bootstrap.php        # Activation/deactivation, plugins_loaded init
│   │   ├── Plugin.php           # Singleton: wires controllers and REST
│   │   └── AbstractController.php
│   ├── Providers/
│   │   └── ListingServiceProvider.php  # Container bindings for all controllers
│   ├── Controllers/
│   │   ├── PostTypeController.php
│   │   ├── TaxonomyController.php
│   │   ├── MetaBoxController.php
│   │   ├── BlockController.php
│   │   ├── SettingsController.php
│   │   ├── CategoryImageController.php
│   │   ├── MediaController.php
│   │   └── UserDashboardController.php
│   ├── Rest/
│   │   ├── AbstractRestController.php  # Base with rest_namespace()
│   │   ├── SearchController.php
│   │   ├── TermController.php
│   │   └── ListingController.php       # Stub for future endpoints
│   ├── Models/
│   │   ├── AbstractModel.php
│   │   └── ListingMeta.php             # Field definitions, sanitize, key mapping
│   ├── Helpers/
│   │   ├── ListingHelper.php           # Shared listing business logic
│   │   └── ArchiveHelper.php           # Archive filter parsing & query building
│   ├── Hooks/
│   │   └── HookNames.php              # Custom do_action / apply_filters names
│   ├── Views/
│   │   ├── admin/
│   │   │   └── meta-box-listing-details.php
│   │   └── partials/
│   │       ├── listing-card.php
│   │       ├── product-card.php
│   │       └── breadcrumb.php
│   └── blocks/                         # Block source with render.php files
│       ├── listing-search/
│       ├── listing-details/
│       ├── listing-cards-slider/
│       ├── related-listings/
│       ├── listings-card/
│       ├── listings-archive/
│       ├── listing-user-dashboard/
│       ├── listing-breadcrumb/
│       └── categories-slider/
├── build/                               # Compiled block assets
├── vendor/
│   ├── autoload.php
│   └── crocodevs/framework/            # The CrocoDevs Framework
└── docs/
    ├── FRAMEWORK.md                     # This file
    ├── REST-API.md                      # Endpoint reference
    └── HOOKS.md                         # Hook documentation
```

---

## Bootstrap Flow

1. **`cb-listing-anything.php`** — Defines version/path constants, requires `vendor/autoload.php`.
2. **`src/Core/Bootstrap.php`** — Required by the main file.
   - Registers activation/deactivation hooks.
   - On `plugins_loaded` (priority 10): calls `Framework::bootstrap()` with the plugin path, then `Plugin::instance()->run()`.
3. **`Framework::bootstrap()`** — Loads configuration, registers service providers, optionally hooks the router.
4. **`Plugin::run()`** — Attaches controllers to WordPress hooks (`init`, `rest_api_init`, `add_meta_boxes`, etc.).

### Constants

| Constant | Value |
|----------|-------|
| `CB_LISTING_ANYTHING_VERSION` | Plugin version string. |
| `CB_LISTING_ANYTHING_PLUGIN_DIR` | Absolute path to plugin root (trailing slash). |
| `CB_LISTING_ANYTHING_PLUGIN_URL` | Public URL to plugin root (trailing slash). |
| `CB_LISTING_ANYTHING_PLUGIN_FILE` | Absolute path to `cb-listing-anything.php`. |

### PSR-4 Autoloading

| Namespace | Directory |
|-----------|-----------|
| `CBListingAnything\` | `src/` |
| `CrocoDevs\` | `vendor/crocodevs/framework/src/` |

---

## Configuration System

The plugin uses two configuration layers:

### 1. Framework Config (`Framework::config()`)

The framework loads `config/app.php` at bootstrap. Access any value with dot-notation:

```php
use CrocoDevs\Framework;

Framework::config( 'app.name' );        // "CB Listing Anything"
Framework::config( 'app.api_prefix' );  // "cb-listing-anything/v1"
Framework::config( 'app.use_router' );  // true
Framework::config( 'app.providers' );   // [ 'CBListingAnything\\Providers\\ListingServiceProvider' ]
```

Global helper: `crocodevs_config( 'app.api_prefix' )`

Plugin's `config/app.php`:

```php
return array(
    'name'        => 'CB Listing Anything',
    'text_domain' => 'cb-listing-anything',
    'api_prefix'  => 'cb-listing-anything/v1',
    'use_router'  => true,
    'providers'   => array(
        'CBListingAnything\\Providers\\ListingServiceProvider',
    ),
);
```

Plugin's `config/post_type.php`:

```php
return array(
    'slug'     => 'cb_listing',
    'supports' => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'revisions', 'author' ),
);
```

Plugin's `config/taxonomies.php`:

```php
return array(
    'category' => 'cb_listing_category',
    'tag'      => 'cb_listing_tag',
);
```

### 2. Plugin Config Files

All plugin-specific configuration lives in the `config/` directory as simple PHP arrays. No static config classes needed.

| File | Keys | Purpose |
|------|------|---------|
| `config/app.php` | `name`, `text_domain`, `api_prefix`, `use_router`, `providers` | Core plugin settings. |
| `config/post_type.php` | `slug`, `supports` | Post type registration data. |
| `config/taxonomies.php` | `category`, `tag` | Taxonomy slugs. |

Use `crocodevs_config()` everywhere instead of hardcoded strings:

```php
$args = [ 'post_type' => crocodevs_config( 'post_type.slug' ) ];
$terms = get_terms( [ 'taxonomy' => crocodevs_config( 'taxonomies.category' ) ] );
```

Listing meta field definitions live in `Models\ListingMeta` (the single source of truth for field structure, sanitization, and type information).

---

## Service Container & Providers

### Container (`CrocoDevs\Container\ServiceManager`)

All controllers and services are registered in the container by `ListingServiceProvider`:

```php
// Resolve a controller
$search = ServiceManager::get( 'cb.listing.rest.search_controller' );

// Create a fresh query builder
$qb = ServiceManager::get( 'cb.listing.query', [ 'post_type' => 'cb_listing' ] );
```

Helper: `crocodevs_resolve( 'cb.listing.settings_controller' )`

### Registered services

| ID | Type | Class |
|----|------|-------|
| `cb.listing.post_type_controller` | singleton | `PostTypeController` |
| `cb.listing.taxonomy_controller` | singleton | `TaxonomyController` |
| `cb.listing.meta_box_controller` | singleton | `MetaBoxController` |
| `cb.listing.block_controller` | singleton | `BlockController` |
| `cb.listing.settings_controller` | singleton | `SettingsController` |
| `cb.listing.category_image_controller` | singleton | `CategoryImageController` |
| `cb.listing.media_controller` | singleton | `MediaController` |
| `cb.listing.rest.search_controller` | singleton | `SearchController` |
| `cb.listing.rest.term_controller` | singleton | `TermController` |
| `cb.listing.query` | factory | `QueryBuilder` |
| `crocodevs.validator` | singleton | `Validator` |

### Provider (`ListingServiceProvider`)

The provider declares `hooks() → ['init' => 10]`, so all bindings are registered at WordPress `init`. Boot runs on `wp_loaded`.

---

## Controllers

Each controller extends `CBListingAnything\Core\AbstractController` and is responsible for hooking into WordPress:

| Controller | Responsibility |
|-----------|---------------|
| `PostTypeController` | Registers `cb_listing` post type on `init`. |
| `TaxonomyController` | Registers category and tag taxonomies on `init`. |
| `MetaBoxController` | Admin meta boxes for listing details. |
| `BlockController` | Registers all Gutenberg blocks from `build/`. |
| `SettingsController` | Admin settings page; exposes `get()`, `currency_symbol()`. |
| `CategoryImageController` | Category image upload in taxonomy admin. |
| `MediaController` | Front-end media upload for dashboard users. |
| `UserDashboardController` | Front-end login, add/edit/delete listing form handling with validation. |

### Adding a new controller

1. Create `src/Controllers/MyController.php` extending `AbstractController`.
2. Add a singleton binding in `ListingServiceProvider::register()`.
3. Hook it in `Plugin::run()` or let the provider's `boot()` attach hooks.

---

## REST API Layer

See `docs/REST-API.md` for endpoint details. Architecture overview:

- **`AbstractRestController`** extends `AbstractController` and provides `$this->rest_namespace()` (from `crocodevs_config('app.api_prefix')`).
- Each REST controller implements `register_routes()`.
- Controllers are registered by `Plugin::run()` on `rest_api_init`.

### Adding a new REST endpoint (traditional)

```php
// src/Rest/MyController.php
namespace CBListingAnything\Rest;

class MyController extends AbstractRestController {
    public function register_routes() {
        register_rest_route( $this->rest_namespace(), '/my-endpoint', [
            'methods'  => 'GET',
            'callback' => [ $this, 'handle' ],
            'permission_callback' => '__return_true',
        ] );
    }

    public function handle( \WP_REST_Request $request ) {
        return new \WP_REST_Response( [ 'status' => 'ok' ], 200 );
    }
}
```

---

## Router Integration

The plugin also defines routes via the CrocoDevs Router in `routes/api.php`. This is an **additional** path for registering REST endpoints — cleaner for new endpoints.

```php
// routes/api.php
use CrocoDevs\Http\Router\Router;

Router::get( '/search', [ SearchController::class, 'search_listings' ], $args );
Router::get( '/categories', [ TermController::class, 'get_categories' ] );
```

Router handlers receive a `CrocoDevs\Http\Request` and can return `CrocoDevs\Http\Response` objects.

### Adding a new route

Add a line to `routes/api.php`:

```php
Router::post( '/listings', [ ListingController::class, 'store' ], [
    'title' => [ 'type' => 'string', 'required' => true ],
] );
```

---

## Query Builder

Used throughout block render files and controllers instead of raw `new WP_Query()`.

```php
use CrocoDevs\Database\QueryBuilder;

$query = QueryBuilder::make()
    ->postType( crocodevs_config( 'post_type.slug' ) )
    ->status( 'publish' )
    ->perPage( 12 )
    ->page( $paged )
    ->whenKeyword( $keyword )
    ->whenTax( crocodevs_config( 'taxonomies.category' ), 'term_id', $category_id )
    ->get();
```

See `vendor/crocodevs/framework/README.md` for the full method reference including `whereMetaBetween()`, `whereAuthorIn()`, `orderByMeta()`, `dateQuery()`, etc.

---

## Validation

The plugin uses `CrocoDevs\Validation\Validator` in two places:

### 1. UserDashboardController (form submission)

The `handle_submission()` method extracts form data into a structured array, validates it against rules, and short-circuits with errors on failure:

```php
$validation = Validator::make( $data, [
    'cb_listing_title'    => 'required|string|max:200',
    'listing_contact_email' => 'nullable|email',
    'listing_website'     => 'nullable|url',
    // ...
] );

if ( $validation->fails() ) {
    // Return errors to the template
}
```

### 2. SearchController (REST endpoint)

Validates query parameters before executing the search:

```php
$validation = Validator::make( $request->get_params(), [
    'keyword'  => 'nullable|string|max:200',
    'category' => 'nullable|integer',
] );

if ( $validation->fails() ) {
    return Response::validationError( $validation->errors() );
}
```

### Adding validation to a new feature

```php
use CrocoDevs\Validation\Validator;

$result = Validator::make( $input, [
    'name'  => 'required|string|max:100',
    'email' => 'required|email',
    'role'  => 'required|in:admin,editor,contributor',
] );

if ( $result->fails() ) {
    // $result->errors() — grouped by field
    // $result->first( 'name' ) — first error for a field
}

$clean = $result->validated(); // Safe data
```

---

## Models & Helpers

### `Models\ListingMeta`

Central place for listing meta field logic:

- `fields()` — All field keys.
- `key( $field )` — Prefixes with `_` for storage.
- `sanitize( $field, $value )` — Type-aware sanitization.
- `is_array_field( $field )` — Whether a field stores arrays.
- `definitions()`, `categories()`, `fields_by_category()` — Structured field data for admin UI.

### `Helpers\ListingHelper`

Reusable listing business logic extracted from block render files:

- `get_listing_meta( $post_id )` — Fetch all listing meta at once.
- `is_open( $meta )` — Check if a listing is currently open.
- `build_full_address( $meta )` — Concatenate address parts.
- `get_preview_post_id()` — Get a sample post ID for block preview.
- `parse_gallery_ids()`, `build_image_list()` — Gallery handling.

### `Helpers\ArchiveHelper`

Centralizes archive page filter parsing and query building:

- `parse_filters()` — Extract filters from `$_GET`.
- `build_query( $filters, $paged )` — Build a `QueryBuilder` from parsed filters.

---

## Config Quick Reference

All configuration is accessed via `crocodevs_config()` with dot-notation:

```php
crocodevs_config( 'app.api_prefix' )       // 'cb-listing-anything/v1'
crocodevs_config( 'app.text_domain' )      // 'cb-listing-anything'
crocodevs_config( 'post_type.slug' )       // 'cb_listing'
crocodevs_config( 'post_type.supports' )   // ['title', 'editor', ...]
crocodevs_config( 'taxonomies.category' )  // 'cb_listing_category'
crocodevs_config( 'taxonomies.tag' )       // 'cb_listing_tag'
```

Listing meta field definitions are in `Models\ListingMeta`:

```php
use CBListingAnything\Models\ListingMeta;

ListingMeta::fields()               // ['listing_price', 'listing_location', ...]
ListingMeta::definitions()          // Associative with label, category, type
ListingMeta::categories()           // ['general' => [...], 'contact' => [...], ...]
ListingMeta::supported_field_types() // ['text' => [...], 'email' => [...], ...]
```

---

## Blocks

Each block lives in `src/blocks/<block-name>/` with a `render.php` server-side render callback. Blocks are built by `@wordpress/scripts` into `build/`.

| Block | Description |
|-------|-------------|
| `listing-search` | AJAX-powered search with keyword + category. |
| `listing-details` | Single listing detail view. |
| `listing-cards-slider` | Horizontal card slider. |
| `related-listings` | Related listings based on category. |
| `listings-card` | Grid of listing cards. |
| `listings-archive` | Full archive with filters and pagination. |
| `listing-user-dashboard` | Front-end login, add/edit/delete listings. |
| `listing-breadcrumb` | Breadcrumb navigation. |
| `categories-slider` | Category cards slider. |

Block render files use:
- `QueryBuilder::make()` instead of raw `WP_Query`.
- `crocodevs_config()` instead of hardcoded slugs.
- `ListingHelper` and `ArchiveHelper` for business logic.
- `crocodevs_view_path()` for view includes.

---

## Views & Partials

Located in `src/Views/`:

- **`admin/`** — Admin-facing templates (meta box forms).
- **`partials/`** — Reusable front-end fragments included by block render files.

Access with: `crocodevs_view_path( 'partials/listing-card' )` → full path to `src/Views/partials/listing-card.php`.

---

## Hooks System

See `docs/HOOKS.md` for the full reference.

Custom hooks are defined as constants in `CBListingAnything\Hooks\HookNames`. The service provider lifecycle uses WordPress hooks: providers declare which hooks trigger their registration via `hooks()`.

---

## How-To: Add a New Feature

### New listing meta field

1. Add the field key and definition to `Models\ListingMeta::definitions()`.
2. Add sanitization logic in `Models\ListingMeta::sanitize()`.
3. The field will automatically appear in admin meta boxes and dashboard forms.
4. If the field needs validation, add a rule to `UserDashboardController::submission_rules()`.

### New REST endpoint

**Option A — Router (preferred for new endpoints):**

```php
// routes/api.php
Router::get( '/listings/featured', [ ListingController::class, 'featured' ] );
```

**Option B — Traditional:**

```php
// src/Rest/ListingController.php
public function register_routes() {
    register_rest_route( $this->rest_namespace(), '/listings/featured', [ ... ] );
}
```

### New taxonomy

1. Add the slug to `config/taxonomies.php`.
2. Register in `TaxonomyController` or create a new controller.
3. Add the controller binding in `ListingServiceProvider`.

### New Gutenberg block

1. Create `src/blocks/<name>/` with `block.json`, `edit.js`, `render.php`.
2. `BlockController` auto-discovers blocks from the `build/` directory.
3. Use `QueryBuilder` and `crocodevs_config()` in `render.php`.

### New service provider

1. Create `src/Providers/MyProvider.php` extending `CrocoDevs\Support\ServiceProvider`.
2. Implement `hooks()`, `register()`, and optionally `boot()`.
3. Add the class name to `config/app.php → providers`.

### New configuration section

1. Create `config/mymodule.php` returning an array.
2. Access with `crocodevs_config( 'mymodule.key' )`.
