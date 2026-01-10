<?php $title = 'Update aplicatie'; ?>
<h2>Update aplicatie</h2>

<?php if (!empty($remote_short)): ?>
    <div style="background-color: #fff3cd; color: #856404; padding: 15px; border: 1px solid #ffeeba; border-radius: 5px; margin-bottom: 20px;">
        <strong>Exista o noua versiune!</strong> Va rugam sa faceti update!
        <br>
        Versiune curenta: <strong><?php echo htmlspecialchars($local_short ?? ''); ?></strong><br>
        Versiune noua: <strong><?php echo htmlspecialchars($remote_short); ?></strong>
    </div>
<?php else: ?>
    <div style="background-color: #d1e7dd; color: #0f5132; padding: 15px; border: 1px solid #badbcc; border-radius: 5px; margin-bottom: 20px;">
        Sunteti la zi! Versiune: <strong><?php echo htmlspecialchars($local_short ?? ''); ?></strong>
    </div>
<?php endif; ?>

<p>Commit local: <strong><?php echo htmlspecialchars($local_short ?? (isset($local) ? substr($local, -7) : '')); ?></strong></p>
<?php if (!empty($remote_short)): ?>
  <p>Commit remote: <strong><?php echo htmlspecialchars($remote_short); ?></strong></p>
<?php endif; ?>
<pre><?php echo htmlspecialchars($status ?? ''); ?></pre>
<div class="card">Legenda git status: <br>## main = branchul curent; D = fișier șters; ?? = fișier neversionat</div>
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
<h3>Utilitare baza de date</h3>
<form method="post" action="/admin/update/clear_db" onsubmit="return confirm('Esti sigur ca vrei sa golesti baza de date? Aceasta actiune este ireversibila.')">
  <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
  <button type="submit" class="btn danger">Goleste baza de date</button>
  <small style="display:block;color:#555">Goleste tabelele de domeniu (parts, sets, colors, categories, inventar, istorice). Nu sterge utilizatorii sau migrarile.</small>
</form>
<form method="post" action="/admin/update/verify_schema" style="margin-top:10px;">
  <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
  <button type="submit">Verifica structura bazei de date</button>
  <small style="display:block;color:#555">Verifica existenta tabelelor si coloanelor esentiale.</small>
</form>
<?php if (!empty($clear_report)): ?>
  <div class="card">
    <strong>Baza de date golita.</strong>
    <div>Tabele afectate: <?php echo htmlspecialchars(implode(', ', $clear_report['cleared'] ?? [])); ?></div>
  </div>
<?php endif; ?>
<?php if (!empty($schema_report)): ?>
  <div class="card">
    <strong>Raport schema DB</strong>
    <?php if (!empty($schema_report['missing_tables'])): ?>
      <div style="color:#b91c1c">Tabele lipsa: <?php echo htmlspecialchars(implode(', ', $schema_report['missing_tables'])); ?></div>
    <?php endif; ?>
    <?php if (!empty($schema_report['missing_columns'])): ?>
      <div style="color:#b91c1c">Coloane lipsa:</div>
      <ul>
        <?php foreach ($schema_report['missing_columns'] as $mc): ?>
          <li><?php echo htmlspecialchars($mc['table']); ?>: <?php echo htmlspecialchars(implode(', ', $mc['columns'])); ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
    <?php if (!empty($schema_report['ok_tables'])): ?>
      <div style="color:#166534">OK: <?php echo htmlspecialchars(implode(', ', $schema_report['ok_tables'])); ?></div>
    <?php endif; ?>
    <?php if (empty($schema_report['missing_tables']) && empty($schema_report['missing_columns'])): ?>
      <div style="color:#166534">Structura este completa.</div>
    <?php endif; ?>
  </div>
<?php endif; ?>
