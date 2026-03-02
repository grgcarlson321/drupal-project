/**
 * @file
 * Single-node map behavior for geofield_map_marker.
 *
 * On full node pages for map_item content, replaces the default geofield
 * marker with a custom DOM overlay (div.c-map-display__counter) and adds
 * a transit layer toggle button.
 */
(function ($, Drupal, once) {
  Drupal.behaviors.mapNodeBehavior = {
    attach: function attach(context) {
      once('map_node', '.field--type-geofield', context).forEach(() => {
        $(context).on('geofieldMapInit', function (e, mapid) {
          initMap(mapid);
        });
      });
    },
  };

  function initMap(mapid) {
    let transitActive = false;

    // ----------------------------------------------------------------
    // Transit layer toggle button (same icon as the list map).
    // ----------------------------------------------------------------
    const controlUI = document.createElement('BUTTON');
    controlUI.classList.add('u-transit-button');

    const controlText = document.createElement('SPAN');
    controlText.classList.add('visually-hidden');
    controlText.innerHTML = 'Transit';
    controlUI.appendChild(controlText);

    const icon = '<svg width="14" height="16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="m2.507 13.29.565-.79H1c-.186 0-.303-.06-.371-.129C.559 12.303.5 12.186.5 12V2C.5 1.176 1.176.5 2 .5h10c.824 0 1.5.676 1.5 1.5v10.1c0 .186-.06.303-.129.371-.068.07-.185.129-.371.129H10.828l.565.79.987 1.383c.17.296.085.565-.137.698-.303.182-.579.097-.714-.128l-.005-.008-.005-.009-1.7-2.6-.149-.226H4.235l-.148.219-1.7 2.5-.008.012-.008.012c-.135.225-.411.31-.714.128-.222-.133-.307-.402-.137-.699l.987-1.381ZM12 7.5h.5v-6h-11v6H12ZM1.5 10c0 .414.14.797.421 1.079.282.28.665.421 1.079.421.414 0 .797-.14 1.079-.421.28-.282.421-.665.421-1.079 0-.414-.14-.797-.421-1.079C3.797 8.641 3.414 8.5 3 8.5c-.414 0-.797.14-1.079.421-.28.282-.421.665-.421 1.079Zm8 0c0 .414.14.797.421 1.079.282.28.665.421 1.079.421.414 0 .797-.14 1.079-.421.28-.282.421-.665.421-1.079 0-.414-.14-.797-.421-1.079-.282-.28-.665-.421-1.079-.421-.414 0-.797.14-1.079.421-.28.282-.421.665-.421 1.079Z" fill="#000" stroke="#000"/></svg>';
    controlUI.insertAdjacentHTML('beforeend', icon);

    const map = Drupal.geoFieldMapFormatter.map_data[mapid].map;
    map.controls[google.maps.ControlPosition.RIGHT_TOP].push(controlUI);
    map.setOptions({ clickableIcons: false });

    const transitLayer = new google.maps.TransitLayer();

    if (transitActive) {
      transitLayer.setMap(map);
      controlUI.classList.add('is-active');
    }

    $(controlUI).click(function () {
      if (typeof transitLayer.getMap() === 'undefined' || transitLayer.getMap() === null) {
        transitActive = true;
        transitLayer.setMap(map);
        controlUI.classList.add('is-active');
      } else {
        transitActive = false;
        transitLayer.setMap(null);
        controlUI.classList.remove('is-active');
      }
    });

    // ----------------------------------------------------------------
    // Custom marker overlay using Google Maps OverlayView.
    // Renders as a div.c-map-display__counter positioned over the map.
    // ----------------------------------------------------------------
    class MyMarker extends google.maps.OverlayView {
      constructor(params) {
        super();

        this.position = params.position;
        this.label = params.label;

        const content = document.createElement('div');
        content.textContent = params.label;
        content.setAttribute('id', 'marker-' + params.label);
        content.classList.add('c-map-display__counter');

        const container = document.createElement('div');
        container.style.position = 'absolute';
        container.style.cursor = 'pointer';
        container.appendChild(content);

        this.container = container;
      }

      /** Called when the overlay is added to the map. */
      onAdd() {
        this.getPanes().floatPane.appendChild(this.container);
      }

      /** Called when the overlay is removed from the map. */
      onRemove() {
        this.container.remove();
      }

      /** Called each frame to reposition the overlay. */
      draw() {
        const pos = this.getProjection().fromLatLngToDivPixel(this.position);
        this.container.style.left = pos.x + 'px';
        this.container.style.top = pos.y + 'px';
      }
    }

    // Replace each default geofield marker with a MyMarker overlay.
    const markers = Drupal.geoFieldMapFormatter.map_data[mapid].markers;

    $.each(markers, function (storeId, marker) {
      marker.setMap(null);

      const position = marker.getPosition();

      const customMarker = new MyMarker({
        position: position,
        label: ' ',
        active: false,
      });

      customMarker.setMap(map);
    });
  }
})(jQuery, Drupal, once);
