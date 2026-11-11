<?php $title = 'Hostel Finder'; ?>
<?php
require __DIR__ . '/config.php';
$wa = preg_match('/^\d{8,15}$/', ADMIN_WHATSAPP) ? ADMIN_WHATSAPP : '';
$waText = "Hello, I would like a landlord account on Hostel Finder.\n\nName: \nHostel name: \nHostel location: \nPhone: \nPreferred username: \n\nPlease create my account. Thank you.";
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="referrer" content="strict-origin-when-cross-origin">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'><text y='20' font-size='20'>🏠</text></svg>">
  <title><?= htmlspecialchars($title) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400;12..96,600;12..96,800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
  <header class="top">
    <div class="topbar">
      <span class="brand">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M3 11l9-8 9 8" />
          <path d="M5 10v10h14V10" />
          <path d="M10 20v-6h4v6" />
        </svg>
        Hostel Finder
      </span>
      <div class="topactions">
        <button type="button" id="helpBtn" class="helpbtn">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="9" />
            <path d="M9.5 9.5a2.5 2.5 0 1 1 3.5 2.3c-.7.4-1 .9-1 1.7" />
            <path d="M12 17h.01" />
          </svg>
          <span>How to use</span>
        </button>
        <details class="staff" id="staff">
          <summary>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
              <path d="M12 5v14M5 12h14" />
            </svg>
            List your hostel
            <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M6 9l6 6 6-6" />
            </svg>
          </summary>
          <div class="staffpanel">
            <h2>Own a hostel?</h2>
            <p>Log in to add your listing, upload photos and keep your available rooms up to date for students.</p>
            <a class="cta" href="admin/">Landlord and staff login</a>
            <?php if ($wa): ?>
              <a class="wa-ask" href="https://wa.me/<?= $wa ?>?text=<?= rawurlencode($waText) ?>" target="_blank" rel="noopener">
                <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                  <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z" />
                </svg>
                <span>No account yet? <strong>Ask the administrator on WhatsApp</strong></span>
              </a>
            <?php else: ?>
              <p class="small">No account yet? Ask the administrator to create one for you.</p>
            <?php endif; ?>
          </div>
        </details>
      </div>
    </div>
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
  <!-- copy; 2026 Hostel Finder. All rights reserved. -->
  <div class="credit-bar">
    <span>&copy; <?= date('Y') ?> Hostel Finder. All rights reserved.</span>
    <span>Designed and developed by <strong>elisha</strong></span>
  </div>

  <div id="tour" class="tour" role="dialog" aria-label="Quick tour" aria-live="polite" hidden>
    <div class="tour-top"><span id="tourStep"></span><button type="button" id="tourSkip" class="linkbtn">Skip tour</button></div>
    <h2 id="tourTitle"></h2>
    <p id="tourText"></p>
    <div class="tour-nav">
      <div class="dots" id="tourDots" aria-hidden="true"></div>
      <div class="tour-btns"><button type="button" id="tourBack">Back</button><button type="button" id="tourNext" class="primary">Next</button></div>
    </div>
  </div>

  <dialog id="detail" aria-label="Hostel details">
    <form method="dialog"><button class="close" aria-label="Close">×</button></form>
    <div id="detailBody"></div>
  </dialog>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
  <script src="assets/js/app.js"></script>
</body>

</html>