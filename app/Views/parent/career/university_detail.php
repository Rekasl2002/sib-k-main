<?php // app/Views/parent/career/university_detail.php — memakai badan detail bersama ?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php $this->setData([
    'backUrl' => site_url('parent/career?tab=universities'),
    'crumbs'  => [
        ['label' => 'Orang Tua', 'url' => base_url('parent/dashboard')],
        ['label' => 'Info Karier dan Studi Lanjut', 'url' => site_url('parent/career?tab=universities')],
        ['label' => 'Detail Perguruan Tinggi'],
    ],
]) ?>
<?= $this->include('career/university_detail_body') ?>
<?= $this->endSection() ?>
