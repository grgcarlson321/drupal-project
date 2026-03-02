# My Places Theme

A Drupal theme providing the front-end templates, SCSS components, and JavaScript behaviors for the `geofield_map_marker` map/list experience. Built on `stable9` and designed to work alongside the `geofield_map_marker` module.

---

## Overview

`my_places` supplies:

- A **split layout component** (`map-display`) that positions a geofield map panel beside a scrollable card list
- A **card component** (`card-horizontal`) used as the `card_horizontal` view mode for `map_item` nodes
- **Node templates** for both the card and full view modes
- A **Views template** routing the `map_items_list` block display into the `map-display` component
- A **Gulp 4 build pipeline** for compiling SCSS → CSS

---

## Requirements

### Drupal modules
| Module | Purpose |
|---|---|
| `drupal/components` `^3.1` | Enables the `@my-places-components` Twig namespace |
| `geofield_map_marker` | Provides the PHP hooks and JS that this theme's templates depend on |

### JavaScript libraries (manual placement required)
These are not available via Composer and must be placed manually before building or enabling the theme:

| Library | Required path |
|---|---|
| GSAP 3.x | `web/libraries/gsap/minified/gsap.min.js` |
| in-view | `web/libraries/in-view/in-view.min.js` |

---

## Build Setup

The theme uses Gulp 4 with `gulp-sass` (dart-sass) to compile SCSS.

```bash
cd web/themes/custom/my_places
npm install
npm run compile        # single build
npm run watch          # rebuild on file change
```

Output CSS lands in `build/css/`. The Gulp config is in `gulpfile.js` and path settings are in `config.js` (create if needed, or adjust paths directly in `gulpfile.js`).

---

## Directory Structure

```
my_places/
├── components/
│   ├── component.twig              # BEM class-management Twig macro
│   ├── map-display/
│   │   ├── map-display.twig        # Split map + card-list layout
│   │   ├── map-display.scss        # Component styles
│   │   └── map-display.js          # GSAP drawer animation + in-view trigger
│   └── card-horizontal/
│       ├── card-horizontal.twig    # Card layout for map_item nodes
│       └── card-horizontal.scss    # Card styles
├── partials/
│   ├── partials.scss               # Master SCSS import
│   ├── _variables.scss             # Colour tokens
│   └── _mixins.scss                # bp() breakpoint mixin
├── templates/
│   ├── views/
│   │   └── views-view--map-items-list--block-1.html.twig
│   └── node/
│       ├── node--map-item--full.html.twig
│       └── node--map-item--card-horizontal.html.twig
├── libraries/
│   ├── breakpoint/breakpoint.js    # window.bPoints.matches() utility
│   └── global/
│       ├── global.css
│       └── global.js
├── my_places.info.yml
├── my_places.libraries.yml
├── my_places.theme
├── gulpfile.js
└── package.json
```

---

## How It Works

### Twig namespace

The theme registers the `@my-places-components` namespace (via `my_places.info.yml`) pointing to the `components/` directory. Templates use this to include components:

```twig
{% include '@my-places-components/map-display/map-display.twig' with { ... } %}
```

### Views → map-display template chain

1. `views-view--map-items-list--block-1.html.twig` receives `attachment_before` (the rendered geofield map from `attachment_1`) and passes it into `map-display.twig` as the `map` variable.
2. `map-display.twig` renders the split layout: map panel on one side, exposed filter + scrollable `{{ rows }}` card list on the other.
3. The card list rows are rendered via `node--map-item--card-horizontal.html.twig`, which wraps each card in `<article id="map-item-id-{{ node.id() }}">`. The `id` attribute is what `map_list.js` uses to correlate cards with map markers.

### card-horizontal node template

The `card_label` variable (a sequential counter set by `geofield_map_marker_preprocess_node`) is used to:

- Apply `circle-card--{{ card_label }}` CSS class on the numbered SVG circle badge
- Match the card number to the corresponding numbered map marker in `map_list.js`

### SCSS colour tokens

Defined in `partials/_variables.scss`:

```scss
$aqua:   #2E808E;   // default marker + counter background
$orange: #D0451B;   // active/hover marker colour
```

These values must match the `markerColor` / `markerActiveColor` constants in `web/modules/custom/geofield_map_marker/js/map_list.js`.

### Breakpoint mixin

`partials/_mixins.scss` provides a `bp()` mixin:

```scss
@include bp(large)          // min-width: 1024px
@include bp(none large)     // max-width: 1023px
@include bp(small large)    // min-width: 640px and max-width: 1023px
```

Available named breakpoints: `tiny` (480), `small` (640), `medium` (768), `large` (1024), `xlarge` (1280).

### map-display.js

Drupal behavior `mapDisplayComponentBehavior` handles:

- **GSAP drawer animation** — sliding the card list drawer open/closed on the "Toggle Location List" button
- **in-view "View Map" button** — shows a floating CTA when the map scrolls out of view on mobile
- **Responsive reorder** — stacks map above list on narrow viewports using JS-driven DOM reorder

---

## Theme Hooks (`my_places.theme`)

| Hook | Purpose |
|---|---|
| `my_places_theme_suggestions_views_view_alter` | Adds `views_view__map_items_list__block_1` template suggestion |
| `my_places_theme_suggestions_views_view_unformatted_alter` | Adds unformatted view suggestion for the list display |
| `my_places_preprocess_page` | General page preprocessing |

---

## Customisation

| What | Where |
|---|---|
| Colours | `partials/_variables.scss` (also update `map_list.js` constants to match) |
| Map drawer width | `components/map-display/map-display.scss` — `.c-map-display__main` and `.c-map-display__drawer-inner` widths |
| Card layout | `components/card-horizontal/card-horizontal.twig` + `.scss` |
| "Toggle" button label | `components/map-display/map-display.twig` |
| Content type slug | Change `map-item` and `map_item` references in all template file names and template logic |
