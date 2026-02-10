# Listing Items Plugin

A standard WordPress plugin for managing listing items with custom post type, categories, tags, and custom fields.

## Features

- **Custom Post Type**: Creates a "Listing" post type for managing listing items
- **Featured Image Support**: Built-in support for featured images
- **Categories**: Hierarchical taxonomy for organizing listings by category
- **Tags**: Non-hierarchical taxonomy for tagging listings
- **Custom Fields**: Additional fields for listing details including:
  - Price
  - Location
  - Address (Street, City, State, ZIP, Country)
  - Contact Information (Email, Phone)
  - Website URL

## Installation

1. Upload the `listing-items` folder to `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. The plugin will automatically create the custom post type and taxonomies

## Usage

### Creating a Listing

1. Navigate to **Listings** → **Add New** in your WordPress admin
2. Enter the listing title and description
3. Set a featured image using the Featured Image meta box
4. Fill in the custom fields in the "Listing Details" meta box:
   - Price
   - Location
   - Address details
   - Contact information
   - Website URL
5. Assign categories and tags using the sidebar taxonomies
6. Publish the listing

### Managing Categories and Tags

- **Categories**: Go to **Listings** → **Categories** to manage listing categories (hierarchical)
- **Tags**: Go to **Listings** → **Tags** to manage listing tags (non-hierarchical)

## Custom Fields

The plugin adds the following custom fields to each listing:

- `_listing_price` - Price of the listing
- `_listing_location` - General location
- `_listing_address` - Street address
- `_listing_city` - City
- `_listing_state` - State/Province
- `_listing_zip_code` - ZIP/Postal code
- `_listing_country` - Country
- `_listing_contact_email` - Contact email address
- `_listing_contact_phone` - Contact phone number
- `_listing_website` - Website URL

## Retrieving Custom Field Data

You can retrieve custom field data in your theme templates using:

```php
// Get price
$price = get_post_meta( get_the_ID(), '_listing_price', true );

// Get location
$location = get_post_meta( get_the_ID(), '_listing_location', true );

// Get contact email
$email = get_post_meta( get_the_ID(), '_listing_contact_email', true );

// Get all custom fields
$address = get_post_meta( get_the_ID(), '_listing_address', true );
$city = get_post_meta( get_the_ID(), '_listing_city', true );
$state = get_post_meta( get_the_ID(), '_listing_state', true );
$zip = get_post_meta( get_the_ID(), '_listing_zip_code', true );
$country = get_post_meta( get_the_ID(), '_listing_country', true );
$phone = get_post_meta( get_the_ID(), '_listing_contact_phone', true );
$website = get_post_meta( get_the_ID(), '_listing_website', true );
```

## Querying Listings

You can query listings using standard WordPress WP_Query:

```php
$args = array(
    'post_type' => 'listing',
    'posts_per_page' => 10,
    'tax_query' => array(
        array(
            'taxonomy' => 'listing_category',
            'field'    => 'slug',
            'terms'    => 'your-category-slug',
        ),
    ),
);

$listings = new WP_Query( $args );
```

## Hooks and Filters

The plugin follows WordPress coding standards and can be extended using standard WordPress hooks and filters.

## Requirements

- WordPress 5.0 or higher
- PHP 7.2 or higher

## License

GPL v2 or later
