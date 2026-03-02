# Geofield Map Marker Demo Recipe

A Drupal Recipe that scaffolds the full `map_item` content type, `location_category` taxonomy, all required field configuration, and the `map_items_list` View needed to run the `geofield_map_marker` module and `my_places` theme on a fresh Drupal site.

---

## What This Recipe Does

Applying this recipe will:

1. Install the required contrib modules (`geofield`, `geofield_map`, `geocoder`, `geocoder_field`, `better_exposed_filters`)
2. Create the `map_item` content type
3. Create the `location_category` taxonomy vocabulary
4. Create the `card_horizontal` entity view mode for nodes
5. Add all required fields to `map_item` (`field_geocode`, `field_map_coordinates`, `field_category`, `field_intro_text`)
6. Configure entity view displays (`full`, `card_horizontal`) and the default form display
7. Create the `map_items_list` View with its block, attachment, and page displays
8. Configure the Google Maps geocoder provider and `geofield_map` global settings
9. Grant content editing permissions to the `editor` role (creating it if it does not exist)

The recipe does **not** install or configure the `my_places` theme — that is a manual step.

---

## Prerequisites

### 1. Composer packages

Run the following before applying the recipe:

```bash
composer require \
  drupal/geofield:^1.39 \
  drupal/geofield_map:^11.1 \
  drupal/geocoder:^4.0 \
  geocoder-php/google-maps-provider:^4.6 \
  drupal/better_exposed_filters:^7.1 \
  drupal/components:^3.1 \
  drupal/twig_field_value:^2.0
```

### 2. Custom module and theme

Copy both of the following into your project before running the recipe:

- `web/modules/custom/geofield_map_marker/`
- `web/themes/custom/my_places/`

### 3. JavaScript libraries (manual)

Two front-end libraries are not available via Composer and must be placed manually:

| Library | Download | Required path |
|---|---|---|
| GSAP 3.x | [gsap.com](https://gsap.com) | `web/libraries/gsap/minified/gsap.min.js` |
| in-view | [github.com/camwiegert/in-view](https://github.com/camwiegert/in-view) | `web/libraries/in-view/in-view.min.js` |

### 4. Google Maps API key

Before applying the recipe, replace `INSERT_GOOGLE_MAPS_API_KEY` in both of these config files:

- `recipes/geofield_map_marker_demo/config/geocoder.geocoder_provider.googlemaps.yml`
- `recipes/geofield_map_marker_demo/config/geofield_map.settings.yml`

Your key needs the following Google APIs enabled: **Maps JavaScript API**, **Geocoding API**.

---

## Applying the Recipe

```bash
ddev drush recipe recipes/geofield_map_marker_demo
ddev drush cr
```

If using a standalone Drush (not DDEV):

```bash
drush recipe recipes/geofield_map_marker_demo
drush cr
```

---

## After Applying

### Enable and configure the theme

```bash
ddev drush theme:enable my_places
ddev drush config:set system.theme default my_places
```

Build the theme CSS:

```bash
cd web/themes/custom/my_places
npm install
npm run compile
```

### Place the block

Go to **Structure → Block layout** and place the `Map Items list` block (the `block_1` display of the `map_items_list` View) in the main content region of a page, or use a Layout Builder / Paragraphs setup to embed it.

### Add content

Create one or more `Map Item` nodes. Each node needs:

- A **Title** (used as part of the geocode query)
- A **Location Category** taxonomy term
- An **Intro Text**

On save, `geofield_map_marker` will write `"<Title>, <Category>"` to `field_geocode`. The `geocoder_field` module will automatically geocode that string into `field_map_coordinates` using the Google Maps provider. The node will then appear on the map.

---

## Configuration Overview

### Content type: `map_item`

| Field | Machine name | Type |
|---|---|---|
| Title | `title` | Core |
| Intro Text | `field_intro_text` | `text_long` |
| Location Category | `field_category` | `entity_reference` → taxonomy_term (`location_category`) |
| Geocode (hidden) | `field_geocode` | `string_long` |
| Map Coordinates | `field_map_coordinates` | `geofield` (WKT) |

`field_geocode` and `field_map_coordinates` are hidden on the default form display — they are populated automatically on save.

### View: `map_items_list`

| Display | Type | Description |
|---|---|---|
| `block_1` | Block | HTML `<ul>` list of `card_horizontal` rendered entities with BEF category filter |
| `attachment_1` | Attachment | `geofield_google_map` map panel; attaches before `block_1`; inherits exposed filters |
| `page_1` | Page | Fallback page at `/map-items-list` |

### Geocoder flow

```
node_presave
  → field_geocode = "<Title>, <Category>"
  → geocoder_field (googlemaps provider)
  → field_map_coordinates (WKT point)
  → geofield_google_map renders marker on map
```

---

## File Structure

```
recipes/geofield_map_marker_demo/
├── recipe.yml
├── composer.json
└── config/
    ├── node.type.map_item.yml
    ├── taxonomy.vocabulary.location_category.yml
    ├── core.entity_view_mode.node.card_horizontal.yml
    ├── field.storage.node.field_geocode.yml
    ├── field.field.node.map_item.field_geocode.yml
    ├── field.storage.node.field_map_coordinates.yml
    ├── field.field.node.map_item.field_map_coordinates.yml
    ├── field.storage.node.field_category.yml
    ├── field.field.node.map_item.field_category.yml
    ├── field.storage.node.field_intro_text.yml
    ├── field.field.node.map_item.field_intro_text.yml
    ├── core.entity_view_display.node.map_item.card_horizontal.yml
    ├── core.entity_view_display.node.map_item.full.yml
    ├── core.entity_form_display.node.map_item.default.yml
    ├── geocoder.geocoder_provider.googlemaps.yml
    ├── geofield_map.settings.yml
    └── views.view.map_items_list.yml
```

---

## Troubleshooting

**Nodes have no coordinates after save**
- Verify the Google Maps API key is correct and both the Maps JavaScript API and Geocoding API are enabled in the Google Cloud Console.
- Check that `geocoder_field` third-party settings are present on `field_map_coordinates` (see `field.field.node.map_item.field_map_coordinates.yml`).

**Map does not render**
- Confirm the `geofield_map` global API key is set: `drush config:get geofield_map.settings gmap_api_key`
- Check the browser console for Google Maps API errors.

**JS libraries missing (GSAP / in-view)**
- Confirm `web/libraries/gsap/minified/gsap.min.js` and `web/libraries/in-view/in-view.min.js` exist.
- Run `drush cr` after placing them.

**Cards do not highlight on map hover**
- Confirm theme CSS has been compiled (`npm run compile` in `web/themes/custom/my_places/`).
- Verify `node--map-item--card-horizontal.html.twig` is rendering `id="map-item-id-{{ node.id() }}"` on the `<article>` element.
