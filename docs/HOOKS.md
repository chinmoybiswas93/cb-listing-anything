# CB Listing Anything – Hooks

## Custom hooks (plugin-defined)

The plugin currently uses WordPress core hooks for most behavior. Custom hook names are defined in `CBListingAnything\Hooks\HookNames` for future use.

| Constant | Value | Purpose |
|----------|--------|--------|
| `HookNames::BEFORE_REGISTER_BLOCKS` | `cb_listing_anything_before_register_blocks` | Fired before block registration (example). |
| `HookNames::LISTING_SAVED` | `cb_listing_anything_listing_saved` | Fired after a listing is saved (example). |

When you add a custom `do_action()` or `apply_filters()` in the plugin, add its name to `HookNames` and document it here with arguments and purpose.

## WordPress hooks used by the plugin

Controllers and Rest classes hook into standard WordPress actions and filters (e.g. `init`, `add_meta_boxes`, `save_post`, `rest_api_init`, `admin_menu`). These are not listed here; see the respective controller files.

## Third-party filtering of output

- In block render (e.g. listing details content), the plugin may use `apply_filters( 'the_content', ... )` for post content. That is a core WordPress filter, not a custom one.
