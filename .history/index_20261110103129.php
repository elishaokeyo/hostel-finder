<?php $title = 'Hostel Finder'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($title) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400;12..96,600;12..96,800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
  <header class="top">
    <h1>Find a hostel near <span id="targetName">your university</span></h1>
    <p>Distances update instantly when you change where you're heading.</p>
  </header>

  <main class="layout">
    <aside class="panel" aria-label="Filters">
      <label>Distance from
        <select id="place"></select>
      </label>
      <div class="row">
        <button type="button" id="pickOnMap">Pick on map</button>
        <button type="button" id="useMyLocation">Use my location</button>
      </div>

      <label>Max price: <output id="priceOut"></output>
        <input type="range" id="maxPrice" min="1000" max="20000" step="500" value="20000">
      </label>
      <label>Max distance: <output id="distOut"></output>
        <input type="range" id="maxDist" min="0.5" max="10" step="0.5" value="10">
      </label>
      <label>Minimum rating
        <select id="minRating">
          <option value="0">Any</option>
          <option value="3.5">3.5 and up</option>
          <option value="4">4.0 and up</option>
          <option value="4.5">4.5 and up</option>
        </select>
      </label>
      <label>Room type
        <select id="roomType">
          <option value="">All types</option>
          <option>Single</option>
          <option>Double</option>
          <option>Bedsitter</option>
          <option>Shared</option>
        </select>
      </label>
      <label>Sort by
        <select id="sortBy">
          <option value="distance">Nearest first</option>
          <option value="price">Lowest price</option>
          <option value="rating">Highest rated</option>
        </select>
      </label>
      <label class="check"><input type="checkbox" id="onlyAvail" checked> Only show hostels with rooms free</label>
    </aside>

    <section class="results">
      <div id="map" role="application" aria-label="Map of hostels"></div>
      <p id="count" aria-live="polite"></p>
      <div id="list"></div>
    </section>
  </main>

  <dialog id="detail" aria-label="Hostel details">
    <form method="dialog"><button class="close" aria-label="Close">×</button></form>
    <div id="detailBody"></div>
  </dialog>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
  <script src="assets/js/app.js"></script>
  <p style="text-align:center;padding:1rem"><a href="admin/">Staff login</a></p>
</body>

</html>