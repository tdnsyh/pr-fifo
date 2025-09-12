<?php
require_once __DIR__ . '/../lib/functions.php';
require_once __DIR__ . '/../db.php';

$cfg = require __DIR__ . '/../config.php';
if (!($cfg['app']['allow_registration'] ?? true)) {
  flash('error', 'Registrasi dimatikan oleh admin.');
  redirect('auth/login.php');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim(post('username', ''));
  $password = (string) post('password', '');
  $confirm = (string) post('confirm', '');

  // Validasi sederhana
  if ($username === '')
    $errors[] = 'Username wajib diisi.';
  if (strlen($password) < 6)
    $errors[] = 'Password minimal 6 karakter.';
  if ($password !== $confirm)
    $errors[] = 'Konfirmasi password tidak cocok.';

  if (!$errors) {
    try {
      // Cek duplikat
      $stmt = db()->prepare("SELECT 1 FROM users WHERE username = :u LIMIT 1");
      $stmt->execute([':u' => $username]);
      if ($stmt->fetch()) {
        $errors[] = 'Username sudah dipakai.';
      } else {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $ins = db()->prepare("INSERT INTO users (username, password_hash) VALUES (:u, :h)");
        $ins->execute([':u' => $username, ':h' => $hash]);

        flash('success', 'Registrasi berhasil. Silakan login.');
        redirect('auth/login.php');
      }
    } catch (Throwable $e) {
      $errors[] = 'Terjadi kesalahan: ' . $e->getMessage();
    }
  }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-center align-items-center" style="min-height: 100vh;">
  <div class="card w-100" style="max-width: 520px;">
    <div class="card-body p-4">
      <h3 class="card-title text-center mb-4">Register</h3>

      <?php if ($errors): ?>
        <div class="alert alert-danger" role="alert">
          <?php foreach ($errors as $e): ?>
            <div><?= h($e) ?></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form method="post" novalidate>
        <div class="mb-3">
          <label class="form-label">Username</label>
          <input name="username"
            class="form-control<?= in_array('Username wajib diisi.', $errors) || in_array('Username sudah dipakai.', $errors) ? ' is-invalid' : '' ?>"
            required value="<?= h(post('username', '')) ?>">
          <?php if (in_array('Username wajib diisi.', $errors)): ?>
            <div class="invalid-feedback">Username wajib diisi.</div>
          <?php elseif (in_array('Username sudah dipakai.', $errors)): ?>
            <div class="invalid-feedback">Username sudah dipakai.</div>
          <?php endif; ?>
        </div>

        <div class="mb-3">
          <label class="form-label">Password</label>
          <input type="password" name="password"
            class="form-control<?= in_array('Password minimal 6 karakter.', $errors) ? ' is-invalid' : '' ?>" required
            minlength="6">
          <?php if (in_array('Password minimal 6 karakter.', $errors)): ?>
            <div class="invalid-feedback">Password minimal 6 karakter.</div>
          <?php endif; ?>
        </div>

        <div class="mb-3">
          <label class="form-label">Konfirmasi Password</label>
          <input type="password" name="confirm"
            class="form-control<?= in_array('Konfirmasi password tidak cocok.', $errors) ? ' is-invalid' : '' ?>"
            required minlength="6">
          <?php if (in_array('Konfirmasi password tidak cocok.', $errors)): ?>
            <div class="invalid-feedback">Konfirmasi password tidak cocok.</div>
          <?php endif; ?>
        </div>

        <button type="submit" class="btn btn-primary w-100">Daftar</button>
      </form>

      <p class="text-center mt-3 mb-0">
        Sudah punya akun?
        <a href="<?= base_url('auth/login.php') ?>">Login</a>
      </p>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>