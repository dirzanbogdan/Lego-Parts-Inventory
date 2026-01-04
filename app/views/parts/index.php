<?php $title = 'Piese'; ?>
<h2>Piese</h2>
<form method="get" action="/search" class="inline">
  <input type="text" name="q" id="search-input" placeholder="Cauta cod sau nume" autocomplete="off">
  <button type="submit">Cauta</button>
</form>
<div id="suggestions"></div>
<table>
  <thead><tr><th>Nume</th><th>Cod</th><th>Categorie</th><th>Actiuni</th></tr></thead>
  <tbody>
    <?php foreach ($parts as $p): ?>
      <tr>
        <td><?php echo htmlspecialchars($p['name']); ?></td>
        <td><?php echo htmlspecialchars($p['part_code']); ?></td>
        <td><?php echo htmlspecialchars($p['category_name'] ?? ''); ?></td>
        <td><a href="/parts/view?id=<?php echo (int)$p['id']; ?>">Detalii</a></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<h3>Adauga piesa</h3>
<form method="post" action="/parts/create">
  <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
  <input type="text" name="name" placeholder="Nume" required>
  <input type="text" name="part_code" placeholder="Cod BrickLink" required>
  <input type="text" name="version" placeholder="Versiune">
  <input type="text" name="image_url" placeholder="Image URL">
  <input type="text" name="bricklink_url" placeholder="BrickLink URL">
  <button type="submit">Salveaza</button>
</form>
<h3>Sync BrickLink</h3>
<form method="post" action="/sync/bricklink">
  <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
  <input type="text" name="part_code" placeholder="Cod BrickLink" required>
  <button type="submit">Sync</button>
</form>
