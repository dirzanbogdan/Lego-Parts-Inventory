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
