<?php $title = 'Inventar'; ?>
<h2>Inventar</h2>
<form method="get" action="/inventory">
  <label>Piesa</label>
  <select name="part_id">
    <option value="0">Selecteaza</option>
    <?php foreach ($parts as $p): ?>
      <option value="<?php echo (int)$p['id']; ?>" <?php echo ($partId===(int)$p['id'])?'selected':''; ?>><?php echo htmlspecialchars($p['name']); ?></option>
    <?php endforeach; ?>
  </select>
  <button type="submit">Afiseaza</button>
</form>
<?php if ($partId): ?>
<table>
  <thead><tr><th>Culoare</th><th>Cod</th><th>Cantitate</th><th>Actualizeaza</th></tr></thead>
  <tbody>
    <?php foreach ($inventory as $i): ?>
      <tr<?php echo ((int)$i['quantity_in_inventory']===0)?' style="background:#fff7ed"':''; ?>>
        <td><?php echo htmlspecialchars($i['color_name']); ?></td>
        <td><?php echo htmlspecialchars($i['color_code']); ?></td>
        <td><?php echo (int)$i['quantity_in_inventory']; ?></td>
        <td>
          <form method="post" action="/inventory/update" class="inline">
            <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
            <input type="hidden" name="part_id" value="<?php echo (int)$partId; ?>">
            <input type="hidden" name="color_id" value="<?php echo (int)$i['color_id']; ?>">
            <input type="number" name="delta" value="1">
            <input type="text" name="reason" placeholder="Motiv">
            <button type="submit">Aplica</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<h3>Istoric modificari</h3>
<table>
  <thead><tr><th>Data</th><th>Culoare</th><th>Delta</th><th>User</th><th>Motiv</th></tr></thead>
  <tbody>
    <?php foreach ($history as $h): ?>
      <tr>
        <td><?php echo htmlspecialchars($h['created_at']); ?></td>
        <td><?php echo htmlspecialchars($h['color_name'] ?? ''); ?></td>
        <td><?php echo (int)$h['delta']; ?></td>
        <td><?php echo htmlspecialchars($h['username'] ?? ''); ?></td>
        <td><?php echo htmlspecialchars($h['reason'] ?? ''); ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>
<h3>Export</h3>
<a class="btn" href="/inventory/export">Export CSV</a>
<h3>Import</h3>
<form method="post" action="/inventory/import" enctype="multipart/form-data">
  <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
  <input type="file" name="csv" accept=".csv" required>
  <button type="submit">Importa</button>
</form>
