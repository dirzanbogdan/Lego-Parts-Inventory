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
<form method="post" action="/admin/update/pull">
  <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
  <button type="submit">Ruleaza git pull</button>
</form>

<h3>Scanare Imagini Locale</h3>
<p>Aceasta optiune va scana folderul <code>public/images</code> si va actualiza baza de date cu link-urile catre imaginile gasite (Parts, Sets, Themes).</p>
<?php if (!empty($scan_log)): ?><pre><?php echo htmlspecialchars($scan_log); ?></pre><?php endif; ?>
<form method="post" action="/admin/update/scan-images">
  <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
  <button type="submit">Scaneaza si Linkeaza Imagini</button>
</form>

<div class="card mt-4">
  <div class="card-header">
    Verificare imagini
  </div>
  <div class="card-body">
    <ul class="nav nav-tabs" role="tablist">
      <li class="nav-item">
        <a class="nav-link active" data-bs-toggle="tab" href="#tab-sets" role="tab">Sets</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#tab-parts" role="tab">Parts</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#tab-themes" role="tab">Themes</a>
      </li>
    </ul>
    <div class="tab-content pt-3">
      <div class="tab-pane active" id="tab-sets" role="tabpanel">
        <form method="post" action="/admin/update/image-stats" class="mb-2">
          <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
          <input type="hidden" name="type" value="sets">
          <button type="submit" class="btn btn-primary btn-sm">Verifică</button>
        </form>
        <?php if (!empty($stats_sets)): ?>
          <div class="row">
            <div class="col">Total: <strong><?php echo (int)$stats_sets['total']; ?></strong></div>
            <div class="col">Local: <strong><?php echo (int)$stats_sets['local']; ?></strong></div>
            <div class="col">No-image: <strong><?php echo (int)$stats_sets['no_image']; ?></strong></div>
            <div class="col">CDN: <strong><?php echo (int)$stats_sets['cdn']; ?></strong></div>
          </div>
        <?php endif; ?>
      </div>
      <div class="tab-pane" id="tab-parts" role="tabpanel">
        <form method="post" action="/admin/update/image-stats" class="mb-2">
          <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
          <input type="hidden" name="type" value="parts">
          <button type="submit" class="btn btn-primary btn-sm">Verifică</button>
        </form>
        <?php if (!empty($stats_parts)): ?>
          <div class="row">
            <div class="col">Total: <strong><?php echo (int)$stats_parts['total']; ?></strong></div>
            <div class="col">Local: <strong><?php echo (int)$stats_parts['local']; ?></strong></div>
            <div class="col">No-image: <strong><?php echo (int)$stats_parts['no_image']; ?></strong></div>
            <div class="col">CDN: <strong><?php echo (int)$stats_parts['cdn']; ?></strong></div>
          </div>
        <?php endif; ?>
      </div>
      <div class="tab-pane" id="tab-themes" role="tabpanel">
        <form method="post" action="/admin/update/image-stats" class="mb-2">
          <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
          <input type="hidden" name="type" value="themes">
          <button type="submit" class="btn btn-primary btn-sm">Verifică</button>
        </form>
        <?php if (!empty($stats_themes)): ?>
          <div class="row">
            <div class="col">Total: <strong><?php echo (int)$stats_themes['total']; ?></strong></div>
            <div class="col">Local: <strong><?php echo (int)$stats_themes['local']; ?></strong></div>
            <div class="col">No-image: <strong><?php echo (int)$stats_themes['no_image']; ?></strong></div>
            <div class="col">CDN: <strong><?php echo (int)$stats_themes['cdn']; ?></strong></div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="card-footer">
    Verificarea se face la click pe buton
  </div>
  </div>
