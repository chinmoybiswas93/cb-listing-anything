# CrocoDevs Framework

A lightweight, WordForge-inspired PHP framework for building professional WordPress plugins. It provides a service container, configuration system, HTTP router, fluent query builder, validation engine, and hook-aware service providers — all without requiring a heavy external dependency.

**Namespace:** `CrocoDevs\`
**Minimum PHP:** 7.2+
**Autoloading:** PSR-4 via Composer

---

## Table of Contents

1. [Quick Start](#quick-start)
2. [Directory Structure](#directory-structure)
3. [Bootstrap](#bootstrap)
4. [Configuration](#configuration)
5. [Service Container](#service-container)
6. [Service Providers](#service-providers)
7. [HTTP Router](#http-router)
8. [Request & Response](#request--response)
9. [Query Builder](#query-builder)
10. [Validation](#validation)
11. [Global Helpers](#global-helpers)
12. [Extending the Framework](#extending-the-framework)

---

## Quick Start

In your main plugin file, require the Composer autoloader and call `Framework::bootstrap()`:

```php
require_once __DIR__ . '/vendor/autoload.php';

use CrocoDevs\Framework;

Framework::bootstrap( __DIR__ );
```

The framework will:

1. Load configuration from `vendor/crocodevs/framework/config/` (defaults) merged with your plugin's `config/` directory (overrides).
2. Register and boot service providers listed in `config/app.php → providers`.
3. If `config/app.php → use_router` is `true`, hook `rest_api_init` to load your `routes/api.php` file.

---

## Directory Structure

```
vendor/crocodevs/framework/
├── config/
│   └── app.php                  # Framework defaults
├── src/
│   ├── Framework.php            # Entry point — bootstrap, paths, config proxy
│   ├── Config/
│   │   └── Config.php           # Dot-notation configuration store
│   ├── Container/
│   │   └── ServiceManager.php   # Lightweight service container
│   ├── Database/
│   │   └── QueryBuilder.php     # Fluent WP_Query builder
│   ├── Http/
│   │   ├── Request.php          # WP_REST_Request wrapper
│   │   ├── Response.php         # Static response factory
│   │   └── Router/
│   │       └── Router.php       # REST API route collector
│   ├── Support/
│   │   ├── helpers.php          # Global helper functions
│   │   ├── ServiceProvider.php  # Abstract base provider
│   │   └── ServiceProviderManager.php  # Provider lifecycle manager
│   └── Validation/
│       ├── Validator.php        # Rule-based data validator
│       └── ValidationResult.php # Validation outcome container
└── composer.json
```

---

## Bootstrap

### `Framework::bootstrap( string $pluginPath, array $providers = [] )`

Call once from your main plugin file. The method is idempotent — subsequent calls are no-ops.

| Parameter      | Type     | Description |
|---------------|----------|-------------|
| `$pluginPath` | `string` | Absolute path to the plugin root directory. |
| `$providers`  | `array`  | Optional. Provider class names. If empty, read from `config('app.providers')`. |

### Path helpers

```php
Framework::appPath( 'src/Views/admin/form.php' );
// → /absolute/path/to/plugin/src/Views/admin/form.php

Framework::viewPath( 'admin.form' );
// → /absolute/path/to/plugin/src/Views/admin/form.php

Framework::assetUrl( 'build/css/style.css' );
// → https://example.com/wp-content/plugins/my-plugin/build/css/style.css
```

---

## Configuration

### How it works

The framework loads every `*.php` file from two directories:

1. **Framework defaults** — `vendor/crocodevs/framework/config/`
2. **Plugin overrides** — `<plugin-root>/config/`

Each file returns a PHP array. The filename (without `.php`) becomes the top-level key. Plugin arrays are merged over framework defaults with `array_replace_recursive`.

### Default `config/app.php`

```php
return array(
    'name'       => 'CrocoDevs App',
    'api_prefix' => 'cb-listing-anything/v1',
    'use_router' => false,
    'providers'  => array(),
);
```

### Plugin override example (`config/app.php`)

```php
return array(
    'name'       => 'My Plugin',
    'api_prefix' => 'my-plugin/v1',
    'use_router' => true,
    'providers'  => array(
        'MyPlugin\\Providers\\AppServiceProvider',
    ),
);
```

### Reading config values

Use dot-notation to drill into nested arrays:

```php
use CrocoDevs\Config\Config;

