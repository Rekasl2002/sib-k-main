<?php
/**
 * File: app/Views/counselor/schedule/index.php
 * Halaman: Kalender Jadwal Konseling (Guru BK)
 * Template: Covex/Qovex (Bootstrap 5)
 *
 * Endpoint yang diharapkan tersedia:
 * GET  /counselor/schedule/events       -> JSON event FullCalendar
 * POST /counselor/schedule/reschedule   -> {id, start, end} untuk drag-drop/resize
 * GET  /counselor/sessions/{id}         -> detail sesi
 * GET  /counselor/schedule/create       -> form buat sesi (opsional, via ?start&end)
 *
 * Variabel opsional:
 * - $defaultView   : 'dayGridMonth' | 'timeGridWeek' | 'timeGridDay' (default month)
 * - $defaultDate   : 'YYYY-MM-DD' (default: today)
 * - $canDrag       : bool (default true) -> izinkan drag-drop/resize
 * - $filters       : array (mis. ['class_id' => 1, 'student_id' => 2, 'status' => 'scheduled'])
 */

$defaultView = $defaultView ?? 'dayGridMonth';
$defaultDate = $defaultDate ?? date('Y-m-d');
$canDrag     = array_key_exists('canDrag', get_defined_vars()) ? (bool)$canDrag : true;
$filters     = $filters ?? [];

$queryString = http_build_query(array_filter([
  'class_id'   => $filters['class_id']   ?? null,
  'student_id' => $filters['student_id'] ?? null,
  'status'     => $filters['status']     ?? null,
  'start'      => $filters['start']      ?? null,
  'end'        => $filters['end']        ?? null,
]));

$eventsUrl  = rtrim(base_url('counselor/schedule/events'), '/')
            . ($queryString ? ('?'.$queryString) : '');

$reschUrl   = rtrim(base_url('counselor/schedule/reschedule'), '/');
$createUrl  = rtrim(base_url('counselor/schedule/create'), '/');
$detailBase = rtrim(base_url('counselor/sessions/detail'), '/');

$tz = 'Asia/Jakarta';

// CSRF (jaga-jaga jika aktif)
$csrfEnabled = function_exists('csrf_token') && function_exists('csrf_hash');
$csrfName    = $csrfEnabled ? csrf_token() : '';
$csrfValue   = $csrfEnabled ? csrf_hash()  : '';
?>

<?= $this->extend('layouts/main'); ?>

<?= $this->section('content'); ?>
<div class="page-content">
  <div class="container-fluid">

    <!-- Header -->
    <div class="row">
      <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
          <h4 class="mb-sm-0">Kalender Jadwal</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= base_url('counselor/dashboard') ?>">Dashboard</a></li>
                    <li class="breadcrumb-item active">Kalender</li>
                </ol>
            </div>
        </div>
      </div>
    </div>

    <!-- Action bar sederhana -->
    <div class="card mb-3">
      <div class="card-body d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <div class="d-flex gap-2">
          <a class="btn btn-success" href="<?= $createUrl; ?>">
            <i class="bx bx-plus me-1"></i> Jadwal Baru
          </a>
          <a class="btn btn-outline-secondary" href="<?= base_url('counselor/sessions'); ?>">
            <i class="bx bx-list-ul me-1"></i> Lihat Daftar Sesi
          </a>
        </div>
        <div class="text-muted small">
          Zona waktu: <span class="fw-semibold"><?= esc($tz) ?></span>
        </div>
      </div>
    </div>

    <!-- Kalender -->
    <div class="card">
      <div class="card-body">
        <div id="calendar"></div>

        <div id="calendarFallback" class="alert alert-warning mt-3 d-none">
          <i class="bx bx-error-circle me-1"></i>
          Skrip kalender belum termuat. Pastikan koneksi ke CDN FullCalendar aktif
          atau pasang asset FullCalendar secara lokal.
        </div>
      </div>
    </div>

  </div>
</div>

<!-- FullCalendar via CDN (boleh pindah ke layout global bila mau) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>

