<?php $title = 'Detalii piesa'; ?>
<h2><?php echo htmlspecialchars($part['name']); ?></h2>
<p>Cod: <?php echo htmlspecialchars($part['part_code']); ?></p>
<?php if (!empty($part['image_url'])): ?>
  <img src="<?php echo htmlspecialchars($part['image_url']); ?>" alt="" class="part-img">
<?php endif; ?>
<?php if (!empty($part['bricklink_url'])): ?>
  <p><a href="<?php echo htmlspecialchars($part['bricklink_url']); ?>" target="_blank">Vezi pe BrickLink</a></p>
<?php endif; ?>
<h3>Inventar pe culori</h3>
<table>
  <thead><tr><th>Culoare</th><th>Cod</th><th>Cantitate</th></tr></thead>
  <tbody>
    <?php foreach ($inventory as $i): ?>
      <tr>
        <td><?php echo htmlspecialchars($i['color_name']); ?></td>
        <td><?php echo htmlspecialchars($i['color_code']); ?></td>
        <td><?php echo (int)$i['quantity_in_inventory']; ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<h3>Editeaza</h3>
<form method="post" action="/parts/update" enctype="multipart/form-data">
  <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
  <input type="hidden" name="id" value="<?php echo (int)$part['id']; ?>">
  <label>Nume</label>
  <input type="text" name="name" value="<?php echo htmlspecialchars($part['name']); ?>" required>
  <label>Cod BrickLink</label>
  <input type="text" name="part_code" value="<?php echo htmlspecialchars($part['part_code']); ?>" required>
  <label>Versiune</label>
  <input type="text" name="version" value="<?php echo htmlspecialchars($part['version']); ?>">
  <label>Poza (upload)</label>
  <input type="file" name="image_file" accept="image/*">
  <label>URL BrickLink</label>
  <input type="text" name="bricklink_url" value="<?php echo htmlspecialchars($part['bricklink_url']); ?>">
  <button type="submit">Salveaza</button>
</form>
<h3>Adauga culoare pentru piesa</h3>
<form method="post" action="/inventory/update" class="inline">
  <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
  <input type="hidden" name="part_id" value="<?php echo (int)$part['id']; ?>">
  <label>Culoare</label>
  <select name="color_id">
    <?php foreach (\App\Models\Color::all() as $c): ?>
      <option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['color_name']); ?></option>
    <?php endforeach; ?>
  </select>
  <input type="hidden" name="delta" value="0">
  <input type="text" name="reason" placeholder="Motiv (optional)">
  <button type="submit">Adauga</button>
</form>
<form method="post" action="/parts/delete" onsubmit="return confirm('Stergi piesa?')">
  <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
  <input type="hidden" name="id" value="<?php echo (int)$part['id']; ?>">
  <button type="submit" class="danger">Sterge</button>
</form>
