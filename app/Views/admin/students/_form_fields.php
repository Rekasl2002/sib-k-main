<div class="row">
    <div class="col-md-6">
        <div class="mb-3">
            <label for="nisn" class="form-label">
                NISN <span class="text-danger">*</span>
            </label>
            <input type="text" class="form-control" name="nisn" 
                   value="<?= old('nisn') ?>" placeholder="10 digit" required>
            <small class="text-muted">Nomor Induk Siswa Nasional (tepat 10 digit)</small>
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label for="nis" class="form-label">
                NIS <span class="text-danger">*</span>
            </label>
            <input type="text" class="form-control" name="nis" 
                   value="<?= old('nis') ?>" placeholder="4-20 karakter" required>
            <small class="text-muted">Nomor Induk Siswa</small>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="mb-3">
            <label for="gender" class="form-label">
                Jenis Kelamin <span class="text-danger">*</span>
            </label>
            <select class="form-select" name="gender" required>
                <option value="">Pilih</option><option value="L" >Laki-laki</option><option value="P" >Perempuan</option>            </select>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <label for="birth_place" class="form-label">Tempat Lahir</label>
            <input type="text" class="form-control" name="birth_place" 
                   value="<?= old('birth_place') ?>">
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <label for="birth_date" class="form-label">Tanggal Lahir</label>
            <input type="date" class="form-control" name="birth_date" 
                   value="<?= old('birth_date') ?>">
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="mb-3">
            <label for="religion" class="form-label">Agama</label>
            <select class="form-select" name="religion">
                <option value="">Pilih</option><option value="Islam" >Islam</option><option value="Kristen" >Kristen</option><option value="Katolik" >Katolik</option><option value="Hindu" >Hindu</option><option value="Buddha" >Buddha</option><option value="Konghucu" >Konghucu</option>            </select>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <label for="class_id" class="form-label">Kelas</label>
            <select class="form-select" name="class_id">
                <option value="">Pilih Kelas</option><option value="1" >X - X-IPA-1</option><option value="2" >X - X-IPA-2</option><option value="12" >X - X-IPA-A-Ganjil-2025</option><option value="3" >X - X-IPS-1</option><option value="4" >X - X-IPS-2</option><option value="5" >XI - XI-IPA-1</option><option value="6" >XI - XI-IPA-2</option><option value="7" >XI - XI-IPS-1</option><option value="8" >XII - XII-IPA-1</option><option value="9" >XII - XII-IPA-2</option><option value="10" >XII - XII-IPS-1</option>            </select>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <label for="admission_date" class="form-label">Tanggal Masuk</label>
            <input type="date" class="form-control" name="admission_date" 
                   value="<?= old('admission_date') ?? date('Y-m-d') ?>">
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="mb-3">
            <label for="address" class="form-label">Alamat Lengkap</label>
            <textarea class="form-control" name="address" rows="3"><?= old('address') ?></textarea>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <label for="parent_id" class="form-label">Orang Tua/Wali</label>
            <select class="form-select" name="parent_id">
                <option value="">Pilih Orang Tua</option><option value="74" >Alfa Ramian Yuda</option><option value="65" >Arial</option><option value="68" >Bold</option><option value="10" >Dewi Lestari</option><option value="9" >Suryanto</option><option value="26" >test</option><option value="57" >Testorangtua1</option><option value="59" >Testorangtua2</option>            </select>
        </div>
        <div class="mb-3">
            <label for="status" class="form-label">Status</label>
            <select class="form-select" name="status"><option value="Aktif" selected>Aktif</option><option value="Alumni" >Alumni</option><option value="Pindah" >Pindah</option><option value="Keluar" >Keluar</option>            </select>
        </div>
    </div>
</div>