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
    <?php if (!empty($debug_file)): ?>
        <div class="alert alert-success">
            Fisier debug (<?= htmlspecialchars($debug_type ?? '') ?>) generat cu succes: 
            <a href="<?= htmlspecialchars($debug_file) ?>" class="alert-link" target="_blank">Descarca CSV</a>
        </div>
    <?php endif; ?>
    <?php $activeTab = $active_tab ?? 'sets'; ?>
    <ul class="nav nav-tabs" role="tablist">
      <li class="nav-item">
        <a class="nav-link <?php echo $activeTab === 'sets' ? 'active' : ''; ?>" data-bs-toggle="tab" href="#tab-sets" role="tab">Sets</a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?php echo $activeTab === 'parts' ? 'active' : ''; ?>" data-bs-toggle="tab" href="#tab-parts" role="tab">Parts</a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?php echo $activeTab === 'themes' ? 'active' : ''; ?>" data-bs-toggle="tab" href="#tab-themes" role="tab">Themes</a>
      </li>
    </ul>
    <div class="tab-content pt-3">
      <div class="tab-pane <?php echo $activeTab === 'sets' ? 'show active' : ''; ?>" id="tab-sets" role="tabpanel">
        <form method="post" action="/admin/update/image-stats" class="mb-2">
          <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
          <input type="hidden" name="type" value="sets">
          <button type="submit" class="btn btn-primary btn-sm">Verifică</button>
        </form>
        <?php if (!empty($stats_sets)): ?>
          <div class="row">
            <div class="col">Total: <strong><?php echo (int)$stats_sets['total']; ?></strong></div>
            <div class="col">
              Local: <strong><?php echo (int)$stats_sets['local']; ?></strong>
              <form method="post" action="/admin/update/image-stats" class="d-inline">
                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
                <input type="hidden" name="type" value="sets">
                <input type="hidden" name="segment" value="local">
                <button type="submit" class="btn btn-link btn-sm p-0 align-baseline">Detalii</button>
              </form>
            </div>
            <div class="col">
              No-image: <strong><?php echo (int)$stats_sets['no_image']; ?></strong>
              <form method="post" action="/admin/update/image-stats" class="d-inline">
                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
                <input type="hidden" name="type" value="sets">
                <input type="hidden" name="segment" value="no_image">
                <button type="submit" class="btn btn-link btn-sm p-0 align-baseline">Detalii</button>
              </form>
            </div>
            <div class="col">CDN: <strong><?php echo (int)$stats_sets['cdn']; ?></strong></div>
          </div>
          <div class="mt-3 d-flex gap-2">
            <form method="post" action="/admin/update/export-debug">
              <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
              <input type="hidden" name="type" value="sets">
              <button type="submit" class="btn btn-warning btn-sm">Export Debug CSV (Lipsa Local)</button>
            </form>
            <form method="post" action="/admin/update/download-images">
              <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
              <input type="hidden" name="type" value="sets">
              <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Download images from CDN? This may take a while.');">Download Missing Images</button>
            </form>
          </div>
          <?php if (!empty($latest_debug_sets)): ?>
            <div class="mt-2">
              <a href="<?php echo htmlspecialchars($latest_debug_sets); ?>" target="_blank">Descarcă ultimul CSV Sets</a>
            </div>
          <?php endif; ?>
        <?php endif; ?>
      </div>
      <div class="tab-pane <?php echo $activeTab === 'parts' ? 'show active' : ''; ?>" id="tab-parts" role="tabpanel">
        <form method="post" action="/admin/update/image-stats" class="mb-2">
          <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
          <input type="hidden" name="type" value="parts">
          <button type="submit" class="btn btn-primary btn-sm">Verifică</button>
        </form>
        <?php if (!empty($stats_parts)): ?>
          <div class="row">
            <div class="col">Total: <strong><?php echo (int)$stats_parts['total']; ?></strong></div>
            <div class="col">
              Local: <strong><?php echo (int)$stats_parts['local']; ?></strong>
              <form method="post" action="/admin/update/image-stats" class="d-inline">
                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
                <input type="hidden" name="type" value="parts">
                <input type="hidden" name="segment" value="local">
                <button type="submit" class="btn btn-link btn-sm p-0 align-baseline">Detalii</button>
              </form>
            </div>
            <div class="col">
              No-image: <strong><?php echo (int)$stats_parts['no_image']; ?></strong>
              <form method="post" action="/admin/update/image-stats" class="d-inline">
                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
                <input type="hidden" name="type" value="parts">
                <input type="hidden" name="segment" value="no_image">
                <button type="submit" class="btn btn-link btn-sm p-0 align-baseline">Detalii</button>
              </form>
            </div>
            <div class="col">CDN: <strong><?php echo (int)$stats_parts['cdn']; ?></strong></div>
          </div>
          <div class="mt-3 d-flex gap-2">
            <form method="post" action="/admin/update/export-debug">
              <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
              <input type="hidden" name="type" value="parts">
              <button type="submit" class="btn btn-warning btn-sm">Export Debug CSV (Lipsa Local)</button>
            </form>
            <form method="post" action="/admin/update/download-images">
              <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
              <input type="hidden" name="type" value="parts">
              <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Download images from CDN? This may take a while.');">Download Missing Images</button>
            </form>
          </div>
          <?php if (!empty($latest_debug_parts)): ?>
            <div class="mt-2">
              <a href="<?php echo htmlspecialchars($latest_debug_parts); ?>" target="_blank">Descarcă ultimul CSV Parts</a>
            </div>
          <?php endif; ?>
        <?php endif; ?>
      </div>
      <div class="tab-pane <?php echo $activeTab === 'themes' ? 'show active' : ''; ?>" id="tab-themes" role="tabpanel">
        <form method="post" action="/admin/update/image-stats" class="mb-2">
          <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
          <input type="hidden" name="type" value="themes">
          <button type="submit" class="btn btn-primary btn-sm">Verifică</button>
        </form>
        <?php if (!empty($stats_themes)): ?>
          <div class="row">
            <div class="col">Total: <strong><?php echo (int)$stats_themes['total']; ?></strong></div>
            <div class="col">
              Local: <strong><?php echo (int)$stats_themes['local']; ?></strong>
              <form method="post" action="/admin/update/image-stats" class="d-inline">
                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
                <input type="hidden" name="type" value="themes">
                <input type="hidden" name="segment" value="local">
                <button type="submit" class="btn btn-link btn-sm p-0 align-baseline">Detalii</button>
              </form>
            </div>
            <div class="col">
              No-image: <strong><?php echo (int)$stats_themes['no_image']; ?></strong>
              <form method="post" action="/admin/update/image-stats" class="d-inline">
                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
                <input type="hidden" name="type" value="themes">
                <input type="hidden" name="segment" value="no_image">
                <button type="submit" class="btn btn-link btn-sm p-0 align-baseline">Detalii</button>
              </form>
            </div>
            <div class="col">CDN: <strong><?php echo (int)$stats_themes['cdn']; ?></strong></div>
          </div>
          <div class="mt-3 d-flex gap-2">
            <form method="post" action="/admin/update/export-debug">
              <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
              <input type="hidden" name="type" value="themes">
              <button type="submit" class="btn btn-warning btn-sm">Export Debug CSV (Lipsa Local)</button>
            </form>
            <form method="post" action="/admin/update/download-images">
              <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
              <input type="hidden" name="type" value="themes">
              <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Download images from CDN? This may take a while.');">Download Missing Images</button>
            </form>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="card-footer">
    Verificarea se face la click pe buton
  </div>
  </div>