<script>
(function(){
  const calendarEl = document.getElementById('calendar');
  const fallbackEl = document.getElementById('calendarFallback');

  if (!window.FullCalendar || !calendarEl) {
    if (fallbackEl) fallbackEl.classList.remove('d-none');
    return;
  }

  const canDrag   = <?= $canDrag ? 'true' : 'false' ?>;
  const eventsUrl = "<?= esc($eventsUrl) ?>";
  const reschUrl  = "<?= esc($reschUrl) ?>";
  const createUrl = "<?= esc($createUrl) ?>";
  const detailBase= "<?= esc($detailBase) ?>";
  const tz        = "<?= esc($tz) ?>";
  const defaultView = "<?= esc($defaultView) ?>";
  const defaultDate = "<?= esc($defaultDate) ?>";
  const csrfEnabled = <?= $csrfEnabled ? 'true' : 'false' ?>;
  const csrfName  = "<?= esc($csrfName) ?>";
  const csrfValue = "<?= esc($csrfValue) ?>";

  // Util: POST JSON (dengan CSRF bila ada)
  async function postJSON(url, payload) {
    const headers = {'Content-Type':'application/json'};
    if (csrfEnabled) headers[csrfName] = csrfValue;

    const res = await fetch(url, {
      method: 'POST',
      headers,
      body: JSON.stringify(payload)
    });
    if (!res.ok) throw new Error('Server error: ' + res.status);
    return res.json().catch(()=> ({}));
  }

  const calendar = new FullCalendar.Calendar(calendarEl, {
    timeZone: tz,
    initialView: defaultView,           // 'dayGridMonth' | 'timeGridWeek' | 'timeGridDay'
    initialDate: defaultDate,
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
    },
    navLinks: true,
    selectable: true,
    selectMirror: true,
    editable: canDrag,                  // drag-drop & resize
    droppable: false,
    dayMaxEvents: true,
    height: 'auto',
    firstDay: 1,                        // Mulai Senin

    // Ambil event dari backend
    events: {
      url: eventsUrl,
      failure: function() {
        if (fallbackEl) {
          fallbackEl.textContent = 'Gagal memuat event dari server.';
          fallbackEl.classList.remove('d-none');
        }
      }
    },

    // Klik event -> buka detail sesi
    eventClick: function(info){
      const id = info.event.extendedProps.session_id || info.event.id;
      if (id) {
        window.location.href = `${detailBase}/${encodeURIComponent(id)}`;
      }
    },

    // Pilih rentang -> ke form buat jadwal
    select: function(selection) {
      // Kirim start/end ke form create sebagai query
      const startIso = selection.startStr;
      const endIso   = selection.endStr;
      window.location.href = `${createUrl}?start=${encodeURIComponent(startIso)}&end=${encodeURIComponent(endIso)}`;
    },

    // Drag-drop / resize -> reschedule
    eventDrop: async function(info) {
      try {
        const id = info.event.extendedProps.session_id || info.event.id;
        await postJSON(reschUrl, {
          id: id,
          start: info.event.start.toISOString(),
          end: info.event.end ? info.event.end.toISOString() : null
        });
      } catch(e) {
        info.revert();
        alert('Gagal mengubah jadwal: ' + e.message);
      }
    },
    eventResize: async function(info) {
      try {
        const id = info.event.extendedProps.session_id || info.event.id;
        await postJSON(reschUrl, {
          id: id,
          start: info.event.start.toISOString(),
          end: info.event.end ? info.event.end.toISOString() : null
        });
      } catch(e) {
        info.revert();
        alert('Gagal mengubah durasi: ' + e.message);
      }
    },

    // Styling event berdasar status (opsional)
    eventDidMount: function(info){
      const status = (info.event.extendedProps.status || '').toLowerCase();
      if (status === 'canceled') {
        info.el.style.opacity = '0.6';
        info.el.style.textDecoration = 'line-through';
      } else if (status === 'pending') {
        info.el.style.borderStyle = 'dashed';
      }
    }
  });

  calendar.render();
})();
</script>

<?= $this->endSection(); ?>
