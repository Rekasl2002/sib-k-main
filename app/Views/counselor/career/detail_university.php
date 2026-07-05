<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php $this->setData([
    'crumbs'  => [
        ['label' => 'Guru BK', 'url' => base_url('counselor/dashboard')],
        ['label' => 'Info Karier dan Studi Lanjut', 'url' => site_url('counselor/career-info?tab=universities')],
        ['label' => 'Detail Perguruan Tinggi'],
    ],
]) ?>
<?= $this->include('career/university_detail_body') ?>
<?= $this->endSection() ?>
