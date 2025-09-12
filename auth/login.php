<?php
require_once __DIR__ . '/../lib/functions.php';
require_once __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim(post('username', ''));
  $password = (string) post('password', '');
  $stmt = db()->prepare("SELECT * FROM users WHERE username = :u LIMIT 1");
  $stmt->execute([':u' => $username]);
  $user = $stmt->fetch();
  if ($user && password_verify($password, $user['password_hash'])) {
    $_SESSION['user'] = ['id' => $user['id'], 'username' => $user['username']];
    flash('success', 'Login berhasil.');
    redirect('public/index.php');
  } else {
    flash('error', 'Username atau password salah.');
  }
}
include __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-center align-items-center" style="min-height: 100vh;">
  <div class="card" style="width: 400px;">
    <div class="card-body p-4">
      <h3 class="card-title text-center mb-4">Login</h3>
      <form method="post">
        <div class="mb-3">
          <label class="form-label">Username</label>
          <input name="username" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Masuk</button>
      </form>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>