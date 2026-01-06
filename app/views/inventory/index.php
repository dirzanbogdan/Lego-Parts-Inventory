<?php $title = 'Inventar'; ?>
<h2>Inventar</h2>
<div class="search-box">
    <form method="get" action="/inventory" style="display:flex; gap:10px; margin-bottom:10px;">
        <input type="text" name="q" placeholder="Cauta piesa (nume sau cod)..." value="<?php echo htmlspecialchars($query ?? ''); ?>" style="flex:1;">
        <button type="submit">Cauta</button>
    </form>
</div>

<form method="get" action="/inventory">
  <input type="hidden" name="q" value="<?php echo htmlspecialchars($query ?? ''); ?>">
  <label>Selecteaza Piesa:</label>
  <select name="part_id" onchange="this.form.submit()">
    <option value="0">-- Selecteaza --</option>
    <?php foreach ($parts as $p): ?>
      <option value="<?php echo (int)$p['id']; ?>" <?php echo ($partId===(int)$p['id'])?'selected':''; ?>><?php echo htmlspecialchars($p['part_code'] . ' - ' . $p['name']); ?></option>
    <?php endforeach; ?>
  </select>
</form>

<?php if ($partId): ?>
<table class="data-table">
  <thead><tr><th>Culoare</th><th>Cod</th><th>Cantitate (Delta)</th><th>Conditie</th><th>Pret</th><th>Actiuni</th></tr></thead>
  <tbody>
    <?php foreach ($inventory as $i): ?>
      <?php $detailsFormId = 'details_form_' . $i['color_id']; ?>
      <tr<?php echo ((int)$i['quantity_in_inventory']===0)?' style="background:#fff7ed"':''; ?>>
        <td><?php echo htmlspecialchars($i['color_name']); ?></td>
        <td><?php echo htmlspecialchars($i['color_code']); ?></td>
        <td>
            <span style="font-weight:bold; margin-right:10px;"><?php echo (int)$i['quantity_in_inventory']; ?></span>
            <form method="post" action="/inventory/update" class="inline" style="display:inline-flex; gap:5px;">
                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
                <input type="hidden" name="part_id" value="<?php echo (int)$partId; ?>">
                <input type="hidden" name="color_id" value="<?php echo (int)$i['color_id']; ?>">
                <input type="number" name="delta" value="0" style="width:50px">
                <input type="text" name="reason" placeholder="Motiv" style="width:80px">
                <button type="submit" class="btn-small">Upd</button>
            </form>
        </td>
        <td>
            <select name="condition" form="<?php echo $detailsFormId; ?>">
                <option value="New" <?php echo ($i['condition_state'] ?? 'New') === 'New' ? 'selected' : ''; ?>>New</option>
                <option value="Used" <?php echo ($i['condition_state'] ?? 'New') === 'Used' ? 'selected' : ''; ?>>Used</option>
            </select>
        </td>
        <td>
            <input type="number" step="0.01" name="price" value="<?php echo htmlspecialchars($i['purchase_price'] ?? '0.00'); ?>" style="width:70px" form="<?php echo $detailsFormId; ?>">
        </td>
        <td>
          <form id="<?php echo $detailsFormId; ?>" method="post" action="/inventory/updateDetails" class="inline">
            <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
            <input type="hidden" name="part_id" value="<?php echo (int)$partId; ?>">
            <input type="hidden" name="color_id" value="<?php echo (int)$i['color_id']; ?>">
            <button type="submit" class="btn-small">Salveaza</button>
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
