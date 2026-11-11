(() => {
  const $ = id => document.getElementById(id);
  const fmt = n => 'KSh ' + n.toLocaleString();
  const esc = s => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  const stars = n => '★'.repeat(Math.round(n)) + '☆'.repeat(5 - Math.round(n));

  let hostels = [], places = [];
  let target = { name: 'your university', lat: 0, lng: 0 };
  let pickMode = false, activeId = null;
  const markers = new Map();

  const map = L.map('map');
  const street = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19, referrerPolicy: 'strict-origin-when-cross-origin', attribution: '&copy; OpenStreetMap contributors'
  });
  const detailed = L.tileLayer('https://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png', {
    maxZoom: 19, referrerPolicy: 'strict-origin-when-cross-origin', subdomains: 'abc', attribution: '&copy; OpenStreetMap contributors, tiles by HOT'
  });
  const imagery = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
    maxZoom: 19, referrerPolicy: 'strict-origin-when-cross-origin', maxNativeZoom: 17, attribution: 'Imagery &copy; Esri'
  });
  const names = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}', {
    maxZoom: 19, referrerPolicy: 'strict-origin-when-cross-origin', maxNativeZoom: 17
  });
  street.addTo(map);
  L.control.layers({ 'Street': street, 'Detailed streets': detailed, 'Satellite': L.layerGroup([imagery, names]) }).addTo(map);
  L.control.scale({ imperial: false }).addTo(map);
  const targetMarker = L.circleMarker([0, 0], { radius: 10, color: '#b42318', fillColor: '#b42318', fillOpacity: .9 }).addTo(map);

  // Haversine distance in km
  function km(aLat, aLng, bLat, bLng) {
    const r = Math.PI / 180, dLat = (bLat - aLat) * r, dLng = (bLng - aLng) * r;
    const h = Math.sin(dLat / 2) ** 2 + Math.cos(aLat * r) * Math.cos(bLat * r) * Math.sin(dLng / 2) ** 2;
    return 12742 * Math.asin(Math.sqrt(h));
  }
  const walkMin = d => Math.max(1, Math.round(d / 5 * 60)); // ~5 km/h

  function setTarget(name, lat, lng) {
    target = { name, lat, lng };
    $('targetName').textContent = name;
    targetMarker.setLatLng([lat, lng]).bindTooltip('Heading to: ' + name);
    render(true);
  }

  function filtered() {
    const maxPrice = +$('maxPrice').value, maxDist = +$('maxDist').value,
          minRating = +$('minRating').value, type = $('roomType').value,
          avail = $('onlyAvail').checked;
    const list = hostels
      .map(h => ({ ...h, dist: km(target.lat, target.lng, h.latitude, h.longitude) }))
      .filter(h => h.price_per_month <= maxPrice && h.dist <= maxDist && h.rating >= minRating
                && (!type || h.room_type === type) && (!avail || h.available_rooms > 0));
    const sort = $('sortBy').value;
    list.sort((a, b) => sort === 'price' ? a.price_per_month - b.price_per_month
                      : sort === 'rating' ? b.rating - a.rating : a.dist - b.dist);
    return list;
  }

  function render(fit) {
    $('priceOut').textContent = fmt(+$('maxPrice').value);
    $('distOut').textContent = $('maxDist').value + ' km';
    const list = filtered();
    $('count').textContent = list.length + (list.length === 1 ? ' hostel' : ' hostels') + ' match';

    $('list').innerHTML = list.length ? list.map(h => `
      <article class="card ${h.available_rooms ? '' : 'full'} ${h.id === activeId ? 'active' : ''}" data-id="${h.id}" tabindex="0">
        ${h.photos.length ? `<img class="thumb" src="${esc(h.photos[0])}" alt="Photo of ${esc(h.name)}" loading="lazy" onerror="this.remove()">` : ''}
        <div><h2>${esc(h.name)}</h2><div class="addr">${esc(h.address)}</div></div>
        <div class="price">${fmt(h.price_per_month)}<small>per month</small></div>
        <div class="meta">
          ${h.reviews_count
            ? `<span class="stars">★ ${h.rating.toFixed(1)} <span class="muted">(${h.reviews_count} ${h.reviews_count === 1 ? 'review' : 'reviews'})</span></span>`
            : '<span class="muted">No reviews yet</span>'}
          <span class="dist">${h.dist.toFixed(1)} km, ${walkMin(h.dist)} min walk</span>
          <span>${esc(h.room_type)}</span>
          <span>${h.available_rooms ? h.available_rooms + ' rooms free' : 'Fully booked'}</span>
          ${h.phone ? `<a href="tel:${esc(h.phone)}">${esc(h.phone)}</a>` : ''}
        </div>
        <div class="tags">${h.amenities.map(a => `<span>${esc(a)}</span>`).join('')}</div>
        <button type="button" class="more" data-open="${h.id}">${h.photos.length > 1 ? 'Photos and reviews' : 'Reviews'}</button>
      </article>`).join('')
      : '<div class="empty">No hostels match. Raise the price or distance limit to see more.</div>';

    markers.forEach(m => map.removeLayer(m)); markers.clear();
    list.forEach(h => {
      const m = L.marker([h.latitude, h.longitude]).addTo(map)
        .bindPopup(`<strong>${esc(h.name)}</strong><br>${fmt(h.price_per_month)}/mo<br>★ ${h.rating.toFixed(1)}, ${h.dist.toFixed(1)} km`);
      m.on('click', () => select(h.id, false));
      markers.set(h.id, m);
    });
    if (fit) {
      const pts = [[target.lat, target.lng], ...list.map(h => [h.latitude, h.longitude])];
      map.fitBounds(pts, { padding: [40, 40], maxZoom: 18 });
    }
  }

  function select(id, fly = true) {
    activeId = id;
    document.querySelectorAll('.card').forEach(c => c.classList.toggle('active', +c.dataset.id === id));
    const m = markers.get(id);
    if (m) { if (fly) map.flyTo(m.getLatLng(), 16); m.openPopup(); }
    const card = document.querySelector(`.card[data-id="${id}"]`);
    if (card && !fly) card.scrollIntoView({ block: 'nearest' });
  }

  // ---- details dialog: photos + reviews ----
  const dlg = $('detail'), body = $('detailBody');
  dlg.addEventListener('click', e => { if (e.target === dlg) dlg.close(); });

  function renderReviews(list) {
    return list.length
      ? '<ul class="revlist">' + list.map(r => `
          <li><strong>${esc(r.reviewer_name)}</strong>
            <span class="stars">${stars(r.rating)}</span>
            <span class="muted">${esc(r.created_at.slice(0, 10))}</span>
            ${r.comment ? `<p>${esc(r.comment)}</p>` : ''}</li>`).join('') + '</ul>'
      : '<p class="muted">No reviews yet. Be the first to review this hostel.</p>';
  }

  async function loadReviews(h) {
    $('avg').innerHTML = h.reviews_count
      ? `<span class="stars">${stars(h.rating)}</span> ${h.rating.toFixed(1)} from ${h.reviews_count} ${h.reviews_count === 1 ? 'review' : 'reviews'}`
      : '';
    try {
      const d = await (await fetch('api/reviews.php?hostel_id=' + h.id)).json();
      $('revList').innerHTML = renderReviews(d.reviews || []);
    } catch { $('revList').textContent = 'Could not load reviews.'; }
  }

  function openDetail(id) {
    const h = hostels.find(x => x.id === id);
    if (!h) return;
    body.innerHTML = `
      <h2>${esc(h.name)}</h2>
      <p class="addr">${esc(h.address)}</p>
      ${h.photos.length ? `<div class="gallery">${h.photos.map(p => `<img src="${esc(p)}" alt="Photo of ${esc(h.name)}" loading="lazy" onerror="this.remove()">`).join('')}</div>` : ''}
      <p id="avg"></p>
      <div id="revList">Loading reviews…</div>
      <form id="revForm" class="revform">
        <h3>Leave a review</h3>
        <label>Your name <input name="name" maxlength="60" required></label>
        <label>Rating
          <select name="rating" required>
            <option value="">Choose a rating</option>
            ${[5, 4, 3, 2, 1].map(n => `<option value="${n}">${n} ${'★'.repeat(n)}</option>`).join('')}
          </select>
        </label>
        <label>Comment (optional) <textarea name="comment" maxlength="1000" rows="3"></textarea></label>
        <input class="hp" name="website" tabindex="-1" autocomplete="off" aria-hidden="true">
        <p id="revMsg" role="status"></p>
        <button class="primary">Post review</button>
      </form>`;
    dlg.showModal();
    loadReviews(h);

    $('revForm').addEventListener('submit', async e => {
      e.preventDefault();
      const f = e.target, msg = $('revMsg'), btn = f.querySelector('button');
      btn.disabled = true; msg.textContent = 'Posting…';
      try {
        const r = await fetch('api/reviews.php', {
          method: 'POST', headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            hostel_id: h.id, name: f.name.value, rating: +f.rating.value,
            comment: f.comment.value, website: f.website.value
          })
        });
        const d = await r.json();
        if (!r.ok) { msg.textContent = d.error || 'Could not post your review.'; }
        else if (d.pending) {
          f.reset(); msg.textContent = 'Thanks! Your review will appear once it has been approved.';
        } else {
          h.rating = d.rating; h.reviews_count = d.reviews_count;
          f.reset(); msg.textContent = 'Thanks! Your review is posted.';
          loadReviews(h); render(false);
        }
      } catch { msg.textContent = 'Network problem. Try again.'; }
      btn.disabled = false;
    });
  }

  // ---- events ----
  const staff = $('staff');
  document.addEventListener('click', e => { if (staff.open && !staff.contains(e.target)) staff.open = false; });
  document.addEventListener('keydown', e => { if (e.key === 'Escape') staff.open = false; });

  ['maxPrice', 'maxDist', 'minRating', 'roomType', 'sortBy', 'onlyAvail']
    .forEach(id => $(id).addEventListener('input', () => render(false)));

  $('place').addEventListener('change', e => {
    const p = places.find(p => p.id == e.target.value);
    if (p) setTarget(p.name, p.latitude, p.longitude);
  });

  $('list').addEventListener('click', e => {
    const open = e.target.closest('[data-open]');
    if (open) return openDetail(+open.dataset.open);
    const c = e.target.closest('.card'); if (c) select(+c.dataset.id);
  });
  $('list').addEventListener('keydown', e => {
    if (e.key === 'Enter' && !e.target.closest('button')) { const c = e.target.closest('.card'); if (c) select(+c.dataset.id); }
  });

  $('pickOnMap').addEventListener('click', e => {
    pickMode = !pickMode;
    e.target.classList.toggle('on', pickMode);
    e.target.textContent = pickMode ? 'Click the map…' : 'Pick on map';
  });
  map.on('click', e => {
    if (!pickMode) return;
    pickMode = false;
    $('pickOnMap').classList.remove('on'); $('pickOnMap').textContent = 'Pick on map';
    setTarget('your chosen spot', e.latlng.lat, e.latlng.lng);
  });

  $('useMyLocation').addEventListener('click', () => {
    if (!navigator.geolocation) return alert('Your browser does not support location.');
    navigator.geolocation.getCurrentPosition(
      p => setTarget('where you are', p.coords.latitude, p.coords.longitude),
      () => alert('Could not get your location. Allow location access or pick a spot on the map.'));
  });

  // ---- quick tour ----
  const steps = [
    { el: () => $('place').closest('label'),
      title: 'Choose where you are heading',
      text: 'Distances are measured from the university by default. Pick another place from the list, tap "Pick on map", or use your location.' },
    { el: () => document.querySelector('.panel'),
      title: 'Narrow down the list',
      text: 'Set your budget, maximum distance, minimum rating and room type. The results update instantly as you change them.' },
    { el: () => $('map'),
      title: 'Explore the map',
      text: 'Each pin is a hostel and the red dot is your destination. Use the layers button on the map for satellite view, and tap a pin to find its card.' },
    { el: () => document.querySelector('#list .card') || $('list'),
      title: 'Check details and get in touch',
      text: 'Cards show price, rating, walking time and rooms free. Use the button on a card for photos and reviews, or tap the phone number to call.' }
  ];
  const tour = $('tour');
  let ti = 0, focused = null;

  function showStep(i) {
    ti = i;
    const s = steps[i];
    if (focused) focused.classList.remove('tour-focus');
    focused = s.el();
    if (focused) {
      focused.classList.add('tour-focus');
      focused.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    $('tourStep').textContent = `Step ${i + 1} of ${steps.length}`;
    $('tourTitle').textContent = s.title;
    $('tourText').textContent = s.text;
    $('tourDots').innerHTML = steps.map((_, k) => `<i class="${k === i ? 'on' : ''}"></i>`).join('');
    $('tourBack').style.visibility = i ? 'visible' : 'hidden';
    $('tourNext').textContent = i === steps.length - 1 ? 'Got it' : 'Next';
  }
  function endTour() {
    tour.hidden = true;
    if (focused) focused.classList.remove('tour-focus');
    focused = null;
    try { localStorage.setItem('hf_tour_done', '1'); } catch {}
  }
  function startTour() { tour.hidden = false; showStep(0); }

  $('tourNext').addEventListener('click', () => ti < steps.length - 1 ? showStep(ti + 1) : endTour());
  $('tourBack').addEventListener('click', () => showStep(ti - 1));
  $('tourSkip').addEventListener('click', endTour);
  $('helpBtn').addEventListener('click', startTour);
  document.addEventListener('keydown', e => { if (e.key === 'Escape' && !tour.hidden) endTour(); });

  const loadData = (tries = 3) => fetch('api/data.php', { cache: 'no-store' })
    .then(r => r.json())
    .catch(err => tries > 1
      ? new Promise(res => setTimeout(res, 700)).then(() => loadData(tries - 1))
      : Promise.reject(err));
  loadData().then(d => {
    if (d.error) throw new Error(d.error);
    hostels = d.hostels; places = d.places;
    $('place').innerHTML = places.map(p => `<option value="${p.id}">${esc(p.name)}</option>`).join('');
    const def = places.find(p => p.is_default) || places[0];
    $('place').value = def.id;
    setTarget(def.name, def.latitude, def.longitude);
    // Show the tour automatically on a visitor's first visit only.
    try { if (!localStorage.getItem('hf_tour_done')) setTimeout(startTour, 900); } catch {}
  }).catch(err => {
    $('list').innerHTML = `<div class="empty">${esc(err.message)}</div>`;
    map.setView([0, 0], 2);
  });
})();
