# CB Listing Anything – Framework

## Architecture overview

- **Bootstrap** (`cb-listing-anything.php`): Defines constants, loads Composer autoload, registers activation/deactivation, and runs `cb_listing_anything_init()` on `plugins_loaded`. Use `cb_listing_anything()` to get the plugin instance after `plugins_loaded`.
- **Core** (`src/Core/`): `Plugin` singleton wires and runs all controllers and REST; `AbstractController` is the base for Controllers; optional `Container` for service registry.
- **Config** (`src/Config/`): Static definitions only—post type labels/args (`PostType`), taxonomy labels/args (`Taxonomies`), listing meta field keys (`ListingMeta`). No WordPress API calls; used by Controllers and Models.
- **Controllers** (`src/Controllers/`): WordPress hooks and registration (post type, taxonomies, meta boxes, blocks, settings, category image). Each extends `Core\AbstractController` and uses Config where applicable.
- **Rest** (`src/Rest/`): REST API endpoints—`SearchController` (/search), `TermController` (/categories). Registered on `rest_api_init` by Plugin.
- **Models** (`src/Models/`): Business logic and data access. `ListingMeta` extends `AbstractModel` and uses `Config\ListingMeta` for field keys; provides `key()`, `sanitize()`, `is_array_field()`, `working_days_options()`.
- **Hooks** (`src/Hooks/`): Optional. `HookNames` constants for custom `do_action` / `apply_filters` names.
- **Views** (`src/Views/`): Admin meta box and front-end partials (e.g. `listing-card.php`). No namespace; included by controllers.
- **Blocks** (`src/blocks/`, `build/`): Block source and build output. Unchanged by framework; registered by BlockController.

## Bootstrap and naming

- Main plugin file: `cb-listing-anything.php`. Accessor: `cb_listing_anything()` returns `Plugin::instance()` (call after `plugins_loaded`).
- PSR-4: `CBListingAnything\` → `src/`. Subnamespaces: `Core`, `Config`, `Controllers`, `Rest`, `Models`, `Hooks`.
- Constants: `CB_LISTING_ANYTHING_VERSION`, `CB_LISTING_ANYTHING_PLUGIN_DIR`, `CB_LISTING_ANYTHING_PLUGIN_URL`, `CB_LISTING_ANYTHING_PLUGIN_FILE`.

## Where to add new code

- New **post type or taxonomy**: Add or extend a class in `Config`, then use it in the corresponding Controller.
- New **REST endpoint**: Add a controller in `Rest\`, implement `register_routes()`, and register it in `Plugin::run()` with `rest_api_init`.
- New **admin UI or hook**: New or existing Controller in `Controllers\`, extending `AbstractController`.
- New **listing meta field**: Add the key to `Config\ListingMeta::field_keys()` and handle sanitize/behavior in `Models\ListingMeta`.
- Custom **hooks**: Define names in `Hooks\HookNames` and document in `docs/HOOKS.md`.
