# Admin React app (`src/admin`)

WordPress admin UI for the plugin, built with `@wordpress/element` and bundled via `@wordpress/scripts`.

## Entry and shared shell

- **`index.js`** — mounts the app root.
- **`App.js`** — picks the active screen from `window.cbListingAdmin` (or equivalent) and renders it inside **`AdminLayout`**.
- **`components/AdminLayout.js`** — **global shell for every screen**: brand, nav (Listings, Categories, Tags, Settings), **Add New**, and `{ children }` for the body.

Do **not** duplicate header/nav on individual screens; only the body content changes.

## Screens (route bodies)

Thin entry points live under **`screens/`**; some re-export feature modules from **`features/`**.

| Screen       | File | Notes |
| ------------ | ---- | ----- |
| **Listings** | `screens/ListingsScreen.js` | Post list, tabs, search, `AdminDataTable`, listings toolbar |
| **Categories** | `screens/CategoriesScreen.js` | Taxonomy terms + parent terms + image picker |
| **Tags** | `screens/TagsScreen.js` | Taxonomy terms (shared data layer with Categories via `taxonomies/useWpTermCollection.js`) |
| **Settings** | `screens/SettingsScreen.js` | Re-exports `features/settings/SettingsScreen.js` — plugin options, sidebar tabs, forms |

**Settings** does not use `admin-list` table components today; it still uses the same **AdminLayout** shell, **ToastContext**, and global styles.

## Folder layout

| Path | Purpose |
| ---- | ------- |
| `components/` | Cross-cutting UI: **AdminLayout**, **admin-list/** (table, toolbar row, bulk bar, pagination), **ListingThumb**, **TermEditModal**, modals/toasts, taxonomy toolbars |
| `context/` | **ToastContext**, **ConfirmDialogContext** |
| `features/listings/` | Listings-only: `listingTableConfig.js`, category helpers, `getListingCategories` |
| `features/settings/` | Settings-only: **SettingsScreen**, **SettingRow**, **SettingsSidebarIcons** |
| `taxonomies/` | `termTableConfig.js`, **`useWpTermCollection`** (shared Categories + Tags list behavior) |
| `shared/` | **media/** (`thumbFromMedia.js`), **html/** (`stripTags.js`), **icons/** (toolbar SVGs), **components/** (`TableToolbarExtras`) |
| `hooks/` | e.g. `useClickOutside` |
| `utils/` | Small shared helpers (e.g. `termMeta.js`) |
| `screens/` | Screen route components |

## Cross-screen components

| Piece | Used on |
| ----- | ------- |
| **AdminLayout** | All screens (via `App`) |
| **ToastContext** / **AdminToastStack**, **ConfirmDialogContext** / **ConfirmModal** | Listings, taxonomies, settings flows |
| **admin-list/** (**AdminDataTable**, toolbar row, bulk bar, pagination, `useAdminTablePerPage`) | Listings, Categories, Tags |
| **ListingThumb** | Listings, Categories |
| **TermEditModal** / **TermImagePicker** | Categories, Tags |
| **TableToolbarExtras** + **ListingsToolbarExtras** / **TaxonomyTableToolbarExtras** | Listings / taxonomies tables |
| **features/settings/** | Settings body only |

## Adding a new screen

1. Implement the screen component (e.g. under `features/my-feature/` or `screens/MyScreen.js`).
2. Wire it in **`App.js`** (switch/route on the appropriate key).
3. Add a nav link (and **Add New** behavior if needed) in **`AdminLayout.js`**.
4. Ensure any REST or `window.cbListingAdmin` data the screen needs is localized from PHP.

After changes, run `npm run build` from the plugin root so `build/admin/` updates.
