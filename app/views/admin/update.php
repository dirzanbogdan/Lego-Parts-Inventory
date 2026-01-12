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
            <button type="button" class="btn btn-success btn-sm" onclick="startDownload('sets', '<?php echo htmlspecialchars($csrf); ?>')">Download Missing Images</button>
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
            <button type="button" class="btn btn-success btn-sm" onclick="startDownload('parts', '<?php echo htmlspecialchars($csrf); ?>')">Download Missing Images</button>
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
            <form method="post" action="/admin/update/populate-theme-urls" class="d-inline">
              <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
              <button type="submit" class="btn btn-info btn-sm">Populează URL-uri CDN (din Seturi)</button>
            </form>
            <button type="button" class="btn btn-success btn-sm" onclick="startDownload('themes', '<?php echo htmlspecialchars($csrf); ?>')">Download Missing Images</button>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="card-footer">
    Verificarea se face la click pe buton
  </div>
  </div>

<!-- Log Modal -->
<div class="modal fade" id="logModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Download Progress</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="modalCloseBtn" disabled></button>
      </div>
      <div class="modal-body bg-dark text-light" style="height: 500px; display: flex; flex-direction: column;">
        <!-- Stats Dashboard -->
        <div id="downloadStats" class="p-3 mb-2 border-bottom" style="font-family: monospace; font-size: 1.1em;">
            <div class="row">
                <div class="col-4 text-warning">SKIPPED: <span id="statSkipped">0</span></div>
                <div class="col-4 text-success">DOWNLOADED: <span id="statDownloaded">0</span></div>
                <div class="col-4 text-danger">FAILED: <span id="statFailed">0</span></div>
            </div>
            <div class="row mt-2">
                <div class="col-12 text-info">PROCESSED: <span id="statProcessed">0</span> / <span id="statTotal">0</span></div>
            </div>
        </div>

        <div style="flex-grow: 1; overflow-y: auto; font-family: monospace;" id="logContent">
            <div class="d-flex align-items-center mb-3" id="loaderSpinner">
                <div class="spinner-border text-light me-3" role="status"></div>
                <span>Processing...</span>
            </div>
            <pre id="logText" class="m-0 text-light" style="white-space: pre-wrap;"></pre>
        </div>
      </div>
      <div class="modal-footer justify-content-between">
                <div>
                    <button type="button" class="btn btn-warning" id="pauseBtn" disabled>Pause</button>
                    <button type="button" class="btn btn-success" id="resumeBtn" style="display:none;">Resume</button>
                </div>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="modalCloseBtnBottom" disabled>Close</button>
              </div>
            </div>
          </div>
        </div>

        <script>
        let controller = null;
        let isPaused = false;
        
        function startDownload(type, csrf) {
            const modal = new bootstrap.Modal(document.getElementById('logModal'));
            const logText = document.getElementById('logText');
            const spinner = document.getElementById('loaderSpinner');
            const closeBtns = [document.getElementById('modalCloseBtn'), document.getElementById('modalCloseBtnBottom')];
            const pauseBtn = document.getElementById('pauseBtn');
            const resumeBtn = document.getElementById('resumeBtn');
            
            // Reset UI
            if (!isPaused) {
                logText.textContent = '';
                document.getElementById('statSkipped').textContent = '0';
                document.getElementById('statDownloaded').textContent = '0';
                document.getElementById('statFailed').textContent = '0';
                document.getElementById('statProcessed').textContent = '0';
                document.getElementById('statTotal').textContent = '0';
            }
            spinner.style.display = 'flex';
            closeBtns.forEach(btn => btn.disabled = true);
            pauseBtn.disabled = false;
            pauseBtn.style.display = 'inline-block';
            resumeBtn.style.display = 'none';
            modal.show();

            controller = new AbortController();
            const signal = controller.signal;

            const formData = new FormData();
            formData.append('type', type);
            formData.append('csrf', csrf);

            fetch('/admin/update/download-images', {
                method: 'POST',
                body: formData,
                signal: signal
            }).then(response => {
                const reader = response.body.getReader();
                const decoder = new TextDecoder();
                let buffer = '';
                
                function read() {
                    reader.read().then(({done, value}) => {
                        if (done) {
                            if (!isPaused) {
                                spinner.style.display = 'none';
                                closeBtns.forEach(btn => btn.disabled = false);
                                pauseBtn.disabled = true;
                                logText.textContent += '\nProcess Finished.';
                                const container = document.getElementById('logContent');
                                container.scrollTop = container.scrollHeight;
                                closeBtns.forEach(btn => {
                                    btn.onclick = () => window.location.reload();
                                });
                            }
                            return;
                        }
                        
                        const chunk = decoder.decode(value, {stream: true});
                        buffer += chunk;
                        
                        let lines = buffer.split('\n');
                        buffer = lines.pop(); // Keep last partial line
                        
                        let textToAppend = '';
                        
                        lines.forEach(line => {
                            if (line.startsWith('STATS:')) {
                                try {
                                    const stats = JSON.parse(line.substring(6));
                                    document.getElementById('statSkipped').textContent = stats.skipped;
                                    document.getElementById('statDownloaded').textContent = stats.downloaded;
                                    document.getElementById('statFailed').textContent = stats.failed;
                                    document.getElementById('statProcessed').textContent = stats.processed;
                                    document.getElementById('statTotal').textContent = stats.total;
                                } catch (e) {
                                    console.error('Stats parse error', e);
                                }
                            } else {
                                textToAppend += line + '\n';
                            }
                        });
                        
                        if (textToAppend) {
                            logText.textContent += textToAppend;
                            
                            // Limit log size
                            if (logText.textContent.length > 50000) {
                                logText.textContent = logText.textContent.substring(logText.textContent.length - 50000);
                            }

                            const container = document.getElementById('logContent');
                            container.scrollTop = container.scrollHeight;
                        }
                        
                        read();
                    }).catch(error => {
                        if (error.name === 'AbortError') {
                            logText.textContent += '\n[PAUSED] Download stopped by user. Click Resume to continue.';
                            spinner.style.display = 'none';
                            closeBtns.forEach(btn => {
                                btn.disabled = false;
                                // Reload page on close to ensure clean state
                                btn.onclick = () => window.location.reload();
                            });
                            return;
                        }
                        throw error;
                    });
                }
                read();
            }).catch(err => {
                if (err.name === 'AbortError') {
                    logText.textContent += '\n[PAUSED] Download stopped by user. Click Resume to continue.';
                } else {
                    logText.textContent += '\nError: ' + err;
                }
                spinner.style.display = 'none';
                closeBtns.forEach(btn => btn.disabled = false);
            });

            pauseBtn.onclick = () => {
                isPaused = true;
                if (controller) {
                    controller.abort();
                }
                pauseBtn.style.display = 'none';
                resumeBtn.style.display = 'inline-block';
            };

            resumeBtn.onclick = () => {
                isPaused = false;
                startDownload(type, csrf);
            };
            
            return false;
        }
        </script>

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
