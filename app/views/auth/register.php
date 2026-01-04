<?php $title = 'Register'; ?>
<h2>Inregistrare</h2>
<?php if (!empty($error)): ?><div class="error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<form method="post" action="/register">
  <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
  <label>Username</label>
  <input type="text" name="username" required>
  <label>Parola</label>
  <input type="password" name="password" required>
  <button type="submit">Creeaza</button>
</form>
