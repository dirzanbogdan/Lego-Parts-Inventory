<?php $title = 'Configurare'; ?>
<h2>Configurare aplicatie</h2>
<?php if (!empty($_GET['ok'])): ?>
  <div class="card">Operatie finalizata: <?php echo htmlspecialchars($_GET['ok']); ?></div>
<?php endif; ?>
<h3>Seed culori de baza</h3>
<form method="post" action="/admin/config/seed_colors">
  <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
  <button type="submit">Seed culori</button>
  <p>Black, White, Red, Blue, Yellow, LBG, DBG, Green.</p>
</form>
<h3>Seed piese sample</h3>
<form method="post" action="/admin/config/seed_parts">
  <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
  <button type="submit">Seed piese</button>
  <p>3001, 3020, 3068b în categoria Basic.</p>
</form>
<h3>Seed seturi sample</h3>
<form method="post" action="/admin/config/seed_sets">
  <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
  <button type="submit">Seed seturi</button>
  <p>Starter Pack SP-001 cu piese sample.</p>
</form>
