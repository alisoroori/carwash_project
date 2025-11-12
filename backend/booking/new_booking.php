<?php
/**
 * new_booking.php
 * Server-rendered booking page that preselects a carwash from ?carwash_id=...
 * Loads carwashes and services from DB and submits to backend API via AJAX.
 */

$bootstrapPath = __DIR__ . '/../includes/bootstrap.php';
$vendorAutoloadFallback = __DIR__ . '/../../vendor/autoload.php';

if (file_exists($bootstrapPath)) {
    require_once $bootstrapPath;
} elseif (file_exists($vendorAutoloadFallback)) {
    require_once $vendorAutoloadFallback;
} else {
    error_log('Bootstrap/autoload not found for new_booking.php');
    http_response_code(500);
    echo 'Application initialization failed.';
    exit;
}

use App\Classes\Session;
use App\Classes\Auth;

if (class_exists(Session::class) && method_exists(Session::class, 'start')) {
    Session::start();
} else {
    if (session_status() == PHP_SESSION_NONE) session_start();
}

Auth::requireRole('customer');

// Ensure CSRF token
if (empty($_SESSION['csrf_token'])) {
    try { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }
    catch (Exception $e) { $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32)); }
}

$selectedCarwashId = isset($_GET['carwash_id']) ? (int)$_GET['carwash_id'] : 0;
$selectedCity = isset($_GET['city']) ? trim($_GET['city']) : '';
$selectedDistrict = isset($_GET['district']) ? trim($_GET['district']) : '';

$carwashes = [];
$services = [];

$locations = []; // city => [districts]

// Try using App\Classes\Database if available, otherwise fallback to legacy DB helper
if (class_exists('\App\Classes\Database')) {
    try {
        $db = \App\Classes\Database::getInstance();
  // Use the same table/fields as the carwashes API (carwash_profiles) so data matches dashboard
  $carwashes = $db->fetchAll('SELECT id, business_name AS name, city, district FROM carwash_profiles ORDER BY business_name');
        if ($selectedCarwashId) {
            $services = $db->fetchAll('SELECT id, name, price FROM services WHERE carwash_id = :cw ORDER BY name', ['cw' => $selectedCarwashId]);
        }
    } catch (Exception $e) {
        error_log('DB error in new_booking: ' . $e->getMessage());
    }
} else {
    // Legacy fallback: try includes/db.php and mysqli
    $legacyDb = __DIR__ . '/../includes/db.php';
    if (file_exists($legacyDb)) {
        require_once $legacyDb;
        if (function_exists('getDBConnection')) {
            try {
                $conn = getDBConnection();
                // legacy fallback: read from carwash_profiles to match API
                $res = $conn->query("SELECT id, business_name AS name, city, district FROM carwash_profiles ORDER BY business_name");
                while ($r = $res->fetch_assoc()) $carwashes[] = $r;
                if ($selectedCarwashId) {
                    $stmt = $conn->prepare('SELECT id, name, price FROM services WHERE carwash_id = ? ORDER BY name');
                    $stmt->bind_param('i', $selectedCarwashId);
                    $stmt->execute();
                    $rs = $stmt->get_result();
                    while ($s = $rs->fetch_assoc()) $services[] = $s;
                }
            } catch (Exception $e) {
                error_log('Legacy DB error in new_booking: ' . $e->getMessage());
            }
        }
    }
}

// Basic HTML output (keeps consistent header/footer via includes)
$isPartial = isset($_GET['partial']) && $_GET['partial'] === '1';
$page_title = 'Yeni Rezervasyon';
if (!$isPartial) include __DIR__ . '/../includes/dashboard_header.php';
?>

