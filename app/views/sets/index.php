<?php $title = 'Seturi'; ?>
<h2>Seturi</h2>
<table>
  <thead><tr><th>Nume</th><th>Cod</th><th>An</th><th>Tip</th><th>Actiuni</th></tr></thead>
  <tbody>
    <?php foreach ($sets as $s): ?>
      <tr>
        <td><?php echo htmlspecialchars($s['set_name']); ?></td>
        <td><?php echo htmlspecialchars($s['set_code']); ?></td>
        <td><?php echo (int)$s['year']; ?></td>
        <td><?php echo htmlspecialchars($s['type']); ?></td>
        <td><a href="/sets/view?id=<?php echo (int)$s['id']; ?>">Detalii</a></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<h3>Adauga set</h3>
<form method="post" action="/sets/create">
  <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
  <input type="text" name="set_name" placeholder="Nume" required>
  <input type="text" name="set_code" placeholder="Cod" required>
  <select name="type">
    <option value="official">Official</option>
    <option value="moc">MOC</option>
    <option value="technic">Technic</option>
    <option value="custom">Custom</option>
  </select>
  <input type="number" name="year" placeholder="An" required>
  <input type="text" name="image" placeholder="Image URL">
  <button type="submit">Salveaza</button>
</form>