<?php if (!empty($detail_items) && !empty($detail_type) && !empty($detail_segment)): ?>
<div class="modal fade" id="imageDetailModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          Detalii <?php echo htmlspecialchars($detail_type); ?> - <?php echo htmlspecialchars($detail_segment); ?>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="table-responsive">
          <table class="table table-sm table-striped">
            <thead>
              <tr>
                <th>ID</th>
                <th>Nume</th>
                <th>Link</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($detail_items as $row): ?>
                <tr>
                  <td>
                    <?php
                    if ($detail_type === 'sets') {
                        echo htmlspecialchars($row['set_num'] ?? '');
                    } elseif ($detail_type === 'parts') {
                        echo htmlspecialchars($row['part_num'] ?? '');
                    } elseif ($detail_type === 'themes') {
                        echo htmlspecialchars($row['id'] ?? '');
                    }
                    ?>
                  </td>
                  <td>
                    <?php echo htmlspecialchars($row['name'] ?? ''); ?>
                  </td>
                  <td>
                    <?php if ($detail_type === 'sets'): ?>
                      <a href="/sets/<?php echo urlencode($row['set_num'] ?? ''); ?>" target="_blank">Deschide set</a>
                    <?php elseif ($detail_type === 'parts'): ?>
                      <a href="/parts/<?php echo urlencode($row['part_num'] ?? ''); ?>" target="_blank">Deschide piesă</a>
                    <?php elseif ($detail_type === 'themes'): ?>
                      <a href="/sets?theme_id=<?php echo urlencode((string)($row['id'] ?? '')); ?>" target="_blank">Deschide seturi temă</a>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Închide</button>
      </div>
    </div>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var modalEl = document.getElementById('imageDetailModal');
  if (modalEl && typeof bootstrap !== 'undefined') {
    var m = new bootstrap.Modal(modalEl);
    m.show();
  }
});
</script>
<?php endif; ?>
