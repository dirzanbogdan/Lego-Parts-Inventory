<?php $title = 'Detalii set'; ?>
<div class="breadcrumbs">
    <a href="/">Catalog</a> \\ <a href="/sets">Sets</a> \\ 
    <a href="/sets/view?id=<?php echo $set['id']; ?>"><?php echo htmlspecialchars($set['set_code']); ?></a>
</div>

<div class="set-header" style="display:flex; justify-content:space-between; align-items:center; margin-top:20px; border-bottom:1px solid #ccc; padding-bottom:10px;">
    <h2><?php echo htmlspecialchars($set['set_name']); ?></h2>
    <div class="actions">
        <?php if (!empty($set['instructions_url'])): ?>
            <a href="<?php echo htmlspecialchars($set['instructions_url']); ?>" target="_blank" class="btn">Instructiuni</a>
        <?php endif; ?>
        <button type="button" class="btn" onclick="document.getElementById('debug-panel').style.display='block'">Debug</button>
        <button type="button" class="btn" onclick="loadSetChangelog(<?php echo (int)$set['id']; ?>)">Changelog</button>
        <form method="post" action="/sync/bricklink_set" style="display:inline;">
            <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
            <input type="hidden" name="set_code" value="<?php echo htmlspecialchars($set['set_code']); ?>">
            <button type="submit" class="btn">Sync BL</button>
        </form>
        <form method="post" action="/sets/favorite" style="display:inline;">
            <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
            <input type="hidden" name="set_id" value="<?php echo (int)$set['id']; ?>">
            <button type="submit" class="btn">Adauga la Favorite</button>
        </form>
    </div>
</div>

<div class="set-info" style="display:flex; gap:20px; margin-top:20px;">
    <?php if (!empty($set['image'])): ?>
        <div class="set-image">
            <img src="<?php echo htmlspecialchars($set['image']); ?>" alt="" class="set-img" style="max-width:300px;">
        </div>
    <?php endif; ?>
    <div class="set-details">
        <p><strong>Cod:</strong> <?php echo htmlspecialchars($set['set_code']); ?></p>
        <p><strong>An:</strong> <?php echo (int)$set['year']; ?></p>
        <p><strong>Tip:</strong> <?php echo htmlspecialchars($set['type']); ?></p>
        
        <h3>Progres</h3>
        <div class="progress-bar-container" style="width:100%; background:#eee; height:20px; border-radius:10px; overflow:hidden;">
            <div class="progress-bar" style="width:<?php echo htmlspecialchars($progress['progress']); ?>%; background:green; height:100%;"></div>
        </div>
        <p>Necesar: <?php echo (int)$progress['need']; ?> • Avem: <?php echo (int)$progress['have']; ?> • Lipsesc: <?php echo (int)$progress['missing']; ?> • Progres: <?php echo htmlspecialchars($progress['progress']); ?>%</p>
    </div>
</div>

<div id="debug-panel" class="card" style="display:<?php echo !empty($_GET['debug'])?'block':'none'; ?>; margin-top:20px;">
    <h3>Debug set</h3>
    <p>Parametri: set_code=<?php echo htmlspecialchars($set['set_code']); ?></p>
    <p>Link-uri BrickLink (pentru verificare):</p>
    <ul>
        <li>Set: <a href="https://www.bricklink.com/v2/catalog/catalogitem.page?S=<?php echo urlencode($set['set_code']); ?>" target="_blank">Page</a></li>
        <li>Inventory: <a href="https://www.bricklink.com/catalogItemInv.asp?S=<?php echo urlencode($set['set_code']); ?>" target="_blank">Inventory</a></li>
    </ul>

    <?php if (!empty($debug)): ?>
      <div><strong>Record set (din DB):</strong></div>
      <pre style="background:#f4f4f4; padding:5px;"><?php echo htmlspecialchars(json_encode($debug['set_raw'], JSON_PRETTY_PRINT)); ?></pre>
      <div><strong>set_parts count:</strong> <?php echo (int)$debug['parts_count']; ?></div>
      
      <div><strong>Istoric Sincronizare (entity_history):</strong></div>
      <table class="data-table">
        <thead><tr><th>Data</th><th>User</th><th>Detalii (JSON Log)</th></tr></thead>
        <tbody>
          <?php foreach (($history ?? []) as $h): ?>
            <tr>
              <td><?php echo htmlspecialchars($h['created_at']); ?></td>
              <td><?php echo htmlspecialchars($h['user_id'] ?? '-'); ?></td>
              <td>
                  <pre style="max-height:200px; overflow:auto; font-size:11px;"><?php 
                    $ch = json_decode($h['changes'], true);
                    echo htmlspecialchars(json_encode($ch ?: $h['changes'], JSON_PRETTY_PRINT)); 
                  ?></pre>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <div>Nu exista date de debug.</div>
    <?php endif; ?>
</div>

<div id="changelog-panel" class="card" style="display:none; margin-top:20px;">
  <h3>Changelog</h3>
  <table class="data-table" id="changelog-table"><thead><tr><th>Data</th><th>Changes</th><th>User</th></tr></thead><tbody></tbody></table>
</div>

<script>
function loadSetChangelog(id){
  document.getElementById('changelog-panel').style.display='block';
  fetch('/parts/history?id='+id+'&type=set').then(r=>r.json()).then(function(rows){
    var tb=document.querySelector('#changelog-table tbody'); tb.innerHTML='';
    rows.forEach(function(row){
      tb.innerHTML += '<tr><td>'+row.created_at+'</td><td>'+row.changes+'</td><td>'+(row.username||'')+'</td></tr>';
    });
  });
}
</script>
<h3>Piese in set</h3>
<table class="data-table">
  <thead><tr><th>Imagine</th><th>Nume</th><th>Cod</th><th>Culoare</th><th>Necesar</th><th>Avem</th><th>Status</th></tr></thead>
  <tbody>
    <?php foreach ($parts as $p): ?>
        <?php 
            $have = (int)($p['quantity_in_inventory'] ?? 0);
            $need = (int)$p['quantity'];
            $missing = max(0, $need - $have);
            $status = $missing === 0 ? '<span style="color:green">Complet</span>' : '<span style="color:red">Lipsa ' . $missing . '</span>';
        ?>
      <tr>
        <td>
            <?php if (!empty($p['image_url'])): ?>
                <img src="<?php echo htmlspecialchars($p['image_url']); ?>" width="40">
            <?php endif; ?>
        </td>
        <td><a href="/parts/view?id=<?php echo $p['part_id']; ?>"><?php echo htmlspecialchars($p['name']); ?></a></td>
        <td><?php echo htmlspecialchars($p['part_code']); ?></td>
        <td><?php echo htmlspecialchars($p['color_name'] ?? ''); ?></td>
        <td><?php echo $need; ?></td>
        <td><?php echo $have; ?></td>
        <td><?php echo $status; ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
