<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php $this->setData([
    'crumbs'  => [
        ['label' => 'Guru BK', 'url' => base_url('counselor/dashboard')],
        ['label' => 'Info Karier dan Studi Lanjut', 'url' => site_url('counselor/career-info')],
        ['label' => 'Detail Karier'],
    ],
]) ?>
<?= $this->include('career/career_detail_body') ?>
<?= $this->endSection() ?>
