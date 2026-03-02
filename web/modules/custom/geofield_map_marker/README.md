# Geofield Map Marker

A Drupal module that enhances `geofield_map` Views displays with numbered SVG markers, interactive card-to-map hover sync, and a single-node map overlay. Designed to work with the `my_places` theme and the `geofield_map_marker_demo` recipe, but can be adapted to any project.

---

## Overview

The module provides two JavaScript behaviors and a set of PHP hooks that wire together:

- A **Views list display** (`block_1`) of `map_item` nodes rendered as horizontal cards
- A **Views attachment display** (`attachment_1`) rendering a `geofield_google_map` style
- A **full node display** with an inline single-marker geofield map

The PHP side populates `drupalSettings` with marker data (nid, URL, title, category) and attaches the correct library to each display. The JS side intercepts the default geofield marker rendering and replaces it with numbered, styled SVG circles.

---

## Dependencies

### Drupal modules
| Module | Purpose |
|---|---|
| `geofield` | Provides the `geofield` field type (WKT geometry storage) |
| `geofield_map` | Provides `geofield_google_map` Views style and `geofieldMapInit` JS event |
| `geocoder` | Geocoding service integration |
| `geocoder_field` | Automatically geocodes a text field into a geofield on entity save |
| `better_exposed_filters` | BEF autosubmit on the category taxonomy filter in the View |

### JavaScript libraries (manual placement required)
| Library | Path |
|---|---|
| GSAP 3.x | `web/libraries/gsap/minified/gsap.min.js` |
| in-view | `web/libraries/in-view/in-view.min.js` |

---

## How It Works

### 1. Geocoding on save (`hook_node_presave`)

When a `map_item` node is saved, the module reads the `field_category` taxonomy term name and writes `"<node title>, <category name>"` into `field_geocode` (a plain text field). The `geocoder_field` module is configured to watch `field_geocode` and populate `field_map_coordinates` (geofield WKT) automatically using the Google Maps geocoder provider.

```
node save → field_geocode = "Title, Category" → geocoder_field → field_map_coordinates (WKT)
```

This means coordinates are automatically derived from the node title + category without requiring manual lat/lon entry.

### 2. Views list + map (`hook_views_pre_render`)

For the `map_items_list` view:

- **`attachment_1`** (geofield map): The module attaches the `geofield_map_marker/map_list` library and builds `drupalSettings.list.markers` — an array of objects containing `{ nid, url, title, category }` for every result row. This data is consumed by `map_list.js`.
- **`block_1`** (card list): The module iterates results and stamps a sequential `card_count` integer onto each entity. This is used by `hook_preprocess_node` to render the numbered counter badge.

### 3. Node preprocessing (`hook_preprocess_node`)

- **`card_horizontal` view mode**: Renders `card_count` as `<span class="c-card-count">N</span>` and passes it as a `card_label` variable to the node template. The template uses `card_label` to apply a matching CSS class (`circle-card--N`) that syncs the card's colour with its map marker.
- **`full` view mode**: Attaches the `geofield_map_marker/map_node` library for the single-node map overlay.

### 4. Map list JS (`js/map_list.js`)

Runs once on `#geofield-map-view-map-items-list-attachment-1` after the `geofieldMapInit` event fires.

- Replaces all default geofield markers with numbered SVG circle markers (teal `#2E808E` default, orange `#D0451B` active/hover)
- Binds hover events between `ul.c-map-display__listing > li` card items and their corresponding map marker — hovering a card highlights the marker and vice versa
- Opens a Google Maps InfoWindow on marker click showing the node title and category
- Adds a "View on Google Maps (transit)" button linking to `https://maps.google.com/maps?travelmode=transit&daddr=<lat,lng>`
- Consumes `drupalSettings.list.markers[i]` for all data

### 5. Map node JS (`js/map_node.js`)

Runs once on `.field--type-geofield` after `geofieldMapInit` fires on the full node page.

- Replaces the default geofield marker with a custom `OverlayView` (`div.c-map-display__counter`) rendered in the map pane
- Adds the same transit button as the list map

---

## Field Structure

| Field | Type | Purpose |
|---|---|---|
| `field_geocode` | `string_long` | Text input for geocoding (hidden on form display) |
| `field_map_coordinates` | `geofield` | WKT geometry auto-populated by geocoder_field |
| `field_category` | `entity_reference` → `taxonomy_term` | Taxonomy term from `location_category` vocabulary |
| `field_intro_text` | `text_long` | Introductory body text for the node |

---

## View Structure (`map_items_list`)

| Display | Plugin | Purpose |
|---|---|---|
| `default` | — | Shared filters, sorts, BEF exposed form config |
| `block_1` | Block | HTML list of cards (`ul.c-map-display__listing`), exposed category filter |
| `attachment_1` | Attachment | `geofield_google_map` style, attaches before `block_1`, inherits exposed filters |
| `page_1` | Page | Simple page fallback at `/map-items-list` |

---

## Configuration

### Google Maps API Key

The module uses Google Maps for both geocoding and map rendering. After applying the recipe (or manually), update the API key in:

- `geocoder.geocoder_provider.googlemaps` — used by `geocoder_field` for geocoding on save
- `geofield_map.settings` — used by `geofield_google_map` Views style for rendering

Search for `INSERT_GOOGLE_MAPS_API_KEY` in both config files and replace with your key.

---

## Customisation

| What | Where |
|---|---|
| Marker colours | `js/map_list.js` — `markerColor` / `markerActiveColor` constants |
| Map center / zoom | `views.view.map_items_list.yml` → `attachment_1` → `map_center` / `map_zoom_and_pan` |
| Map style (JSON) | `views.view.map_items_list.yml` → `attachment_1` → `custom_style_map.custom_style_options` |
| Content type machine name | Change all `map_item` references in `.module`, JS, config YAML, and Twig templates |
| Taxonomy vocabulary | Change all `location_category` references in `.module`, field config, and Views YAML |
