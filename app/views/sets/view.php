<?php $title = 'Detalii set'; ?>
<h2><?php echo htmlspecialchars($set['set_name']); ?></h2>
<p>Cod: <?php echo htmlspecialchars($set['set_code']); ?> • An: <?php echo (int)$set['year']; ?> • Tip: <?php echo htmlspecialchars($set['type']); ?></p>
<?php if (!empty($set['image'])): ?>
  <img src="<?php echo htmlspecialchars($set['image']); ?>" alt="" class="set-img">
<?php endif; ?>
<h3>Piese in set</h3>
<table>
  <thead><tr><th>Nume</th><th>Cod</th><th>Culoare</th><th>Cantitate</th></tr></thead>
  <tbody>
    <?php foreach ($parts as $p): ?>
      <tr>
        <td><?php echo htmlspecialchars($p['name']); ?></td>
        <td><?php echo htmlspecialchars($p['part_code']); ?></td>
        <td><?php echo htmlspecialchars($p['color_name'] ?? ''); ?></td>
        <td><?php echo (int)$p['quantity']; ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<h3>Progres</h3>
<p>Necesar: <?php echo (int)$progress['need']; ?> • Avem: <?php echo (int)$progress['have']; ?> • Lipsesc: <?php echo (int)$progress['missing']; ?> • Progres: <?php echo htmlspecialchars($progress['progress']); ?>%</p>
<form method="post" action="/sets/favorite">
  <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
  <input type="hidden" name="set_id" value="<?php echo (int)$set['id']; ?>">
  <button type="submit">Adauga la Favorite</button>
</form>
