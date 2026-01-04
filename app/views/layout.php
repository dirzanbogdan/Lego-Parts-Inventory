<?php $title = $title ?? 'Lego Parts Inventory'; ?>
<!doctype html>
<html lang="ro">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo htmlspecialchars($title); ?></title>
<link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body>
<header>
  <div class="container">
    <h1>Lego Parts Inventory</h1>
    <nav>
      <a href="/">Acasa</a>
      <a href="/parts">Piese</a>
      <a href="/inventory">Inventar</a>
      <a href="/sets">Seturi</a>
      <a href="/search">Cautare</a>
      <?php if (!empty($_SESSION['user'])): ?>
        <span class="user">Salut, <?php echo htmlspecialchars($_SESSION['user']['username']); ?></span>
        <?php if (($_SESSION['user']['role'] ?? 'user') === 'admin'): ?>
          <a href="/admin/update">Update</a>
          <a href="/admin/config">Config</a>
        <?php endif; ?>
        <a href="/logout">Logout</a>
      <?php else: ?>
        <a href="/login">Login</a>
        <a href="/register">Register</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<main class="container">
  <?php include __DIR__ . '/' . $view . '.php'; ?>
</main>
<script>
window.CSRF="<?php echo htmlspecialchars($csrf); ?>";
</script>
<script src="/assets/js/app.js"></script>
</body>
</html>
