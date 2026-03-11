# CB Listing Anything — REST API Reference

Base namespace: **`cb-listing-anything/v1`** (configured in `config/app.php → api_prefix`)

All endpoints are public read (`permission_callback: __return_true`) unless otherwise noted.

---

## Table of Contents

1. [Overview](#overview)
2. [Endpoints](#endpoints)
   - [GET /search](#get-search)
   - [GET /categories](#get-categories)
3. [Validation Errors](#validation-errors)
4. [Defining Routes](#defining-routes)
5. [Adding New Endpoints](#adding-new-endpoints)
6. [Future / Stub Endpoints](#future--stub-endpoints)

---

## Overview

The plugin exposes REST API endpoints in two ways:

1. **Traditional** — Controllers extend `AbstractRestController` and call `register_rest_route()` in their `register_routes()` method. These are wired by `Plugin::run()` on `rest_api_init`.

2. **Router** — Routes defined in `routes/api.php` using `CrocoDevs\Http\Router\Router`. The framework registers these automatically on `rest_api_init` when `config('app.use_router')` is `true`.

Both approaches register routes under the same namespace (`cb-listing-anything/v1`). The router is the preferred approach for new endpoints.

### Base URL

```
https://your-site.com/wp-json/cb-listing-anything/v1/
```

---

## Endpoints

### GET /search

Search listings by keyword and/or category. Input is validated before querying.

**URL:** `GET /wp-json/cb-listing-anything/v1/search`

**Query parameters:**

| Param      | Type    | Default | Rules                    | Description                    |
|------------|---------|---------|--------------------------|--------------------------------|
| `keyword`  | string  | `''`    | nullable, string, max:200 | Free-text search keyword.      |
| `category` | integer | `0`     | nullable, integer         | Listing category term ID.      |

**Success response:** `200 OK`

```json
[
  {
    "id": 42,
    "title": "Coffee Shop Downtown",
    "url": "https://example.com/listings/coffee-shop-downtown/",
    "thumbnail": "https://example.com/wp-content/uploads/thumb.jpg",
    "location": "New York, NY",
    "price": "$2.50",
    "category": "Restaurant"
  }
]
```

If both `keyword` and `category` are empty/zero, the response is an empty array `[]`.

**Validation error response:** `422 Unprocessable Entity`

```json
{
  "errors": {
    "keyword": ["The keyword must not exceed 200."],
    "category": ["The category must be an integer."]
  }
}
```

**Example:**

```
GET /wp-json/cb-listing-anything/v1/search?keyword=coffee&category=5
```

---

### GET /categories

List all listing categories (taxonomy: `cb_listing_category`).

**URL:** `GET /wp-json/cb-listing-anything/v1/categories`

**Query parameters:** None.

**Success response:** `200 OK`

```json
[
  { "id": 1, "name": "Restaurant", "parent": 0, "count": 8 },
  { "id": 2, "name": "Retail", "parent": 0, "count": 3 },
  { "id": 5, "name": "Cafe", "parent": 1, "count": 2 }
]
```

Returns an empty array `[]` if no categories exist or an error occurs.

---

## Validation Errors

Endpoints that use the CrocoDevs Validator return structured error responses when input is invalid.

**HTTP Status:** `422 Unprocessable Entity`

**Body format:**

```json
{
  "errors": {
    "field_name": [
      "The field name field is required.",
      "The field name must be a string."
    ],
    "another_field": [
      "The another field must be an integer."
    ]
  }
}
```

Errors are grouped by field name. Each field may have multiple error messages (one per failed rule).

### Supported validation rules

| Rule        | Description                                      |
|-------------|--------------------------------------------------|
| `required`  | Field must be present and non-empty.              |
| `nullable`  | Field may be null/empty; skip remaining rules.    |
| `string`    | Must be a string.                                 |
| `email`     | Must be a valid email address.                    |
| `url`       | Must be a valid URL.                              |
| `numeric`   | Must be numeric.                                  |
| `integer`   | Must be an integer.                               |
| `boolean`   | Must be boolean-like.                             |
| `array`     | Must be an array.                                 |
| `min:n`     | Minimum string length or numeric value.           |
| `max:n`     | Maximum string length or numeric value.           |
| `in:a,b,c`  | Must be one of the listed values.                 |

---

## Defining Routes

### Routes file: `routes/api.php`

```php
use CrocoDevs\Http\Router\Router;
use CBListingAnything\Rest\SearchController;
use CBListingAnything\Rest\TermController;

Router::get( '/search', [ SearchController::class, 'search_listings' ], [
    'keyword'  => [
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default'           => '',
    ],
    'category' => [
        'type'              => 'integer',
        'sanitize_callback' => 'absint',
        'default'           => 0,
    ],
] );

Router::get( '/categories', [ TermController::class, 'get_categories' ] );
```

### Available HTTP methods

```php
Router::get( $path, $handler, $args );
Router::post( $path, $handler, $args );
Router::put( $path, $handler, $args );
Router::delete( $path, $handler, $args );
```

### Handler signatures

Router-defined handlers receive a `CrocoDevs\Http\Request` instead of `WP_REST_Request`:

```php
use CrocoDevs\Http\Request;
use CrocoDevs\Http\Response;

public function index( Request $request ) {
    $keyword = $request->get( 'keyword', '' );
    $all     = $request->all();
    $subset  = $request->only( ['keyword', 'category'] );

    return Response::success( $data );
}
```

### Response helpers

```php
Response::success( $data );              // 200
Response::created( $data );              // 201
Response::json( $data, 200, $headers );  // Custom status + headers
Response::error( 'Bad request', 400 );   // Error with message
Response::notFound( 'Not found' );       // 404
Response::validationError( $errors );    // 422 with field errors
```

---

## Adding New Endpoints

### Step 1: Create a controller

```php
// src/Rest/ReviewController.php
namespace CBListingAnything\Rest;

use CrocoDevs\Http\Request;
use CrocoDevs\Http\Response;
use CrocoDevs\Validation\Validator;

class ReviewController {

    public function index( Request $request ) {
        $listing_id = $request->get( 'listing_id' );
        // ... fetch reviews
        return Response::success( $reviews );
    }

    public function store( Request $request ) {
        $result = Validator::make( $request->all(), [
            'listing_id' => 'required|integer',
            'rating'     => 'required|integer|min:1|max:5',
            'comment'    => 'required|string|max:1000',
        ] );

        if ( $result->fails() ) {
            return Response::validationError( $result->errors() );
        }

        $data = $result->validated();
        // ... save review
        return Response::created( $review );
    }
}
```

### Step 2: Add routes

```php
// routes/api.php
use CBListingAnything\Rest\ReviewController;

Router::get( '/reviews', [ ReviewController::class, 'index' ], [
    'listing_id' => [ 'type' => 'integer', 'required' => true ],
] );

Router::post( '/reviews', [ ReviewController::class, 'store' ] );
```

### Step 3 (optional): Register in the service provider

If you want the controller in the container:

```php
// In ListingServiceProvider::register()
ServiceManager::singleton( 'cb.listing.rest.review_controller', function () {
    return new ReviewController();
} );
```

---

## Future / Stub Endpoints

These endpoints are planned but not yet implemented:

| Method | Path | Description | Status |
|--------|------|-------------|--------|
| `GET` | `/listings` | Paginated listing list with filters. | Stub in `ListingController`. |
| `POST` | `/listings` | Create a listing via REST. | Planned. |
| `PUT` | `/listings/{id}` | Update a listing via REST. | Planned. |
| `DELETE` | `/listings/{id}` | Delete/trash a listing via REST. | Planned. |
| `GET` | `/tags` | List all listing tags. | Planned. |
| `GET` | `/reviews` | List reviews for a listing. | Planned. |
| `POST` | `/reviews` | Submit a review. | Planned. |

When implementing these, use the Router + Validator pattern shown above.
