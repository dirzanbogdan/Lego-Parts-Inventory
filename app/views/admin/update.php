<?php $title = 'Update aplicatie'; ?>
<h2>Update aplicatie</h2>
<p>Commit local: <strong><?php echo htmlspecialchars($local ?? ''); ?></strong></p>
<p>Commit remote: <strong><?php echo htmlspecialchars($remote ?? ''); ?></strong></p>
<pre><?php echo htmlspecialchars($status ?? ''); ?></pre>
<?php if (!empty($before) || !empty($after)): ?>
  <p>Inainte: <?php echo htmlspecialchars($before ?? ''); ?> • Dupa: <?php echo htmlspecialchars($after ?? ''); ?></p>
  <?php if (!empty($pull_log)): ?><pre><?php echo htmlspecialchars($pull_log); ?></pre><?php endif; ?>
<?php endif; ?>
<h3>Backup DB</h3>
<?php if (!empty($last_backup)): ?><p>Ultimul backup: <a href="<?php echo htmlspecialchars($last_backup); ?>" target="_blank"><?php echo htmlspecialchars($last_backup); ?></a></p><?php endif; ?>
<form method="post" action="/admin/update/backup">
  <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
  <button type="submit">Genereaza backup</button>
</form>
<h3>Update din Git</h3>
<form method="post" action="/admin/update/apply">
  <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
  <button type="submit">Ruleaza git pull + migrari</button>
</form>
