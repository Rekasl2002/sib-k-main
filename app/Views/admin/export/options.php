<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Export Data</title>
  <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
  <style>
    body{font-family: Inter, Arial, sans-serif; padding:20px;}
    .wrap{max-width:920px;margin:0 auto;}
    h1{font-size:22px;margin:0 0 12px;}
    .card{border:1px solid #e5e7eb;border-radius:10px;padding:16px;margin:12px 0;}
    .row{display:flex; gap:10px; align-items:flex-end; flex-wrap:wrap;}
    label{font-size:12px;color:#555;display:block;margin-bottom:4px;}
    input{padding:8px;border:1px solid #d1d5db;border-radius:8px;}
    .btn{padding:10px 14px;border-radius:10px;border:1px solid #0ea5a8;background:#0ea5a8;color:#fff;text-decoration:none;display:inline-block;}
    .btn:hover{opacity:.9}
  </style>
</head>
<body>
<div class="wrap">
  <h1>Export Data</h1>

  <div class="card">
    <h3>Export Siswa</h3>
    <a class="btn" href="<?= site_url('admin/export/students') ?>">Unduh XLSX</a>
  </div>

  <div class="card">
    <h3>Export Pelanggaran</h3>
    <form class="row" method="get" action="<?= site_url('admin/export/violations') ?>">
      <div>
        <label>Dari</label>
        <input type="date" name="from">
      </div>
      <div>
        <label>Sampai</label>
        <input type="date" name="to">
      </div>
      <div>
        <button class="btn" type="submit">Unduh XLSX</button>
      </div>
    </form>
  </div>

  <div class="card">
    <h3>Export Sesi Konseling</h3>
    <form class="row" method="get" action="<?= site_url('admin/export/sessions') ?>">
      <div>
        <label>Dari</label>
        <input type="date" name="from">
      </div>
      <div>
        <label>Sampai</label>
        <input type="date" name="to">
      </div>
      <div>
        <button class="btn" type="submit">Unduh XLSX</button>
      </div>
    </form>
  </div>
</div>
</body>
</html>