<div class="max-w-3xl mx-auto p-6">
  <h1 class="text-2xl font-bold mb-4">Yeni Rezervasyon</h1>

  <?php
  // Build locations mapping from carwashes result so we can populate city/district selects
  foreach ($carwashes as $cw) {
      $city = trim($cw['city'] ?? '');
      $district = trim($cw['district'] ?? '');
      if ($city === '') continue;
      if (!isset($locations[$city])) $locations[$city] = [];
      if ($district !== '' && !in_array($district, $locations[$city], true)) $locations[$city][] = $district;
  }
  ?>

  <form id="newBookingForm" class="space-y-4" method="post" action="/carwash_project/backend/api/bookings/create.php">
    <label for="auto_label_76" class="sr-only">Csrf token</label><label for="auto_label_76" class="sr-only">Csrf token</label><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); "\>" id="auto_label_76">">
    <label for="auto_label_75" class="sr-only">Action</label><label for="auto_label_75" class="sr-only">Action</label><input type="hidden" name="action" value="create_reservation" id="auto_label_75">

    <div>
      <label for="citySelect" class="block text-sm font-bold mb-2">Åžehir</label>
      <select id="citySelect" name="city" class="w-full px-4 py-2 border rounded">
        <option value="\>-- Åžehir SeÃ§iniz --</option>
        <?php foreach ($locations as $cityName => $dists): ?>
          <option value="<?php echo htmlspecialchars($cityName); ?>" <?php echo ($selectedCity && $selectedCity === $cityName) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cityName); ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <label for="districtSelect" class="block text-sm font-bold mb-2">Mahalle / Ä°lÃ§e</label>
      <select id="districtSelect" name="district" class="w-full px-4 py-2 border rounded">
        <option value="\>-- Mahalle SeÃ§iniz --</option>
        <?php if ($selectedCity && isset($locations[$selectedCity])): foreach ($locations[$selectedCity] as $d): ?>
          <option value="<?php echo htmlspecialchars($d); ?>" <?php echo ($selectedDistrict && $selectedDistrict === $d) ? 'selected' : ''; ?>><?php echo htmlspecialchars($d); ?></option>
        <?php endforeach; endif; ?>
      </select>
    </div>

    <div>
      <label for="carwashSelect" class="block text-sm font-bold mb-2">Konum (Oto YÄ±kama)</label>
      <select id="carwashSelect" name="carwash_id" class="w-full px-4 py-2 border rounded">
        <option value="\>-- Konum SeÃ§iniz --</option>
        <?php foreach ($carwashes as $cw): ?>
          <option value="<?php echo (int)$cw['id']; ?>" data-city="<?php echo htmlspecialchars($cw['city'] ?? ''); ?>" data-district="<?php echo htmlspecialchars($cw['district'] ?? ''); ?>" <?php echo ($selectedCarwashId && $selectedCarwashId == $cw['id']) ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($cw['name'] . ' â€” ' . ($cw['district'] ?? '') . ' / ' . ($cw['city'] ?? '')); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <label for="serviceSelect" class="block text-sm font-bold mb-2">Hizmet</label>
      <select id="serviceSelect" name="service_id" class="w-full px-4 py-2 border rounded">
        <option value="\>-- Hizmet SeÃ§iniz --</option>
        <?php foreach ($services as $s): ?>
          <option value="<?php echo (int)$s['id']; ?>" data-price="<?php echo htmlspecialchars($s['price'] ?? ''); ?>"><?php echo htmlspecialchars($s['name'] . ' â€” ' . ($s['price'] ?? '')); ?></option>
        <?php endforeach; ?>
      </select>
      <div id="serviceHint" class="text-sm text-gray-600 mt-1"></div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
    <label for="dateInput" class="block text-sm font-bold mb-2">Tarih</label>
  <input id="dateInput" name="date" type="date" class="w-full px-4 py-2 border rounded" min="<?php echo date('Y-m-d'); ?>">
    </div>
    <div>
  <label for="timeSelect" class="block text-sm font-bold mb-2">Saat</label>
  <select id="timeSelect" name="time" class="w-full px-4 py-2 border rounded">
          <?php for ($h = 9; $h < 18; $h++): ?>
            <option value="<?php echo sprintf('%02d:00', $h); ?>"><?php echo sprintf('%02d:00', $h); ?></option>
            <option value="<?php echo sprintf('%02d:30', $h); ?>"><?php echo sprintf('%02d:30', $h); ?></option>
          <?php endfor; ?>
        </select>
      </div>
    </div>

    <div>
      <label for="notes" class="block text-sm font-bold mb-2">Notlar (isteÄŸe baÄŸlÄ±)</label>
      <textarea id="notes" name="notes" rows="3" class="w-full px-4 py-2 border rounded"></textarea>
    </div>

    <div class="flex justify-end gap-3">
      <a href="/carwash_project/backend/dashboard/Customer_Dashboard.php#reservations" class="px-4 py-2 border rounded">Geri</a>
      <button id="submitBtn" type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Rezervasyon Yap</button>
    </div>
  </form>

  <div id="result" class="mt-4"></div>
