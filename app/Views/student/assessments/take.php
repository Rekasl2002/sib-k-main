<!-- app/Views/student/assessments/take.php -->
<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php helper(['url','form']); ?>

<style>
/* Kotak gambar seragam, responsif tetap nyaman di mobile */
.asm-q-img-box{
  width: min(420px, 100%);
  height: 260px;
  display: flex; align-items: center; justify-content: center;
  background: #fff; border: 1px solid #e5e7eb; border-radius: .5rem;
  padding: .5rem;
}
.asm-q-img{ max-width: 100%; max-height: 100%; object-fit: contain; }
.asm-q-type{
  font-size:.75rem; background:#eef2ff; border:1px solid #dbeafe; color:#1e3a8a;
  padding:.15rem .5rem; border-radius:.375rem; display:inline-block; margin:.25rem 0;
}
/* Info bar di atas kanan */
.asm-infobar{ text-align:right }
.asm-kpi{ font-size:.85rem; color:#6b7280 }
.asm-kpi .v{ font-weight:600; color:#111827 }
.asm-progress{ height:8px; background:#e5e7eb; border-radius:999px; overflow:hidden }
.asm-progress .bar{ height:100%; width:0%; background:#4f46e5; transition: width .2s }
.asm-req{ color:#dc2626 }
</style>

<?php
// -------- Helpers aman (array/objek) --------
if (!function_exists('rowa')) {
  function rowa($r): array { return is_array($r) ? $r : (is_object($r) ? (array)$r : []); }
}
if (!function_exists('v')) {
  function v($r, $k, $d='') { $a = rowa($r); return esc($a[$k] ?? $d); }
}
if (!function_exists('json_to_list')) {
  function json_to_list($j): array {
    if (is_array($j)) return $j;
    if (is_string($j) && $j !== '') {
      $d = json_decode($j, true);
      if (is_array($d)) return array_values($d);
    }
    return [];
  }
}
if (!function_exists('type_label')) {
  function type_label(string $t): string {
    $map = [
      'Essay'          => 'Essay',
      'Multiple Choice'=> 'Pilihan Ganda',
      'True/False'     => 'Benar/Salah',
      'Checkbox'       => 'Pilihan Jamak',
      'Rating Scale'   => 'Skala Penilaian',
    ];
    return $map[$t] ?? $t;
  }
}

/**
 * Normalisasi sumber asset agar selalu absolute URL
 * - Biarkan URL http/https/ protocol-relative apa adanya
 * - Jika path relatif (uploads/..., public/uploads/...), jadikan base_url(...)
 * - Normalisasi backslash Windows ke slash
 */
if (!function_exists('asset_src')) {
  function asset_src(?string $u): string {
    $u = trim((string)$u);
    if ($u === '') return '';
    if (preg_match('~^(?:https?:)?//~i', $u)) return $u; // absolut
    $u = str_replace('\\', '/', $u);
    if (stripos($u, 'public/') === 0) $u = substr($u, 7);
    return base_url(ltrim($u, '/'));
  }
}

$asm    = rowa($assessment ?? []);
$qs     = isset($questions) && is_array($questions) ? $questions : (array)($questions ?? []);
$asmId  = (int)($asm['id'] ?? 0);
$durMin = (int)($asm['duration_minutes'] ?? 0);
$rid    = (int)($resultId ?? 0);

// -------- Ambil baseline waktu dari DB (fallback bila controller tidak mengirim remainingSeconds) --------
$startedAt        = null;
$timeSpentSeconds = 0;
if ($rid > 0) {
  try {
    $db  = \Config\Database::connect();
    $row = $db->table('assessment_results')
              ->select('started_at, time_spent_seconds')
              ->where('id', $rid)
              ->get()->getRowArray();
    $startedAt        = $row['started_at'] ?? null;
    $timeSpentSeconds = (int)($row['time_spent_seconds'] ?? 0);
  } catch (\Throwable $e) { /* ignore */ }
}

// Hitung sisa detik: PRIORITAS pakai $remainingSeconds dari controller.
// Fallback: gunakan max(elapsed_from_started_at, time_spent_seconds) agar tidak dobel.
$serverNow     = date('Y-m-d H:i:s');
$serverNowTs   = strtotime($serverNow);
$remainingSec  = null;

if ($durMin > 0) {
  if (isset($remainingSeconds) && $remainingSeconds !== null) {
    $remainingSec = max(0, (int)$remainingSeconds);
  } else {
    $elapsedFromStart = $startedAt ? max(0, $serverNowTs - strtotime($startedAt)) : 0;
    $elapsedSec       = max($elapsedFromStart, $timeSpentSeconds);
    $remainingSec     = max(0, ($durMin * 60) - $elapsedSec);
  }
}
$totalQuestions = count($qs);

// URL aksi submit dengan fallback jika named route tidak tersedia
$submitUrl = function_exists('route_to')
  ? route_to('student.assessments.submit', $asmId)
  : base_url('student/assessments/submit/'.$asmId);
?>

<div class="page-content">
  <div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between mb-3">
      <h4 class="mb-0">Kerjakan Asesmen</h4>
      <?php $toAvail = function_exists('route_to') ? route_to('student.assessments') : base_url('student/assessments'); ?>
      <a class="btn btn-light" href="<?= $toAvail ?>">Kembali</a>
    </div>

    <div class="card">
      <div class="card-body">
        <div class="row g-3 align-items-start justify-content-between">
          <div class="col-md">
            <h5 class="mb-1"><?= v($asm,'title','Tanpa Judul') ?></h5>
            <?php if (!empty($asm['description'])): ?>
              <p class="text-muted mb-2"><?= esc($asm['description']) ?></p>
            <?php endif; ?>
            <div class="small text-muted">
              Jenis: <span class="fw-semibold"><?= v($asm,'assessment_type','Assessment') ?></span>
              <?php if (!empty($asm['total_questions'])): ?>
                • Soal: <span class="fw-semibold"><?= (int)$asm['total_questions'] ?></span>
              <?php else: ?>
                • Soal: <span class="fw-semibold"><?= (int)$totalQuestions ?></span>
              <?php endif; ?>
              <?php if ($durMin > 0): ?>
                • Durasi: <span class="fw-semibold"><?= $durMin ?> menit</span>
              <?php else: ?>
                • Durasi: <span class="fw-semibold">Tanpa batas</span>
              <?php endif; ?>
            </div>
          </div>

          <div class="col-md-4 asm-infobar">
            <div class="asm-kpi">
              Progres: <span class="v"><span id="kpiAnswered">0</span>/<?= (int)$totalQuestions ?></span>
            </div>
            <div class="asm-progress mb-2" aria-hidden="true">
              <div class="bar" id="progressBar"></div>
            </div>

            <?php if ($durMin > 0): ?>
              <div class="asm-kpi">Sisa Waktu</div>
              <div id="countdown" class="fs-5 fw-semibold">--:--</div>
            <?php else: ?>
              <div class="asm-kpi">Waktu berjalan</div>
              <div id="timerUp" class="fs-5 fw-semibold">00:00</div>
            <?php endif; ?>
          </div>
        </div>

        <hr>

        <form id="assessmentForm"
              action="<?= $submitUrl ?>"
              method="post" novalidate>
          <?= csrf_field() ?>
          <?php if ($rid): ?>
            <input type="hidden" name="result_id" value="<?= $rid ?>">
          <?php endif; ?>

          <?php if (!empty($qs)): ?>
            <?php foreach ($qs as $i => $qRaw): ?>
              <?php $q = rowa($qRaw); $qid = (int)($q['id'] ?? 0); ?>
              <div class="mb-4" id="qwrap-<?= $qid ?>">
                <label class="fw-semibold mb-2 d-block">
                  <?= ($i+1) ?>.
                  <?= esc($q['question_text'] ?? 'Pertanyaan') ?>
                  <?php if (!empty($q['is_required'])): ?><span class="asm-req">*</span><?php endif; ?>
                </label>

                <?php
                  $type   = $q['question_type'] ?? 'Essay';
                  $opts   = json_to_list($q['options'] ?? []);
                  if ($type === 'True/False' && empty($opts)) $opts = ['True','False'];
                  if ($type === 'Rating Scale' && empty($opts)) $opts = ['1','2','3','4','5'];
                  $req    = !empty($q['is_required']);
                  $imgUrl = $q['image_url'] ?? ($q['image'] ?? null);
                  $imgSrc = asset_src($imgUrl);
                ?>

                <div class="asm-q-type">Tipe Soal: <?= esc(type_label($type)) ?></div>

                <?php if ($imgSrc !== ''): ?>
                  <div class="mb-2 asm-q-img-box">
                    <img src="<?= esc($imgSrc) ?>" alt="Gambar pertanyaan" class="asm-q-img">
                  </div>
                <?php endif; ?>

                <?php if ($type === 'Essay'): ?>
                  <textarea class="form-control asm-input"
                            name="q_<?= $qid ?>"
                            rows="4"
                            <?= $req ? 'data-required="1"' : '' ?>
                            data-qid="<?= $qid ?>"></textarea>

                <?php elseif (in_array($type, ['Multiple Choice','True/False','Rating Scale'], true)): ?>
                  <?php foreach ($opts as $k => $opt): ?>
                    <?php
                      $oid = 'q'.$qid.'_'.substr(md5((string)$opt), 0, 8);
                      $reqAttr = ($req && $k === 0) ? 'data-required="1"' : '';
                    ?>
                    <div class="form-check">
                      <input class="form-check-input asm-input"
                             type="radio"
                             name="q_<?= $qid ?>"
                             value="<?= esc($opt) ?>"
                             id="<?= $oid ?>"
                             <?= $reqAttr ?>
                             data-qid="<?= $qid ?>">
                      <label class="form-check-label" for="<?= $oid ?>"><?= esc($opt) ?></label>
                    </div>
                  <?php endforeach; ?>
                  <button class="btn btn-sm btn-outline-secondary mt-2" type="button"
                          onclick="clearRadio('q_<?= $qid ?>')">
                    Kosongkan pilihan
                  </button>

                <?php elseif ($type === 'Checkbox'): ?>
                  <?php foreach ($opts as $opt): ?>
                    <?php $oid = 'q'.$qid.'_'.substr(md5((string)$opt), 0, 8); ?>
                    <div class="form-check">
                      <input class="form-check-input asm-input"
                             type="checkbox"
                             name="q_<?= $qid ?>[]"
                             value="<?= esc($opt) ?>"
                             id="<?= $oid ?>"
                             <?= $req ? 'data-required="1"' : '' ?>
                             data-qid="<?= $qid ?>">
                      <label class="form-check-label" for="<?= $oid ?>"><?= esc($opt) ?></label>
                    </div>
                  <?php endforeach; ?>

                <?php else: ?>
                  <div class="alert alert-warning mb-0">
                    Tipe pertanyaan <strong><?= esc($type) ?></strong> belum didukung di halaman siswa.
                  </div>
                <?php endif; ?>

                <div class="invalid-feedback d-block" style="display:none" id="err-<?= $qid ?>">
                  Mohon lengkapi jawaban untuk pertanyaan ini.
                </div>
              </div>
            <?php endforeach; ?>

            <div class="d-flex align-items-center gap-2">
              <button id="submitBtn" class="btn btn-primary" type="submit">Kumpulkan</button>
              <button id="saveLocalBtn" class="btn btn-outline-secondary" type="button">Simpan Sementara (Local)</button>
              <span id="saveHint" class="text-muted small"></span>
            </div>
          <?php else: ?>
            <p class="text-muted mb-0">Belum ada pertanyaan pada asesmen ini.</p>
          <?php endif; ?>
        </form>
      </div>
    </div>
  </div>
</div>

<?php // =========================================================
// Scripts: countdown berbasis remainingSeconds (server) + heartbeat autosave waktu
// ========================================================= ?>
<script>
(function() {
  const asmId   = <?= json_encode($asmId) ?>;
  const rid     = <?= json_encode($rid) ?>;
  const durMin  = <?= json_encode($durMin) ?>;
  const remainingInit = <?= $remainingSec === null ? 'null' : (int)$remainingSec ?>; // detik
  const startedAt = <?= $startedAt ? json_encode($startedAt) : 'null' ?>; // info saja
  const spentBase = <?= (int)$timeSpentSeconds ?>; // sudah terekam di DB sebelum halaman ini
  const totalQ = <?= (int)$totalQuestions ?>;

  const form      = document.getElementById('assessmentForm');
  const submitBtn = document.getElementById('submitBtn');
  const saveBtn   = document.getElementById('saveLocalBtn');
  const saveHint  = document.getElementById('saveHint');
  const cdEl      = document.getElementById('countdown');
  const upEl      = document.getElementById('timerUp');
  const kpiAnswered = document.getElementById('kpiAnswered');
  const progressBar = document.getElementById('progressBar');

  const answersKey = 'asm_' + asmId + '_rid_' + rid + '_answers';
  const hbKey      = 'asm_' + asmId + '_rid_' + rid + '_hb_last_sent';   // epoch detik heartbeat terakhir
  const startedKey = 'asm_' + asmId + '_rid_' + rid + '_client_started'; // fallback client start

  // --------------- Tools kecil ---------------
  function qsAll(sel, root=document){ return Array.prototype.slice.call(root.querySelectorAll(sel)); }
  function setProgress(ans, tot) {
    const pct = tot > 0 ? Math.min(100, Math.round(ans*100/tot)) : 0;
    if (kpiAnswered) kpiAnswered.textContent = ans;
    if (progressBar) progressBar.style.width = pct + '%';
  }
  function scrollToEl(el){ if (!el) return; const r = el.getBoundingClientRect(); window.scrollBy({ top: r.top - 100, behavior: 'smooth' }); }

  // --------------- Anti double submit ---------------
  if (form) {
    form.addEventListener('submit', function(e) {
      // Validasi klien untuk required
      const bad = validateRequired();
      if (bad && bad.firstWrap) {
        e.preventDefault();
        scrollToEl(bad.firstWrap);
        return;
      }
      try { beaconHeartbeat(true); } catch(e) {}
      if (submitBtn) { submitBtn.disabled = true; submitBtn.innerText = 'Mengirim...'; }
      try { localStorage.removeItem(answersKey); } catch(e) {}
    });
  }

  // --------------- Countdown / Timer up ---------------
  let totalSec = null;
  let warned2min = false;

  if (durMin && durMin > 0) {
    // Gunakan sisa waktu dari server agar tidak reset
    totalSec = (typeof remainingInit === 'number') ? remainingInit : (durMin * 60);

    const tickDown = () => {
      if (cdEl) {
        const m = Math.floor(totalSec / 60);
        const s = totalSec % 60;
        cdEl.textContent = String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
      }
      if (!warned2min && totalSec <= 120) {
        warned2min = true;
        try { alert('Waktu tersisa kurang dari 2 menit. Mohon selesaikan dan kumpulkan jawaban.'); } catch(e) {}
      }
      if (totalSec <= 0) {
        freezeForm();
        if (form) form.submit();
        return;
      }
      totalSec -= 1;
      setTimeout(tickDown, 1000);
    };
    tickDown();
  } else {
    // Tanpa batas: tampilkan timer naik (client-side)
    let sec = 0;
    setInterval(() => {
      sec += 1;
      if (upEl) {
        const m = Math.floor(sec / 60);
        const s = sec % 60;
        upEl.textContent = String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
      }
    }, 1000);
  }

  function freezeForm() {
    const inputs = form ? form.querySelectorAll('input, textarea, button, select') : [];
    inputs.forEach(i => i.disabled = true);
  }

  // --------------- Draft jawaban (LocalStorage) ---------------
  function collectAnswers() {
    const data = {};
    if (!form) return data;
    const els = form.querySelectorAll('[name^="q_"]');
    els.forEach(el => {
      if (el.type === 'radio') {
        if (el.checked) data[el.name] = el.value;
      } else if (el.type === 'checkbox') {
        if (!data[el.name]) data[el.name] = [];
        if (el.checked) data[el.name].push(el.value);
      } else {
        data[el.name] = el.value;
      }
    });
    return data;
  }

  function applyAnswers(draft) {
    if (!form || !draft) return;
    Object.keys(draft).forEach(name => {
      const val = draft[name];
      const selector = `[name="${name}"]${name.endsWith('[]') ? ', [name="' + name + '[]"]' : ''}`;
      const els = form.querySelectorAll(selector);
      if (!els || els.length === 0) return;

      if (Array.isArray(val)) {
        els.forEach(el => { if (el.type === 'checkbox') el.checked = val.includes(el.value); });
      } else {
        els.forEach(el => {
          if (el.type === 'radio') {
            el.checked = (el.value === String(val));
          } else if (el.type !== 'checkbox') {
            el.value = val;
          }
        });
      }
    });
    updateProgress();
  }

  function saveLocal() {
    try {
      const data = collectAnswers();
      localStorage.setItem(answersKey, JSON.stringify(data));
      if (saveHint) {
        const dt = new Date();
        saveHint.textContent = 'Tersimpan di perangkat (' + dt.toLocaleTimeString() + ')';
        setTimeout(() => saveHint.textContent = '', 4000);
      }
    } catch (e) {
      if (saveHint) saveHint.textContent = 'Gagal menyimpan lokal.';
    }
  }

  if (saveBtn) saveBtn.addEventListener('click', saveLocal);
  if (form) form.addEventListener('change', () => {
    if (saveLocal.__t) clearTimeout(saveLocal.__t);
    saveLocal.__t = setTimeout(() => { saveLocal(); updateProgress(); }, 500);
  });

  // Pulihkan draft bila ada
  try {
    const raw = localStorage.getItem(answersKey);
    if (raw) {
      applyAnswers(JSON.parse(raw));
      if (saveHint) {
        saveHint.textContent = 'Draf jawaban dipulihkan dari perangkat.';
        setTimeout(() => saveHint.textContent = '', 4000);
      }
    } else {
      updateProgress(); // set awal
    }
  } catch (e) { updateProgress(); }

  // --------------- Heartbeat waktu ke server ---------------
  // Endpoint: /api/assessments/{asmId}/autosave (lihat Routes.php)
  // Mengirim incremental elapsed_seconds sejak heartbeat terakhir.
  const apiUrl = <?= json_encode(base_url('api/assessments')) ?> + '/' + asmId + '/autosave';

  if (!localStorage.getItem(startedKey)) {
    localStorage.setItem(startedKey, String(Math.floor(Date.now()/1000)));
  }
  function nowSec() { return Math.floor(Date.now()/1000); }

  function calcDeltaToSend() {
    const last = parseInt(localStorage.getItem(hbKey) || '0', 10);
    const cur = nowSec();
    const delta = cur - (isFinite(last) ? last : 0);
    return Math.max(0, Math.min(delta, 600)); // clamp 0..600
  }

  async function heartbeat(includeAnswers=false) {
    if (!rid) return;
    const delta = calcDeltaToSend();
    if (delta <= 0) return;

    const payload = { result_id: rid, elapsed_seconds: delta };
    if (includeAnswers) { try { payload.answers = collectAnswers(); } catch(e) {} }

    try {
      await fetch(apiUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify(payload),
        credentials: 'same-origin'
      });
      localStorage.setItem(hbKey, String(nowSec()));
    } catch (e) {
      // next tick try again
      localStorage.setItem(hbKey, String(nowSec() - 1));
    }
  }

  function beaconHeartbeat(includeAnswers=false) {
    if (!rid) return;
    const delta = calcDeltaToSend();
    if (delta <= 0) return;

    const payload = { result_id: rid, elapsed_seconds: delta };
    if (includeAnswers) { try { payload.answers = collectAnswers(); } catch(e) {} }
    try {
      const blob = new Blob([JSON.stringify(payload)], { type: 'application/json' });
      navigator.sendBeacon(apiUrl, blob);
      localStorage.setItem(hbKey, String(nowSec()));
    } catch(e) {}
  }

  // Jadwalkan heartbeat periodik
  let t30 = setInterval(() => heartbeat(false), 30000);
  let t60 = setInterval(() => heartbeat(true), 60000);

  document.addEventListener('visibilitychange', () => {
    if (document.hidden) { beaconHeartbeat(true); saveLocal(); }
  });
  window.addEventListener('beforeunload', () => {
    beaconHeartbeat(true);
    saveLocal();
  });

  // --------------- Progress & Validasi ---------------
  function countAnswered() {
    if (!form) return 0;
    let answered = 0;
    // Kelompok per pertanyaan berdasarkan data-qid
    const qWraps = qsAll('[id^="qwrap-"]');
    qWraps.forEach(w => {
      const qid = (w.id || '').replace('qwrap-','');
      if (!qid) return;
      const inputs = qsAll('[data-qid="'+qid+'"]', w);
      if (inputs.length === 0) return;
      let ok = false;
      inputs.forEach(el => {
        if (el.tagName === 'TEXTAREA') {
          if (el.value && el.value.trim() !== '') ok = true;
        } else if (el.type === 'radio') {
          if (el.checked) ok = true;
        } else if (el.type === 'checkbox') {
          // akan dihitung ok jika minimal 1 checked pada grup ini
        }
      });
      if (!ok) {
        // khusus checkbox: cek di akhir
        const checks = inputs.filter(el => el.type === 'checkbox');
        if (checks.length > 0) {
          ok = checks.some(el => el.checked);
        }
      }
      if (ok) answered++;
    });
    return answered;
  }

  function updateProgress() {
    const ans = countAnswered();
    setProgress(ans, totalQ);
  }

  function setError(qid, show) {
    const el = document.getElementById('err-'+qid);
    const wrap = document.getElementById('qwrap-'+qid);
    if (el) el.style.display = show ? 'block' : 'none';
    if (wrap) wrap.classList.toggle('has-error', !!show);
  }

  // Validasi required (Essay harus terisi, Radio minimal 1, Checkbox minimal 1)
  function validateRequired() {
    const bad = [];
    const reqInputs = qsAll('[data-required="1"]');
    const seen = new Set(); // per qid
    reqInputs.forEach(el => {
      const qid = el.getAttribute('data-qid');
      if (!qid || seen.has(qid)) return;
      seen.add(qid);
      const wrap = document.getElementById('qwrap-'+qid);
      const group = qsAll('[data-qid="'+qid+'"]', wrap || document);
      let ok = false;

      // Essay
      const text = group.find(x => x.tagName === 'TEXTAREA');
      if (text) ok = !!(text.value && text.value.trim() !== '');

      // Radio / Rating / TrueFalse
      if (!ok) {
        const radios = group.filter(x => x.type === 'radio');
        if (radios.length > 0) ok = radios.some(x => x.checked);
      }

      // Checkbox
      if (!ok) {
        const checks = group.filter(x => x.type === 'checkbox');
        if (checks.length > 0) ok = checks.some(x => x.checked);
      }

      setError(qid, !ok);
      if (!ok) bad.push({ qid: qid, firstWrap: wrap });
    });

    return bad.length ? { list: bad, firstWrap: bad[0]?.firstWrap || null } : null;
  }

  // Tombol "Kosongkan pilihan" untuk Radio
  window.clearRadio = function(name) {
    const els = qsAll('input[type="radio"][name="'+name+'"]');
    els.forEach(e => e.checked = false);
    updateProgress();
  };

})();
</script>

<?= $this->endSection() ?>