Config::get( 'app.name' );                  // "My Plugin"
Config::get( 'app.api_prefix' );            // "my-plugin/v1"
Config::get( 'app.custom_key', 'default' ); // "default" if not set
```

Or use the global helper:

```php
crocodevs_config( 'app.use_router' ); // true
```

### Adding custom config files

Create any `config/<name>.php` returning an array. Access values as `<name>.<key>`:

```php
// config/mail.php
return array( 'from' => 'hello@example.com', 'driver' => 'wp_mail' );

// Usage
crocodevs_config( 'mail.from' ); // "hello@example.com"
```

---

## Service Container

`CrocoDevs\Container\ServiceManager` is a static service registry supporting three binding types.

### Singleton (resolved once, then cached)

```php
use CrocoDevs\Container\ServiceManager;

ServiceManager::singleton( 'mailer', function () {
    return new Mailer( crocodevs_config( 'mail.driver' ) );
} );

$mailer = ServiceManager::get( 'mailer' ); // Same instance every time
```

### Factory (new instance each call)

```php
ServiceManager::register( 'query', function ( array $args = [] ) {
    return QueryBuilder::make( $args );
} );

$q1 = ServiceManager::get( 'query', [ 'post_type' => 'page' ] );
$q2 = ServiceManager::get( 'query' ); // Different instance
```

### Direct instance

```php
ServiceManager::instance( 'plugin.version', '2.1.0' );

ServiceManager::get( 'plugin.version' ); // "2.1.0"
```

### Checking existence

```php
ServiceManager::has( 'mailer' ); // true
```

### Global helper

```php
crocodevs_resolve( 'mailer' ); // Proxy for ServiceManager::get()
```

---

## Service Providers

Service providers organize registration logic into cohesive units. Each provider extends `CrocoDevs\Support\ServiceProvider`.

### Creating a provider

```php
namespace MyPlugin\Providers;

use CrocoDevs\Container\ServiceManager;
use CrocoDevs\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider {

    public function hooks() {
        // Defer registration until the 'init' action at priority 10.
        return array( 'init' => 10 );
    }

    public function register() {
        ServiceManager::singleton( 'my.service', function () {
            return new MyService();
        } );
    }

    public function boot() {
        // Runs after ALL providers have been registered (on wp_loaded).
        add_action( 'template_redirect', [ $this, 'handle_redirects' ] );
    }
}
```

### Provider lifecycle

| Phase | When | What happens |
|-------|------|-------------|
| **Queued** | `ServiceProviderManager::register()` is called | Class names stored; `hooks()` inspected. |
| **Registered** | The WordPress hook(s) from `hooks()` fire | `register()` is called; bindings are added to the container. |
| **Booted** | `wp_loaded` action (priority 999) | `boot()` is called on every registered provider. |

If `hooks()` returns an empty array, the provider is registered **immediately** (no deferral).

### Registering providers

List them in `config/app.php → providers`, or pass them directly to `Framework::bootstrap()`:

```php
Framework::bootstrap( __DIR__, [
    'MyPlugin\\Providers\\AppServiceProvider',
    'MyPlugin\\Providers\\RestServiceProvider',
] );
```

---

## HTTP Router

The router provides a clean, Laravel-style API for defining REST endpoints. It is an optional layer — you can still use `register_rest_route()` directly.

### Enabling the router

Set `use_router` to `true` in `config/app.php`. Then create a `routes/api.php` file in your plugin root:

```php
// routes/api.php
use CrocoDevs\Http\Router\Router;
use MyPlugin\Rest\ProductController;

Router::get( '/products', [ ProductController::class, 'index' ] );

Router::get( '/products/(?P<id>\d+)', [ ProductController::class, 'show' ], [
    'id' => [ 'type' => 'integer', 'required' => true ],
] );

Router::post( '/products', [ ProductController::class, 'store' ] );

Router::put( '/products/(?P<id>\d+)', [ ProductController::class, 'update' ] );

Router::delete( '/products/(?P<id>\d+)', [ ProductController::class, 'destroy' ] );
```

### How it works

1. On `rest_api_init`, `Framework::registerRoutes()` requires `routes/api.php`.
2. Each `Router::get()` / `post()` / etc. stores a route definition.
3. `Router::registerRoutes()` calls `register_rest_route()` for each definition under the `app.api_prefix` namespace.
4. Handlers are wrapped so they receive a `CrocoDevs\Http\Request` instead of `WP_REST_Request`.

### Route handler signatures

Handlers can be either a `[ClassName, 'method']` array or a closure:

```php
// Class-based (auto-instantiated)
Router::get( '/stats', [ StatsController::class, 'index' ] );

