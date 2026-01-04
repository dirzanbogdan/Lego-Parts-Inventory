<?php $title = 'Cautare'; ?>
<h2>Cautare</h2>
<form method="get" action="/search">
  <input type="text" name="q" value="<?php echo htmlspecialchars($query ?? ''); ?>" placeholder="Cod, nume">
  <input type="text" name="color" placeholder="Culoare">
  <input type="text" name="category" placeholder="Categorie">
  <input type="text" name="year" placeholder="An">
  <input type="number" step="0.01" name="min_weight" placeholder="Greutate minima">
  <input type="number" step="0.01" name="max_weight" placeholder="Greutate maxima">
  <label><input type="checkbox" name="available" value="1"> In inventar</label>
  <button type="submit">Filtreaza</button>
</form>
<table>
  <thead><tr><th>Nume</th><th>Cod</th><th>Categorie</th></tr></thead>
  <tbody>
    <?php foreach ($parts as $p): ?>
      <tr>
        <td><?php echo htmlspecialchars($p['name']); ?></td>
        <td><?php echo htmlspecialchars($p['part_code']); ?></td>
        <td><?php echo htmlspecialchars($p['category_name'] ?? ''); ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
