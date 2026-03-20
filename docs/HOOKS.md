# CB Listing Anything — Hooks Reference

This document covers all hook integration points: the plugin's custom hooks, the framework's hook-aware service provider system, WordPress core hooks used by the plugin, and guidance for adding new hooks.

---

## Table of Contents

1. [Custom Plugin Hooks](#custom-plugin-hooks)
2. [Framework: Service Provider Hooks](#framework-service-provider-hooks)
3. [Framework: Router Hook](#framework-router-hook)
4. [WordPress Hooks Used by the Plugin](#wordpress-hooks-used-by-the-plugin)
5. [Adding New Custom Hooks](#adding-new-custom-hooks)
6. [Third-Party Filtering](#third-party-filtering)

---

## Custom Plugin Hooks

Custom hooks are defined as constants in `CBListingAnything\Hooks\HookNames`. Always use the constants (not raw strings) when calling `do_action()` or `apply_filters()`.

### Actions

| Constant | Hook Name | When Fired | Arguments |
|----------|-----------|------------|-----------|
| `HookNames::BEFORE_REGISTER_BLOCKS` | `cb_listing_anything_before_register_blocks` | Before Gutenberg blocks are registered by `BlockController`. | None. |
| `HookNames::LISTING_SAVED` | `cb_listing_anything_listing_saved` | After a listing is successfully created or updated (from the user dashboard or admin). | `int $post_id`, `string $action` (`'add_listing'` or `'edit_listing'`). |

### Usage example

```php
use CBListingAnything\Hooks\HookNames;

// In your extension plugin or theme:
add_action( HookNames::LISTING_SAVED, function ( $post_id, $action ) {
    if ( 'add_listing' === $action ) {
        // Send notification email for new listings
        wp_mail( 'admin@example.com', 'New Listing', "Listing #$post_id submitted." );
    }
}, 10, 2 );

add_action( HookNames::BEFORE_REGISTER_BLOCKS, function () {
    // Register additional blocks before the plugin's blocks
} );
```

---

## Framework: Service Provider Hooks

The CrocoDevs framework uses a hook-aware service provider lifecycle. Each provider can declare which WordPress hooks trigger its registration.

### How it works

1. Providers implement a `hooks()` method returning `['hook_name' => priority]`.
2. `ServiceProviderManager::register()` inspects each provider's hooks and defers `register()` to those WordPress actions.
3. All providers are booted (`boot()` called) on `wp_loaded` at priority 999.

### Current provider hooks

| Provider | Hook | Priority | Purpose |
|----------|------|----------|---------|
| `ListingServiceProvider` | `init` | 10 | Registers all controller singletons and the query builder factory into the service container. |

### Lifecycle timeline

```
plugins_loaded (10)
  └─ Framework::bootstrap()
       ├─ Config loaded
       ├─ ServiceProviderManager::register() called
       │    └─ For each provider, hooks() inspected:
       │         ├─ Empty hooks → register() called immediately
       │         └─ ['init' => 10] → add_action('init', register, 10)
       └─ Router hooked to rest_api_init (if enabled)

init (10)
  └─ ListingServiceProvider::register()
       └─ All controller singletons bound to ServiceManager

wp_loaded (999)
  └─ ServiceProviderManager::bootProviders()
       └─ ListingServiceProvider::boot()

rest_api_init
  └─ Framework::registerRoutes()
       └─ routes/api.php loaded → Router::registerRoutes()
  └─ Plugin::run() wires controllers
       └─ SearchController::register_routes()
       └─ TermController::register_routes()
```

### Creating a deferred provider

```php
namespace MyExtension\Providers;

use CrocoDevs\Support\ServiceProvider;
use CrocoDevs\Container\ServiceManager;

class AnalyticsProvider extends ServiceProvider {

    public function hooks() {
        // Defer until admin_init — only needed in admin context.
        return array( 'admin_init' => 10 );
    }

    public function register() {
        ServiceManager::singleton( 'analytics', function () {
            return new AnalyticsService();
        } );
    }

    public function boot() {
        // Runs on wp_loaded after all providers are registered.
        add_action( 'admin_menu', function () {
            // Add analytics submenu page
        } );
    }
}
```

### Creating an immediate provider

Return an empty array from `hooks()` (or omit the method — the default returns `[]`):

```php
public function hooks() {
    return array(); // Register immediately during bootstrap
}
```

---

## Framework: Router Hook

When `config('app.use_router')` is `true`, the framework hooks into `rest_api_init`:

```
rest_api_init
  └─ Framework::registerRoutes()
       ├─ Router::init()
       ├─ require routes/api.php    ← your route definitions
       └─ Router::registerRoutes()  ← calls register_rest_route() for each
```

The router uses the `app.api_prefix` config value as the REST namespace for all routes.

---

## WordPress Hooks Used by the Plugin

The plugin hooks into standard WordPress actions and filters throughout its controllers. This is a summary of the major ones:

### Actions

| Hook | Controller | Purpose |
|------|-----------|---------|
| `plugins_loaded` | `Bootstrap.php` | Initialize plugin, bootstrap framework. |
| `init` | `PostTypeController` | Register `cb_listing` post type. |
| `init` | `TaxonomyController` | Register category and tag taxonomies. |
| `init` | `BlockController` | Register Gutenberg blocks. |
| `add_meta_boxes` | `MetaBoxController` | Add listing detail meta boxes. |
| `save_post` | `MetaBoxController` | Save meta box data. |
| `rest_api_init` | `Plugin::run()` | Register REST controllers' routes. |
| `rest_api_init` | `Framework` | Load router routes (if enabled). |
| `admin_menu` | `SettingsController` | Add plugin settings page. |
| `admin_init` | `SettingsController` | Register settings fields. |
| `admin_enqueue_scripts` | `CategoryImageController` | Enqueue media uploader for category image. |
| `wp_loaded` | `ServiceProviderManager` | Boot all registered service providers. |

### Filters

| Hook | Where | Purpose |
|------|-------|---------|
| `show_admin_bar` | `Bootstrap.php` | Hide admin bar for `cb_listing_contributor` role. |
| `the_content` | Block render files | Apply content filters to listing description. |

---

## Adding New Custom Hooks

### Step 1: Define the constant

```php
// src/Hooks/HookNames.php
public const LISTING_BEFORE_DELETE = 'cb_listing_anything_listing_before_delete';
public const LISTING_META_SANITIZED = 'cb_listing_anything_listing_meta_sanitized';
```

### Step 2: Fire the hook

```php
use CBListingAnything\Hooks\HookNames;

// Action — notify before deletion
do_action( HookNames::LISTING_BEFORE_DELETE, $post_id );

// Filter — allow modification of sanitized meta
$meta = apply_filters( HookNames::LISTING_META_SANITIZED, $meta, $post_id );
```

### Step 3: Document it

Add the hook to this file with its name, when it fires, and what arguments it passes.

### Naming convention

All custom hooks should use the prefix `cb_listing_anything_` to avoid collisions. Use the constant from `HookNames` — never hardcode the string directly.

---

## Third-Party Filtering

### Content filtering

Block render files (e.g., listing details) may use `apply_filters( 'the_content', $post->post_content )` to render post content. This is a WordPress core filter — third-party plugins (e.g., shortcode processors, content formatters) will apply their filters as expected.

### Extension plugin pattern

To build an extension plugin that hooks into CB Listing Anything:

```php
// my-listing-extension/my-listing-extension.php
use CBListingAnything\Hooks\HookNames;

add_action( 'plugins_loaded', function () {
    // Wait until after CB Listing Anything is loaded.
    if ( ! class_exists( 'CBListingAnything\\Core\\Plugin' ) ) {
        return;
    }

    // Hook into listing save
    add_action( HookNames::LISTING_SAVED, 'my_extension_on_listing_saved', 10, 2 );

    // Hook into REST routes via the router
    add_action( 'rest_api_init', function () {
        \CrocoDevs\Http\Router\Router::get(
            '/listings/featured',
            [ MyExtension\FeaturedController::class, 'index' ]
        );
    } );
}, 20 ); // Priority 20 → after CB Listing Anything's priority 10
```

### Registering additional service providers

Extension plugins can register their own providers by adding them to the config or calling `ServiceProviderManager` directly:

```php
use CrocoDevs\Support\ServiceProviderManager;

add_action( 'plugins_loaded', function () {
    ServiceProviderManager::register( [
        'MyExtension\\Providers\\ExtensionServiceProvider',
    ] );
}, 15 );
```
