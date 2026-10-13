// One keyless map for both address areas. Directions retain the supplied addresses.
(() => {
  const container = document.querySelector('[data-contact-map]');
  if (!container || !window.L) return;

  const english = document.documentElement.lang === 'en';
  const locations = [...document.querySelectorAll('.contact-location[data-location]')];
  const points = locations.map((location) => [Number(location.dataset.lat), Number(location.dataset.lng)]);
  const reset = document.querySelector('[data-map-reset]');
  const status = document.querySelector('[data-map-status]');
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  const initialize = () => {
    const map = L.map(container, {
      scrollWheelZoom: false,
      zoomControl: false,
      dragging: !window.matchMedia('(pointer: coarse)').matches,
      zoomAnimation: !reducedMotion,
      fadeAnimation: !reducedMotion,
      markerZoomAnimation: !reducedMotion,
    });
    container.classList.add('map-ready');
    L.control.zoom({ position: 'topleft', zoomInTitle: english ? 'Zoom in' : 'Povećaj', zoomOutTitle: english ? 'Zoom out' : 'Smanji' }).addTo(map);
    const showBoth = () => map.fitBounds(points, {
      paddingTopLeft: [40, 48],
      paddingBottomRight: [110, 48],
      maxZoom: 12,
      animate: false,
    });
    showBoth();

    const tiles = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    }).addTo(map);
    const failedTiles = new Set();
    tiles.on('tileerror', (event) => failedTiles.add(event.tile));
    tiles.on('tileload', (event) => failedTiles.delete(event.tile));
    tiles.on('tileunload', (event) => failedTiles.delete(event.tile));
    tiles.on('load', () => {
      status.hidden = !failedTiles.size;
      status.textContent = failedTiles.size
        ? (english ? 'Some map tiles could not load. Use the directions links above.' : 'Dio mape se nije učitao. Koristite upute za dolazak iznad.')
        : '';
    });

    locations.forEach((location, index) => {
      const city = location.querySelector('h2, h3').textContent;
      const name = location.querySelector('.contact-location-name').textContent;
      const address = location.querySelector('address');
      const directions = location.querySelector('.contact-directions');
      const popup = document.createElement('div');
      const heading = document.createElement('strong');
      heading.textContent = `${name} · ${city}`;
      popup.append(heading, document.createElement('br'), address.cloneNode(true), document.createElement('br'), directions.cloneNode(true));
      const marker = L.marker(points[index], {
        title: `${name}, ${city}`,
        icon: L.divIcon({
          className: 'contact-pin',
          html: `<span aria-hidden="true">0${index + 1}</span>`,
          iconSize: [34, 34],
          iconAnchor: [17, 17],
        }),
      }).addTo(map);
      marker.bindTooltip(city, { permanent: true, direction: 'right', offset: [20, 0], className: 'contact-map-label' });
      marker.bindPopup(popup, { maxWidth: 240 });
      marker.getElement().setAttribute('aria-label', `${name}, ${city}`);
    });

    reset.hidden = false;
    reset.addEventListener('click', () => { map.closePopup(); showBoth(); });
    map.on('resize', showBoth);
    if (window.ResizeObserver) {
      const observer = new ResizeObserver(() => map.invalidateSize({ pan: false }));
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
