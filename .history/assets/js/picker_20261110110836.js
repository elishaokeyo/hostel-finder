// Click-to-set coordinates for the admin forms.
(() => {
  const el = document.getElementById('picker');
  if (!el) return;
  const lat = document.getElementById('latitude'), lng = document.getElementById('longitude');
  const has = lat.value !== '' && lng.value !== '';
  const start = has ? [+lat.value, +lng.value] : [+el.dataset.lat, +el.dataset.lng];

  const map = L.map(el).setView(start, has ? 16 : 14);
  const street = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19, attribution: '&copy; OpenStreetMap contributors'
  });
  const detailed = L.tileLayer('https://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png', {
    maxZoom: 19, subdomains: 'abc', attribution: '&copy; OpenStreetMap contributors, tiles by HOT'
  });
  const imagery = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
    maxZoom: 19, maxNativeZoom: 17, attribution: 'Imagery &copy; Esri'
  });
  const names = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}', {
    maxZoom: 19, maxNativeZoom: 17
  });
  street.addTo(map);
  L.control.layers({ 'Street': street, 'Detailed streets': detailed, 'Satellite': L.layerGroup([imagery, names]) }).addTo(map);
  L.control.scale({ imperial: false }).addTo(map);

  let marker = has ? L.marker(start).addTo(map) : null;
  function place(ll) {
    lat.value = (+ll.lat).toFixed(6);
    lng.value = (+ll.lng).toFixed(6);
    marker ? marker.setLatLng(ll) : (marker = L.marker(ll).addTo(map));
  }
  map.on('click', e => place(e.latlng));
  [lat, lng].forEach(i => i.addEventListener('change', () => {
    const a = parseFloat(lat.value), b = parseFloat(lng.value);
    if (!isNaN(a) && !isNaN(b)) { place({ lat: a, lng: b }); map.setView([a, b], 16); }
  }));
})();
