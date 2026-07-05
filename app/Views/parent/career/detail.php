<?php // app/Views/parent/career/detail.php — memakai badan detail bersama agar konsisten ?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php $this->setData([
    'backUrl' => site_url('parent/career'),
    'crumbs'  => [
        ['label' => 'Orang Tua', 'url' => base_url('parent/dashboard')],
        ['label' => 'Info Karier dan Studi Lanjut', 'url' => site_url('parent/career')],
        ['label' => 'Detail Karier'],
    ],
]) ?>
<?= $this->include('career/career_detail_body') ?>
<?= $this->endSection() ?>
