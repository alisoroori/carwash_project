<?php
session_start();
require_once '../../includes/db.php';

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
    header('Location: ../../auth/login.php');
    exit();
}

$carwashes_query = "SELECT id, business_name AS name, city, district FROM carwash_profiles WHERE status = 'active'";
$carwashes = $conn->query($carwashes_query);

// Support partial embedding: if ?partial=1, return only the form fragment
$isPartial = isset($_GET['partial']) && $_GET['partial'] == '1';
?>

<?php
// If partial requested, output only the inner embedded booking fragment (form + scripts)
if ($isPartial) :
?>
    <div id="newReservationForm" class="p-6">
      <h3 class="text-xl font-bold mb-6">Yeni Rezervasyon OluÅŸtur</h3>

      <div id="embeddedBooking" class="space-y-6">
        <div>
          <label class="block text-sm font-bold text-gray-700 mb-2">Hizmet SeÃ§in</label>
          <div id="embeddedServices" class="space-y-2">
            <div class="text-sm muted">Hizmetler yÃ¼kleniyor...</div>
          </div>
        </div>

        <div>
          <label for="vehicle" class="block text-sm font-bold text-gray-700 mb-2">AraÃ§ SeÃ§in</label>
          <select id="vehicle" name="vehicle_id" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
            <option value="">AraÃ§ SeÃ§iniz</option>
          </select>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 lg:gap-6">
          <div>
            <label for="reservationDate" class="block text-sm font-bold text-gray-700 mb-2">Tarih</label>
            <input type="date" id="reservationDate" name="date" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500" data-validate="required">
          </div>
          <div>
            <label for="reservationTime" class="block text-sm font-bold text-gray-700 mb-2">Saat</label>
            <select id="reservationTime" name="time" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500" data-validate="required">
              <option value="">Saat seÃ§in</option>
            </select>
          </div>
        </div>

        <div>
          <label for="location" class="block text-sm font-bold text-gray-700 mb-2">Konum</label>
          <select id="location" name="carwash_id" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500" data-validate="required">
            <option value="">Konum SeÃ§iniz</option>
            <?php
            // Render server-side options for standalone usage
            if ($carwashes && $carwashes->num_rows > 0) {
              $carwashes->data_seek(0);
              while ($cw = $carwashes->fetch_assoc()) {
                $id = (int)$cw['id'];
                $name = htmlspecialchars($cw['name']);
                $label = $name . (!empty($cw['district']) ? ' â€” ' . htmlspecialchars($cw['district']) : '');
                echo "<option value=\"{$id}\">{$label}</option>\n";
              }
            }
            ?>
          </select>
        </div>

        <div>
          <label for="notes" class="block text-sm font-bold text-gray-700 mb-2">Ek Notlar (Ä°steÄŸe BaÄŸlÄ±)</label>
          <textarea id="notes" name="notes" rows="3" placeholder="Ã–zel istekleriniz veya notlarÄ±nÄ±z..." class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"></textarea>
        </div>

        <div id="embeddedMessage" class="text-sm"></div>

        <div class="flex flex-col sm:flex-row justify-end space-y-3 sm:space-y-0 sm:space-x-4">
          <button type="button" onclick="(function(){ const listView = document.getElementById('reservationListView'); if(listView){ document.getElementById('newReservationForm').classList.add('hidden'); listView.classList.remove('hidden'); } })()" class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg font-bold hover:bg-gray-50 transition-colors">Geri DÃ¶n</button>
          <button id="embeddedConfirm" type="button" class="gradient-bg text-white px-6 py-3 rounded-lg font-bold hover:shadow-lg transition-all">
            <i class="fas fa-calendar-plus mr-2"></i>Rezervasyon Yap
          </button>
        </div>
      </div>
    </div>

    <script>
    (function(){
      const API_SERVICES = '/carwash_project/backend/api/services/list.php';
      const API_CARWASHES = '/carwash_project/backend/api/carwashes/list.php';
      const API_CREATE = '/carwash_project/backend/api/bookings/create.php';

      const el = id => document.getElementById(id);

      async function loadCarwashes(){
        try{
          const resp = await fetch(API_CARWASHES,{
            cache: 'no-store',
            credentials: 'same-origin',
            headers: {
              'Accept': 'application/json',
              'X-Requested-With': 'XMLHttpRequest'
            }
          });
          const list = await resp.json();
          const loc = el('location');
          if(!loc) return;
          // preserve server-rendered options if any
          const existing = Array.from(loc.options).map(o=>o.value).filter(v=>v);
          list.forEach(cw=>{
            if(!existing.includes(String(cw.id))){ const o=document.createElement('option'); o.value=cw.id; o.textContent=cw.name + (cw.district?(' â€” '+cw.district):''); loc.appendChild(o); }
          });

          // preselect from querystring if provided
          const qs = new URLSearchParams(window.location.search);
          const pre = qs.get('carwash_id') || qs.get('carwash');
          if(pre) { loc.value = pre; if(loc.value) loadServicesForCarwash(loc.value); }
        }catch(e){ console.warn('Failed to load carwashes',e); }
      }

      async function loadServicesForCarwash(carwashId){
        try{
          const resp = await fetch(API_SERVICES + '?carwash_id=' + encodeURIComponent(carwashId),{
            cache: 'no-store',
            credentials: 'same-origin',
            headers: {
              'Accept': 'application/json',
              'X-Requested-With': 'XMLHttpRequest'
            }
          });
          const svcs = await resp.json();
          const container = el('embeddedServices');
          container.innerHTML = '';
          if(!Array.isArray(svcs) || svcs.length===0){ container.innerHTML = '<div class="muted">Hizmet bulunamadÄ±</div>'; return; }
          svcs.forEach(s=>{
            const d = document.createElement('div'); d.className='p-3 border rounded-lg flex justify-between items-center cursor-pointer'; d.innerHTML = `<div><div style=\"font-weight:600\">${s.name}</div><div class=\"small muted\">${s.description||''}</div></div><div style=\"font-weight:700\">â‚º${Number(s.price||0).toFixed(2)}</div>`;
            d.onclick = ()=>{ selectServiceEmbedded(s); };
            container.appendChild(d);
          });
        }catch(e){ console.warn('Failed to load services',e); }
      }

      let selectedService = null;
      function selectServiceEmbedded(s){ selectedService = s; document.querySelectorAll('#embeddedServices > div').forEach(n=>n.style.outline=''); const items = Array.from(document.querySelectorAll('#embeddedServices > div')); const idx = items.findIndex(it=>it.innerText.includes(s.name)); if(items[idx]) items[idx].style.outline='3px solid rgba(37,99,235,0.12)'; }

      function populateTimes(){ const timeSel = el('reservationTime'); if(!timeSel) return; timeSel.innerHTML=''; for(let h=9; h<18; h++){ ['00','30'].forEach(m=>{ const o=document.createElement('option'); o.value = `${String(h).padStart(2,'0')}:${m}`; o.textContent = `${String(h).padStart(2,'0')}:${m}`; timeSel.appendChild(o); }); } }

      async function submitEmbedded(){
        const msg = el('embeddedMessage');
        if(!selectedService){ msg.textContent='LÃ¼tfen hizmet seÃ§in'; msg.style.color='red'; return; }
        const carwashId = el('location').value; const date = el('reservationDate').value; const time = el('reservationTime').value; const notes = el('notes').value || '';
        if(!carwashId || !date || !time){ msg.textContent='LÃ¼tfen tÃ¼m zorunlu alanlarÄ± doldurun'; msg.style.color='red'; return; }
        const fd = new FormData(); fd.append('carwash_id', carwashId); fd.append('service_id', selectedService.id); fd.append('date', date); fd.append('time', time); fd.append('notes', notes);
        const btn = el('embeddedConfirm'); btn.disabled = true; btn.textContent = 'GÃ¶nderiliyor...';
        try{
          const r = await fetch(API_CREATE, {
            method: 'POST',
            body: fd,
            credentials: 'same-origin',
            headers: {
              'Accept': 'application/json',
              'X-Requested-With': 'XMLHttpRequest'
            }
          });
          const json = await r.json();
          if(json.success){ msg.textContent = 'Rezervasyon baÅŸarÄ±lÄ±. ID: '+json.booking_id; msg.style.color='green';
            // Optionally refresh reservations list by dispatching a custom event
            window.dispatchEvent(new CustomEvent('booking:created', { detail: json }));
            // hide form after short delay
            setTimeout(()=>{ const list = document.getElementById('reservationListView'); if(list){ document.getElementById('newReservationForm').classList.add('hidden'); list.classList.remove('hidden'); } }, 900);
          } else { msg.textContent = 'Hata: '+(json.errors?json.errors.join('\n'):(json.message||'Bilinmeyen hata')); msg.style.color='red'; }
        }catch(e){ console.error(e); msg.textContent = 'Sunucu hatasÄ±'; msg.style.color='red'; }
        finally{ btn.disabled = false; btn.textContent = 'Rezervasyon Yap'; }
      }

      document.addEventListener('DOMContentLoaded', function(){ populateTimes(); loadCarwashes(); const loc = el('location'); if(loc) loc.addEventListener('change', ()=>{ const v=el('location').value; if(v) loadServicesForCarwash(v); }); const btn = el('embeddedConfirm'); if(btn) btn.addEventListener('click', submitEmbedded);
        // Pre-fill from query params (city/district) if present
        const qs = new URLSearchParams(window.location.search);
        const city = qs.get('city'); const district = qs.get('district');
        if(city){ const cf = document.getElementById('cityFilter'); if(cf){ if(![...cf.options].some(o=>o.value===city)){ const o=document.createElement('option'); o.value=city; o.textContent=city; cf.appendChild(o); } cf.value=city; loadDistrictOptions && loadDistrictOptions(); }
          if(district){ const df = document.getElementById('districtFilter'); if(df){ if(![...df.options].some(o=>o.value===district)){ const od=document.createElement('option'); od.value=district; od.textContent=district; df.appendChild(od); } df.value=district; } }
        }
      });
    })();
    </script>

<?php
// End partial
    exit;
endif;

// Full standalone page falls back to the multi-step wizard but includes the embeddable booking UI above
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yeni Randevu - AquaTR</title>
    <link rel="stylesheet" href="<?php echo $base_url; ?>/dist/output.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <!-- Navbar -->
    <nav class="bg-white shadow-lg mb-8">
        <div class="max-w-6xl mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <a href="index.php" class="text-xl font-semibold text-blue-600">
                    <i class="fas fa-arrow-left"></i> Panele DÃ¶n
                </a>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto px-4">
        <h1 class="text-3xl font-bold text-gray-800 mb-8">Yeni Randevu OluÅŸtur</h1>

        <!-- Embed the same booking fragment so standalone and embedded use identical UI/JS -->
        <?php
        // Output the same fragment by including this file via partial internally
        $_GET['partial'] = '1';
        include __FILE__;
        ?>
    </div>
</body>
</html>