// Closure
Router::get( '/ping', function ( \CrocoDevs\Http\Request $request ) {
    return \CrocoDevs\Http\Response::success( [ 'pong' => true ] );
} );
```

---

## Request & Response

### `CrocoDevs\Http\Request`

A thin wrapper around `WP_REST_Request` passed to router-handled endpoints.

| Method | Returns | Description |
|--------|---------|-------------|
| `get( $key, $default )` | `mixed` | Single parameter from URL, query, or body. |
| `all()` | `array` | All merged parameters. |
| `only( ['key1', 'key2'] )` | `array` | Subset of parameters. |
| `has( $key )` | `bool` | Whether a parameter is present. |
| `method()` | `string` | HTTP method (`GET`, `POST`, etc.). |
| `wpRequest()` | `WP_REST_Request` | Access the underlying WP object. |

### `CrocoDevs\Http\Response`

A static factory for building `WP_REST_Response` objects.

| Method | Status | Description |
|--------|--------|-------------|
| `Response::success( $data )` | 200 | Standard success. |
| `Response::created( $data )` | 201 | Resource created. |
| `Response::json( $data, $status, $headers )` | custom | Full control over status and headers. |
| `Response::error( $message, $status )` | custom | Generic error. |
| `Response::notFound( $message )` | 404 | Not found. |
| `Response::validationError( $errors )` | 422 | Validation failure with field-level errors. |

---

## Query Builder

`CrocoDevs\Database\QueryBuilder` is a fluent interface over `WP_Query`. It does **not** replace `WP_Query` — it builds the arguments array and executes it for you.

### Basic usage

```php
use CrocoDevs\Database\QueryBuilder;

$query = QueryBuilder::make()
    ->postType( 'product' )
    ->status( 'publish' )
    ->perPage( 12 )
    ->page( 2 )
    ->orderBy( 'date', 'DESC' )
    ->get();

if ( $query->have_posts() ) {
    while ( $query->have_posts() ) {
        $query->the_post();
        // ...
    }
    wp_reset_postdata();
}
```

### Complete method reference

| Method | WP_Query arg | Description |
|--------|-------------|-------------|
| `postType( $type )` | `post_type` | Post type(s) to query. |
| `status( $status )` | `post_status` | Post status(es). |
| `perPage( $n )` | `posts_per_page` | Results per page. |
| `page( $n )` | `paged` | Current page (min 1). |
| `whenKeyword( $kw )` | `s` | Search keyword (skipped if empty). |
| `whenTax( $tax, $field, $terms )` | `tax_query` | Taxonomy filter (skipped if terms empty). |
| `whereMeta( $key, $value, $compare, $type )` | `meta_query` | Single meta comparison. |
| `whereMetaBetween( $key, $min, $max, $type )` | `meta_query` | BETWEEN range (price, dates). |
| `orderBy( $orderby, $order )` | `orderby` / `order` | Standard ordering. |
| `orderByMeta( $key, $order, $type )` | `meta_key` / `orderby` | Order by meta value. |
| `author( $id )` | `author` | Single author ID. |
| `whereAuthorIn( $ids )` | `author__in` | Multiple author IDs. |
| `whereAuthorNotIn( $ids )` | `author__not_in` | Exclude authors. |
| `dateQuery( $args )` | `date_query` | WP Date Query array. |
| `include( $ids )` | `post__in` | Only these post IDs. |
| `exclude( $ids )` | `post__not_in` | Exclude these post IDs. |
| `fields( $fields )` | `fields` | Return format (`'ids'`, `'id=>parent'`). |
| `noFoundRows( $bool )` | `no_found_rows` | Skip counting for pagination. |
| `mergeArgs( $args )` | *(any)* | Merge arbitrary WP_Query args. |
| `toArgs()` | — | Get the raw args array without executing. |
| `get()` | — | Execute and return a `WP_Query` instance. |

### Chaining example with validation

```php
$validated = Validator::make( $request->all(), [
    'keyword'   => 'nullable|string|max:200',
    'category'  => 'nullable|integer',
    'price_min' => 'nullable|numeric',
    'price_max' => 'nullable|numeric',
] )->validated();

$query = QueryBuilder::make()
    ->postType( 'product' )
    ->status( 'publish' )
    ->perPage( 20 )
    ->whenKeyword( $validated['keyword'] ?? '' )
    ->whenTax( 'product_category', 'term_id', $validated['category'] ?? 0 );

