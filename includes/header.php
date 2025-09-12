<?php require_once __DIR__ . '/../lib/functions.php'; ?>

<!doctype html>
<html>

<head>
  <meta charset="utf-8">
  <title><?= h((require __DIR__ . '/../config.php')['app']['name']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      display: flex;
    }

    .sidebar {
      width: 300px;
      min-height: 100vh;
    }

    main {
      flex-grow: 1;
      padding: 20px;
    }
  </style>
</head>

<body class="bg-light">

  <!-- Sidebar -->
  <nav class="sidebar bg-dark text-white">
    <div class="p-3">
      <h4 class="text-white">Dashboard</h4>
      <ul class="nav flex-column mt-4">
        <li class="nav-item">
          <a class="nav-link text-white" href="/">Dashboard</a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white" href="<?= base_url('public/items.php') ?>">Items</a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white" href="<?= base_url('public/suppliers.php') ?>">Suppliers</a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white" href="<?= base_url('public/purchases.php') ?>">Barang Masuk</a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white" href="<?= base_url('public/issues.php') ?>">Barang Keluar</a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white" href="<?= base_url('public/adjustments.php') ?>">Penyesuaian</a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white" href="<?= base_url('public/reports/stock.php') ?>">Laporan Stok</a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white" href="<?= base_url('public/reports/movements.php') ?>">Riwayat Stok</a>
        </li>
      </ul>
      <hr class="text-white">
      <a class="btn btn-outline-danger w-100" href="<?= base_url('auth/logout.php') ?>">Logout</a>
    </div>
  </nav>

  <!-- Main Content -->
  <main>
    <?php include __DIR__ . '/flash.php'; ?>