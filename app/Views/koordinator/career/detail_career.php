<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php $this->setData([
    'crumbs'  => [
        ['label' => 'Koordinator', 'url' => base_url('koordinator/dashboard')],
        ['label' => 'Info Karier dan Studi Lanjut', 'url' => site_url('koordinator/career-info')],
        ['label' => 'Detail Karier'],
    ],
]) ?>
<?= $this->include('career/career_detail_body') ?>
<?= $this->endSection() ?>
