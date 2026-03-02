/**
 * @file
 * Map list behavior for geofield_map_marker.
 *
 * Replaces default geofield markers with numbered SVG circle markers,
 * syncs hover/click between the card list and the map, and adds a
 * transit layer toggle button.
 *
 * Depends on drupalSettings.list.markers[] populated by the module's
 * hook_views_pre_render() for the map_items_list view attachment_1 display.
 */
(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.mapListBehavior = {
    attach(context) {
      once('map_list', '#geofield-map-view-map-items-list-attachment-1', context).forEach(() => {
        createMap(context);
      });
    }
  };

  function createMap(context) {
    // React on geofieldMapInit event fired by geofield_map after the Google
    // Map instance is ready.
    $(context).on('geofieldMapInit', function (e, mapid) {
      const map = Drupal.geoFieldMapFormatter.map_data[mapid].map;

      createTransitButton(map);

      // ----------------------------------------------------------------
      // Transit layer toggle button
      // ----------------------------------------------------------------
      function createTransitButton(map) {
        let transitActive = false;

        const controlUI = document.createElement('BUTTON');
        controlUI.classList.add('u-transit-button');

        const controlText = document.createElement('SPAN');
        controlText.classList.add('visually-hidden');
        controlText.innerHTML = 'Transit';
        controlUI.appendChild(controlText);

        // Bus icon SVG.
        const icon = '<svg width="14" height="16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="m2.507 13.29.565-.79H1c-.186 0-.303-.06-.371-.129C.559 12.303.5 12.186.5 12V2C.5 1.176 1.176.5 2 .5h10c.824 0 1.5.676 1.5 1.5v10.1c0 .186-.06.303-.129.371-.068.07-.185.129-.371.129H10.828l.565.79.987 1.383c.17.296.085.565-.137.698-.303.182-.579.097-.714-.128l-.005-.008-.005-.009-1.7-2.6-.149-.226H4.235l-.148.219-1.7 2.5-.008.012-.008.012c-.135.225-.411.31-.714.128-.222-.133-.307-.402-.137-.699l.987-1.381ZM12 7.5h.5v-6h-11v6H12ZM1.5 10c0 .414.14.797.421 1.079.282.28.665.421 1.079.421.414 0 .797-.14 1.079-.421.28-.282.421-.665.421-1.079 0-.414-.14-.797-.421-1.079C3.797 8.641 3.414 8.5 3 8.5c-.414 0-.797.14-1.079.421-.28.282-.421.665-.421 1.079Zm8 0c0 .414.14.797.421 1.079.282.28.665.421 1.079.421.414 0 .797-.14 1.079-.421.28-.282.421-.665.421-1.079 0-.414-.14-.797-.421-1.079-.282-.28-.665-.421-1.079-.421-.414 0-.797.14-1.079.421-.28.282-.421.665-.421 1.079Z" fill="#000" stroke="#000"/></svg>';
        controlUI.insertAdjacentHTML('beforeend', icon);

        map.controls[google.maps.ControlPosition.RIGHT_TOP].push(controlUI);
        map.setOptions({ clickableIcons: false });

        const transitLayer = new google.maps.TransitLayer();

        // Re-apply transit state if the map was re-initialized.
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
      }

      // ----------------------------------------------------------------
      // Collect article IDs from the card list.
      // Drupal renders each map_item node card-horizontal with id="map-item-id-{nid}".
      // ----------------------------------------------------------------
      const cardIds = $('article[id^=\'map-item-id-\']').map(function () {
        // Extract the nid from the trailing segment of the id.
        return this.id.split('-').slice(-1)[0];
      });

      // Default geofield markers.
      const markers = Drupal.geoFieldMapFormatter.map_data[mapid].markers;

      const myMarkers = [];
      let count = 0;
      let markerIndex = 1;

      // ----------------------------------------------------------------
      // Replace default markers with numbered SVG circle markers.
      // ----------------------------------------------------------------
      $.each(cardIds, function (index, value) {
        let marker = markers[value];
        marker.setMap(null);

        const position = marker.getPosition();
        count++;

        marker = new google.maps.Marker({
          position: position,
          map: map,
        });

        colorSvgMarker(marker, '#2E808E', count);
        marker.set('count', count);
        marker.set('clicked', false);
        marker.setZIndex(markerIndex++);
        myMarkers.push(marker);
        marker.setMap(map);

        // Click: highlight this marker and show its InfoWindow.
        google.maps.event.addListener(marker, 'click', function () {
          resetMarkers();
          const fillColor = '#D0451B';
          const markerCount = marker.get('count');
          const isClicked = marker.get('clicked');
          markerIndex++;

          if (!isClicked) {
            const circleCardId = 'circle-card--' + markerCount;
            const cardIcon = document.getElementById(circleCardId);
            if (cardIcon) {
              cardIcon.style.fill = fillColor;
            }
            marker.set('clicked', true);
            marker.set('cardIcon', circleCardId);
            marker.setZIndex(markerIndex);
            colorSvgMarker(marker, fillColor, markerCount);
          }

          showInfoWindow(marker, markerCount - 1);
          scrollSideBar(markerCount - 1);
        });
      });

      let hoverIndex = 0;
      let hoverLabel = 0;
      let hoverCardIcon = '';
      const defaultColor = '#2E808E';
      const activeColor = '#D0451B';
      let hoverMarker = myMarkers[0];

      // ----------------------------------------------------------------
      // List item hover: highlight the corresponding map marker.
      // ----------------------------------------------------------------
      $('ul.c-map-display__listing > li')
        .hover(
          function () {
            resetMarkers();

            hoverIndex = $(this).index();
            hoverLabel = hoverIndex + 1;
            hoverMarker = myMarkers[hoverIndex];
            hoverMarker.setZIndex(markerIndex++);

            colorSvgMarker(hoverMarker, activeColor, hoverLabel);

            const circleCardId = 'circle-card--' + hoverLabel;
            hoverCardIcon = document.getElementById(circleCardId);
            if (hoverCardIcon) {
              hoverCardIcon.style.fill = activeColor;
            }

            showInfoWindow(hoverMarker, hoverIndex);
          },
          function () {
            resetMarkers();
            if (hoverCardIcon) {
              hoverCardIcon.style.fill = defaultColor;
            }
            colorSvgMarker(hoverMarker, defaultColor, hoverLabel);
          }
        );

      // ----------------------------------------------------------------
      // SVG circle template (filled with {{ color }}).
      // ----------------------------------------------------------------
      function getSvgElement() {
        return [
          '<svg class="shadow" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">',
          '<circle cx="12" cy="12" r="11.5" fill="{{ color }}" stroke="#fff"/>',
          '</svg>',
        ].join('\n');
      }

      const trackinfowindow = [];

      // ----------------------------------------------------------------
      // Reset all markers and close open InfoWindows.
      // ----------------------------------------------------------------
      function resetMarkers() {
        trackinfowindow.forEach(iw => iw.close());
        trackinfowindow.length = 0;

        for (let i = 0; i < myMarkers.length; i++) {
          const m = myMarkers[i];
          if (m.get('clicked') === true) {
            m.set('clicked', false);
            const iconId = m.get('cardIcon');
            const cardIconEl = document.getElementById(iconId);
            if (cardIconEl) {
              cardIconEl.style.fill = defaultColor;
            }
            colorSvgMarker(m, defaultColor, m.get('count'));
          }
        }
      }

      // ----------------------------------------------------------------
      // Scroll the sidebar drawer to bring the card into view.
      // ----------------------------------------------------------------
      function scrollSideBar(index) {
        const list = $('ul.c-map-display__listing li').eq(index);
        const cardPos = list.offset().top;
        const drawerPos = $('#map-display__drawer').offset().top;
        const drawerScroll = $('#map-display__drawer').scrollTop();
        const scrollPos = cardPos - drawerPos + drawerScroll;
        $('#map-display__drawer').stop().animate({ scrollTop: scrollPos }, 500);
      }

      // ----------------------------------------------------------------
      // Show an InfoWindow above a marker.
      // drupalSettings.list.markers[index] contains { nid, url, title, category }.
      // ----------------------------------------------------------------
      function showInfoWindow(marker, index) {
        const nidMarkers = drupalSettings.list.markers;
        const position = marker.position;
        const cardPreTitle = nidMarkers[index] ? nidMarkers[index].category : '';
        const cardTitle = nidMarkers[index] ? nidMarkers[index].title : '';
        const url = nidMarkers[index] ? nidMarkers[index].url : '#';

        const contentString =
          '<div class="tool-tip--container">' +
          '<h3 class="c-card-horizontal__title">' +
          '<a class="a-tooltip click-region" href="' + url + '">' +
          '<span class="c-card-horizontal__preTitle">' + cardPreTitle + '</span>' +
          '<span class="tooltip">' + cardTitle + '</span>' +
          '</a>' +
          '</h3>' +
          '</div>';

        const infowindow = new google.maps.InfoWindow();
        infowindow.setContent(contentString);
        infowindow.setPosition(position);
        infowindow.setOptions({ pixelOffset: new google.maps.Size(0, -25) });
        infowindow.setZIndex(999);
        infowindow.open(map);
        trackinfowindow.push(infowindow);
      }

      // ----------------------------------------------------------------
      // Color a marker by encoding an SVG circle as a base64 data URI.
      // ----------------------------------------------------------------
      function colorSvgMarker(marker, fillColor, markerLabel) {
        let mapMarker = getSvgElement().replace('{{ color }}', fillColor);
        marker.setIcon({
          url: 'data:image/svg+xml;charset=UTF-8;base64,' + btoa(mapMarker),
          scaledSize: new google.maps.Size(26, 26),
        });
        marker.setLabel({
          text: markerLabel.toString(),
          class: 'text-icon',
          stroke: '#FFF',
          strokeWidth: '1px',
          color: '#fff',
          fontWeight: '800',
          fontSize: '14px',
          fontFamily: '"Helvetica Neue", Helvetica, Arial, sans-serif',
        });
      }
    });
  }
})(jQuery, Drupal, drupalSettings);
