<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php $this->setData([
    'crumbs'  => [
        ['label' => 'Koordinator', 'url' => base_url('koordinator/dashboard')],
        ['label' => 'Info Karier dan Studi Lanjut', 'url' => site_url('koordinator/career-info?tab=universities')],
        ['label' => 'Detail Perguruan Tinggi'],
    ],
]) ?>
<?= $this->include('career/university_detail_body') ?>
<?= $this->endSection() ?>