if ( ! empty( $validated['price_min'] ) && ! empty( $validated['price_max'] ) ) {
    $query->whereMetaBetween( '_price', $validated['price_min'], $validated['price_max'] );
}

$results = $query->get();
```

---

## Validation

### Basic usage

```php
use CrocoDevs\Validation\Validator;

$result = Validator::make( $data, [
    'title'    => 'required|string|max:200',
    'email'    => 'required|email',
    'price'    => 'nullable|numeric|min:0',
    'category' => 'required|integer',
    'status'   => 'required|in:draft,publish,pending',
    'tags'     => 'nullable|array',
    'website'  => 'nullable|url',
] );

if ( $result->fails() ) {
    // $result->errors() → ['title' => ['The title field is required.'], ...]
    // $result->first( 'title' ) → 'The title field is required.'
    return;
}

$clean = $result->validated(); // Only fields that had rules and passed
```

### Supported rules

| Rule | Description |
|------|-------------|
| `required` | Field must be present and non-empty. |
| `nullable` | Field may be null/empty — skip remaining rules if so. |
| `string` | Must be a string. |
| `email` | Must pass `FILTER_VALIDATE_EMAIL`. |
| `url` | Must pass `FILTER_VALIDATE_URL`. |
| `numeric` | Must be numeric (`is_numeric`). |
| `integer` | Must pass `FILTER_VALIDATE_INT`. |
| `boolean` | Must be bool-like (true/false/0/1/"0"/"1"). |
| `array` | Must be a PHP array. |
| `min:n` | Minimum string length or numeric value. |
| `max:n` | Maximum string length or numeric value. |
| `in:a,b,c` | Value must be one of the listed options. |

Rules are pipe-delimited: `'required|string|max:200'`

### `ValidationResult` API

| Method | Returns | Description |
|--------|---------|-------------|
| `fails()` | `bool` | `true` if any rule failed. |
| `passes()` | `bool` | `true` if all rules passed. |
| `errors()` | `array<string, string[]>` | All errors grouped by field. |
| `first( $field )` | `string\|null` | First error for a specific field. |
| `validated()` | `array` | Clean data — only fields with rules that passed. |

### Global helper

```php
$result = crocodevs_validate( $data, $rules );
```

### Usage in REST endpoints

```php
use CrocoDevs\Http\Response;
use CrocoDevs\Validation\Validator;

public function store( WP_REST_Request $request ) {
    $result = Validator::make( $request->get_params(), [
        'title' => 'required|string|max:200',
        'price' => 'required|numeric|min:0',
    ] );

    if ( $result->fails() ) {
        return Response::validationError( $result->errors() );
    }

    $data = $result->validated();
    // ... save and return Response::created( $item )
}
```

---

## Global Helpers

Loaded automatically via Composer's `files` autoload. Available everywhere after autoload.

| Function | Description |
|----------|-------------|
| `crocodevs_app_path( $rel )` | Absolute path from plugin root. |
| `crocodevs_view_path( $view )` | Absolute path to a view template (`'admin.form'` → `src/Views/admin/form.php`). |
| `crocodevs_asset_url( $path )` | Public URL for a plugin asset. |
| `crocodevs_resolve( $id, ...$args )` | Resolve a service from `ServiceManager`. |
| `crocodevs_config( $key, $default )` | Read a configuration value (dot-notation). |
| `crocodevs_validate( $data, $rules )` | Shorthand for `Validator::make()`. |

---

## Extending the Framework

### Adding a new subsystem

1. Create your classes under `src/<Subsystem>/` (e.g., `src/Cache/CacheManager.php`).
2. PSR-4 autoloading handles the rest — namespace `CrocoDevs\Cache\CacheManager`.
3. Optionally add a global helper in `src/Support/helpers.php`.
4. If the subsystem needs bootstrapping, add a service provider or hook it in `Framework::bootstrap()`.

### Adding new validation rules

Extend the `Validator` class or add a `validate<RuleName>` static method:

```php
// In your plugin, subclass or add to Validator:
protected static function validatePhone( $value ) {
    return (bool) preg_match( '/^\+?[\d\s\-()]{7,20}$/', $value );
}
```

### Adding new QueryBuilder methods

Add a public method that sets the appropriate `WP_Query` argument:

```php
public function whereMetaExists( $key ) {
    $this->args['meta_query'][] = array(
        'key'     => $key,
        'compare' => 'EXISTS',
    );
    return $this;
}
```

### Creating config files for new modules

Drop a PHP file in `vendor/crocodevs/framework/config/` for defaults, and let plugins override by placing a file with the same name in their `config/` directory.
