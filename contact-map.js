// One keyless map for both address areas. Directions retain the supplied addresses.
(() => {
  const container = document.querySelector('[data-contact-map]');
  if (!container || !window.maplibregl) return;

  const english = document.documentElement.lang === 'en';
  const locations = [...document.querySelectorAll('.contact-location[data-location]')];
  const points = locations.map((location) => [Number(location.dataset.lng), Number(location.dataset.lat)]);
  const reset = document.querySelector('[data-map-reset]');
  const status = document.querySelector('[data-map-status]');
  const style = 'https://tiles.openfreemap.org/styles/positron';
  const showError = () => {
    container.dataset.mapState = 'error';
    status.hidden = false;
    status.textContent = english
      ? 'The map could not load. Use the directions links above.'
      : 'Mapa se nije učitala. Koristite upute za dolazak iznad.';
  };

  const initialize = () => {
    container.dataset.mapState = 'loading';
    let map;
    try {
      map = new maplibregl.Map({
        container,
        style,
        scrollZoom: false,
        dragRotate: false,
        dragPan: !window.matchMedia('(pointer: coarse)').matches,
        attributionControl: false,
        locale: {
          'NavigationControl.ZoomIn': english ? 'Zoom in' : 'Povećaj',
          'NavigationControl.ZoomOut': english ? 'Zoom out' : 'Smanji',
          'Popup.Close': english ? 'Close popup' : 'Zatvori',
        },
      });
    } catch {
      showError();
      return;
    }
    map.getCanvas().setAttribute('aria-label', container.getAttribute('aria-label'));
    map.touchZoomRotate.disableRotation();
    map.addControl(new maplibregl.NavigationControl({ showCompass: false }), 'top-left');
    map.addControl(new maplibregl.AttributionControl({ compact: false }), 'bottom-right');
    const bounds = points.reduce((bounds, point) => bounds.extend(point), new maplibregl.LngLatBounds());
    const showBoth = () => map.fitBounds(bounds, {
      padding: { top: 48, left: 40, bottom: 48, right: 110 },
      maxZoom: 12,
      duration: 0,
      bearing: 0,
      pitch: 0,
    });
    showBoth();
    map.on('error', showError);
    map.once('load', () => {
      container.classList.add('map-ready');
      if (container.dataset.mapState !== 'error') container.dataset.mapState = 'ready';
      reset.hidden = false;
    });

    const popups = [];
    locations.forEach((location, index) => {
      const city = location.querySelector('h2, h3').textContent;
      const name = location.querySelector('.contact-location-name').textContent;
      const address = location.querySelector('address');
      const directions = location.querySelector('.contact-directions');
      const popup = document.createElement('div');
      const heading = document.createElement('strong');
      heading.textContent = `${name} · ${city}`;
      popup.append(heading, document.createElement('br'), address.cloneNode(true), document.createElement('br'), directions.cloneNode(true));
      const pin = document.createElement('button');
      pin.type = 'button';
      pin.className = 'contact-pin';
      pin.title = `${name}, ${city}`;
      pin.setAttribute('aria-label', `${name}, ${city}`);
      const number = document.createElement('span');
      number.className = 'contact-pin-number';
      number.setAttribute('aria-hidden', 'true');
      number.textContent = `0${index + 1}`;
      const label = document.createElement('span');
      label.className = 'contact-map-label';
      label.setAttribute('aria-hidden', 'true');
      label.textContent = city;
      pin.append(number, label);
      const markerPopup = new maplibregl.Popup({ offset: 22, maxWidth: '240px' }).setDOMContent(popup);
      popups.push(markerPopup);
      new maplibregl.Marker({ element: pin }).setLngLat(points[index]).addTo(map);
      // A native button handles Enter/Space; MapLibre's extra keypress toggle would run twice.
      pin.addEventListener('click', (event) => {
        event.stopPropagation();
        const wasOpen = markerPopup.isOpen();
        popups.forEach((popup) => popup.remove());
        if (!wasOpen) markerPopup.setLngLat(points[index]).addTo(map);
      });
    });

    reset.addEventListener('click', () => { popups.forEach((popup) => popup.remove()); showBoth(); });
    map.on('resize', showBoth);
    if (window.ResizeObserver) {
      const observer = new ResizeObserver(() => map.resize());
      observer.observe(container);
      window.addEventListener('pagehide', () => observer.disconnect(), { once: true });
    }
  };

  if (window.IntersectionObserver) {
    const observer = new IntersectionObserver((entries) => {
      if (!entries.some((entry) => entry.isIntersecting)) return;
      observer.disconnect();
      initialize();
    }, { rootMargin: '200px' });
    observer.observe(container);
  } else initialize();
})();
