# CB Listing Anything – REST API

Base namespace: **`cb-listing-anything/v1`**

All endpoints use `permission_callback: __return_true` (public read).

---

## GET /search

Search listings by keyword and/or category.

**Query parameters**

| Param     | Type    | Default | Description           |
|-----------|---------|--------|-----------------------|
| `keyword` | string  | `''`   | Search keyword.       |
| `category`| integer | `0`    | Listing category term ID (0 = all). |

**Response:** `200 OK` — JSON array of listing objects.

**Example:** `GET /wp-json/cb-listing-anything/v1/search?keyword=coffee&category=5`

**Example response body:**

```json
[
  {
    "id": 42,
    "title": "Coffee Shop Downtown",
    "url": "https://example.com/listings/coffee-shop-downtown/",
    "thumbnail": "https://example.com/wp-content/uploads/...",
    "location": "New York, NY",
    "price": "$2.50",
    "category": "Restaurant"
  }
]
```

If both `keyword` and `category` are empty, the response is an empty array `[]`.

---

## GET /categories

List all listing categories (terms of taxonomy `cb_listing_category`).

**Query parameters:** None.

**Response:** `200 OK` — JSON array of term objects.

**Example:** `GET /wp-json/cb-listing-anything/v1/categories`

**Example response body:**

```json
[
  { "id": 1, "name": "Restaurant", "parent": 0, "count": 8 },
  { "id": 2, "name": "Retail", "parent": 0, "count": 3 }
]
```

---

## Optional endpoints (stub)

- **GET /listings** — Optional future endpoint for listing list with pagination. Not yet implemented; see `Rest\ListingController`.
- **GET /tags** — Optional future endpoint for listing tags; same shape as categories. Not yet implemented.
