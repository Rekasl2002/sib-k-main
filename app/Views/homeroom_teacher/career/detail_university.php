<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php $this->setData([
    'crumbs'  => [
        ['label' => 'Wali Kelas', 'url' => base_url('homeroom/dashboard')],
        ['label' => 'Info Karier dan Studi Lanjut', 'url' => site_url('homeroom/career-info?tab=universities')],
        ['label' => 'Detail Perguruan Tinggi'],
    ],
]) ?>
<?= $this->include('career/university_detail_body') ?>
<?= $this->endSection() ?>
