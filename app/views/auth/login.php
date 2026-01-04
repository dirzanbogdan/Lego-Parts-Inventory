<?php $title = 'Login'; ?>
<h2>Login</h2>
<?php if (!empty($error)): ?><div class="error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<form method="post" action="/login">
  <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
  <label>Username</label>
  <input type="text" name="username" required>
  <label>Parola</label>
  <input type="password" name="password" required>
  <button type="submit">Login</button>
</form>