</div>

<script>
    (function(){
      const API_SERVICES = '/carwash_project/backend/api/services/list.php';
      const el = id => document.getElementById(id);

      // When carwash changes, update city/district selects and load services via API
      if (el('carwashSelect')) {
        el('carwashSelect').addEventListener('change', async function(){
          const val = this.value;
          // set city/district based on data attributes of selected option
          const opt = this.options[this.selectedIndex];
          if (opt) {
            const city = opt.getAttribute('data-city') || '';
            const district = opt.getAttribute('data-district') || '';
            if (el('citySelect')) el('citySelect').value = city;
            if (el('districtSelect')) {
              // repopulate districtSelect options based on selected city
              const cityVal = city;
              // attempt to use existing districtsByCity mapping from dashboard context if present
              if (window.districtsByCity && window.districtsByCity[cityVal]) {
                el('districtSelect').innerHTML = '<option value="\>-- Mahalle SeÃ§iniz --</option>' + window.districtsByCity[cityVal].map(d=>`<option value="${d}" ${d===district?'selected':''}>${d}</option>`).join('');
              } else if (district) {
                el('districtSelect').innerHTML = '<option value="\>-- Mahalle SeÃ§iniz --</option>' + `<option value="${district}" selected>${district}</option>`;
              }
            }

          }
          // load services
          if (el('serviceSelect')) el('serviceSelect').innerHTML = '<option value="\>-- Hizmet SeÃ§iniz --</option>';
          if (!val) return;
          try {
            const resp = await fetch(API_SERVICES + '?carwash_id=' + encodeURIComponent(val));
            const json = await resp.json();
            if (Array.isArray(json)) {
              json.forEach(s => {
                const o = document.createElement('option');
                // service id should be the submitted value
                o.value = s.id || '';
                if (s.price !== undefined) o.setAttribute('data-price', s.price);
                o.textContent = (s.name || ('Hizmet ' + (s.id||''))) + (s.price?(' â€” '+s.price):'');
                el('serviceSelect').appendChild(o);
              });
            }
          } catch (e) { console.warn('Failed to load services', e); }
        });
      }

      // When the city select changes, update districts list
      if (el('citySelect')) {
        el('citySelect').addEventListener('change', function(){
          const city = this.value;
          if (!el('districtSelect')) return;
          el('districtSelect').innerHTML = '<option value="\>-- Mahalle SeÃ§iniz --</option>';
          if (!city) return;
          if (window.districtsByCity && window.districtsByCity[city]) {
            window.districtsByCity[city].forEach(d => {
              const o = document.createElement('option'); o.value = d; o.textContent = d; el('districtSelect').appendChild(o);
            });
          }
        });
      }

      // If carwash was preselected, trigger change to populate dependent fields
      document.addEventListener('DOMContentLoaded', function(){
        if (el('carwashSelect') && el('carwashSelect').value) {
          el('carwashSelect').dispatchEvent(new Event('change'));
        } else if (el('citySelect') && el('citySelect').value) {
          el('citySelect').dispatchEvent(new Event('change'));
        }
      });
    })();
</script>

<?php if (!$isPartial) include __DIR__ . '/../includes/footer.php';

?>



