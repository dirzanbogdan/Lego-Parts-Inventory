<?php $title = $part['name']; ?>
<div class="breadcrumbs">
    <a href="/">Catalog</a> \ <a href="/parts">Parts</a> \ 
    <a href="/parts?category=<?php echo urlencode($part['category_name'] ?? ''); ?>"><?php echo htmlspecialchars($part['category_name'] ?? 'Uncategorized'); ?></a> \ 
    <a href="/parts/view?id=<?php echo $part['id']; ?>"><?php echo htmlspecialchars($part['part_code']); ?></a>
</div>

<div class="part-header" style="display:flex; justify-content:space-between; align-items:center; margin-top:20px; border-bottom:1px solid #ccc; padding-bottom:10px;">
    <div>
        <h2 style="margin:0;"><?php echo htmlspecialchars($part['name']); ?></h2>
        <div style="color:#555">Item no.: <?php echo htmlspecialchars($part['part_code']); ?></div>
    </div>
    <div class="actions">
        <button id="btn-edit" class="btn" onclick="document.getElementById('edit-form').style.display='block';">Edit</button>
        <button id="btn-changelog" class="btn" onclick="loadChangelog(<?php echo $part['id']; ?>, 'part')">Changelog</button>
        <form method="post" action="/sync/bricklink" style="display:inline;">
            <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf ?? ''); ?>">
            <input type="hidden" name="part_code" value="<?php echo htmlspecialchars($part['part_code']); ?>">
            <button type="submit" class="btn">Sync BL</button>
        </form>
    </div>
</div>

<div id="edit-form" style="display:none; background:#f9f9f9; padding:20px; margin-top:20px; border:1px solid #ddd;">
    <h3>Edit Part</h3>
    <form method="post" action="/parts/update" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf ?? ''); ?>">
        <input type="hidden" name="id" value="<?php echo $part['id']; ?>">
        <div class="form-row">
            <label>Name: <input type="text" name="name" value="<?php echo htmlspecialchars($part['name']); ?>"></label>
            <label>Code: <input type="text" name="part_code" value="<?php echo htmlspecialchars($part['part_code']); ?>"></label>
        </div>
        <div class="form-row">
            <label>Years: <input type="text" name="years_released" value="<?php echo htmlspecialchars($part['years_released'] ?? ''); ?>"></label>
            <label>Weight: <input type="text" name="weight" value="<?php echo htmlspecialchars($part['weight'] ?? ''); ?>"></label>
        </div>
        <div class="form-row">
            <label>Stud Dim: <input type="text" name="stud_dimensions" value="<?php echo htmlspecialchars($part['stud_dimensions'] ?? ''); ?>"></label>
            <label>Pack Dim: <input type="text" name="package_dimensions" value="<?php echo htmlspecialchars($part['package_dimensions'] ?? ''); ?>"></label>
        </div>
        <div class="form-row">
            <label>Image: <input type="file" name="image_file"></label>
        </div>
        <button type="submit" class="btn">Save Changes</button>
        <button type="button" class="btn" onclick="document.getElementById('edit-form').style.display='none';">Cancel</button>
    </form>
</div>

<div class="part-details" style="display:flex; gap:20px; margin-top:20px;">
    <div class="image-section">
        <?php if (!empty($part['image_url'])): ?>
            <img src="<?php echo htmlspecialchars($part['image_url']); ?>" alt="Part Image" style="max-width:300px; border:1px solid #eee; padding:5px;">
        <?php else: ?>
            <div style="width:300px; height:300px; background:#eee; display:flex; align-items:center; justify-content:center;">No Image</div>
        <?php endif; ?>
    </div>
    <div class="info-section" style="flex:1;">
        <table class="info-table">
            <tr>
                <td style="font-weight:bold; width:180px;">Item Info.</td>
                <td>
                    Years Released: <?php echo htmlspecialchars($part['years_released'] ?? '?'); ?><br>
                    Weight: <?php echo htmlspecialchars($part['weight'] ?? '?'); ?> g<br>
                    Stud Dim.: <?php echo htmlspecialchars($part['stud_dimensions'] ?? ''); ?><br>
                    Pack. Dim.: <?php echo htmlspecialchars($part['package_dimensions'] ?? '?'); ?>
                </td>
            </tr>
            <tr>
                <td style="font-weight:bold;">Item Consists Of:</td>
                <td>
                    <a href="#" onclick="document.getElementById('consists-popup').style.display='block'; return false;">
                        <?php echo count($consistOfParts); ?> Parts
                    </a>
                </td>
            </tr>
            <tr>
                <td style="font-weight:bold;">Item Appears In:</td>
                <td>
                    <a href="/sets?part_id=<?php echo $part['id']; ?>">
                        <?php echo $appearsInCount; ?> Sets
                    </a>
                </td>
            </tr>
        </table>
    </div>
</div>

<div class="related-section" style="margin-top:30px; border-top:1px solid #eee; padding-top:20px;">
    <h3>Related Items</h3>
    <p>This Item fits with and is usually used with the following Item(s):</p>
    <?php if (empty($relatedItems)): ?>
        <div>No related items found.</div>
    <?php else: ?>
        <div>
            <?php foreach ($relatedItems as $item): ?>
                <div>
                    Part <?php echo htmlspecialchars($item['code']); ?>
                    <?php if (!empty($item['id'])): ?>
                        (<a href="/parts/view?id=<?php echo (int)$item['id']; ?>">Link</a>)
                    <?php endif; ?>
                    <?php if (!empty($item['name'])): ?>
                        <?php echo htmlspecialchars($item['name']); ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    </div>

<div class="inventory-section" style="margin-top:30px; border-top:1px solid #eee; padding-top:20px;">
    <h3>Inventar</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th>Culoare</th>
                <th>Cantitate</th>
                <th>Condition</th>
                <th>Pret achizitie (Lei)</th>
                <th>Actiuni</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($inventory)): ?>
                <tr><td colspan="5">Nu exista inregistrari de inventar pentru aceasta piesa.</td></tr>
            <?php else: ?>
                <?php foreach ($inventory as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['color_name']); ?></td>
                        <td><?php echo (int)$row['quantity_in_inventory']; ?>
                            <form method="post" action="/inventory/update" class="inline">
                                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf ?? ''); ?>">
                                <input type="hidden" name="part_id" value="<?php echo (int)$part['id']; ?>">
                                <input type="hidden" name="color_id" value="<?php echo (int)$row['color_id']; ?>">
                                <input type="hidden" name="delta" value="1">
                                <input type="hidden" name="reason" value="adjust +1 from part view">
                                <button type="submit" class="btn">+1</button>
                            </form>
                            <form method="post" action="/inventory/update" class="inline">
                                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf ?? ''); ?>">
                                <input type="hidden" name="part_id" value="<?php echo (int)$part['id']; ?>">
                                <input type="hidden" name="color_id" value="<?php echo (int)$row['color_id']; ?>">
                                <input type="hidden" name="delta" value="-1">
                                <input type="hidden" name="reason" value="adjust -1 from part view">
                                <button type="submit" class="btn danger">-1</button>
                            </form>
                        </td>
                        <td><?php echo htmlspecialchars($row['condition_state'] ?? 'New'); ?></td>
                        <td><?php echo htmlspecialchars($row['purchase_price'] ?? ''); ?></td>
                        <td>
                            <form method="post" action="/inventory/updateDetails">
                                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf ?? ''); ?>">
                                <input type="hidden" name="part_id" value="<?php echo (int)$part['id']; ?>">
                                <input type="hidden" name="color_id" value="<?php echo (int)$row['color_id']; ?>">
                                <select name="condition">
                                    <option value="New" <?php echo (($row['condition_state'] ?? 'New')==='New')?'selected':''; ?>>New</option>
                                    <option value="Used" <?php echo (($row['condition_state'] ?? 'New')==='Used')?'selected':''; ?>>Used</option>
                                </select>
                                <input type="text" name="price" value="<?php echo htmlspecialchars($row['purchase_price'] ?? ''); ?>" placeholder="0.00" style="width:80px;">
                                <button type="submit" class="btn">Salveaza</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Popups -->
<div id="changelog-popup" class="popup-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000;">
    <div class="popup-content" style="background:white; margin:100px auto; padding:20px; width:600px; max-width:90%;">
        <span class="close" onclick="document.getElementById('changelog-popup').style.display='none'" style="float:right; cursor:pointer; font-size:24px;">&times;</span>
        <h3>Changelog</h3>
        <table id="changelog-table" class="data-table"><thead><tr><th>Date</th><th>Changes</th><th>User</th></tr></thead><tbody></tbody></table>
    </div>
</div>

<div id="consists-popup" class="popup-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000;">
    <div class="popup-content" style="background:white; margin:100px auto; padding:20px; width:600px; max-width:90%;">
        <span class="close" onclick="document.getElementById('consists-popup').style.display='none'" style="float:right; cursor:pointer; font-size:24px;">&times;</span>
        <h3>Item Consists Of</h3>
        <table class="data-table">
            <thead><tr><th>Image</th><th>Item No.</th><th>Description</th></tr></thead>
            <tbody>
                <?php foreach ($consistOfParts as $cp): ?>
                    <tr>
                        <td><img src="<?php echo htmlspecialchars($cp['image_url']); ?>" width="30"></td>
                        <td><a href="/parts/view?id=<?php echo $cp['id']; ?>"><?php echo htmlspecialchars($cp['part_code']); ?></a></td>
                        <td><?php echo htmlspecialchars($cp['name']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function loadChangelog(id, type) {
    document.getElementById('changelog-popup').style.display = 'block';
    fetch('/parts/history?id=' + id + '&type=' + type)
        .then(r => r.json())
        .then(data => {
            const tbody = document.querySelector('#changelog-table tbody');
            tbody.innerHTML = '';
            data.forEach(row => {
                tbody.innerHTML += `<tr>
                    <td>${row.created_at}</td>
                    <td>${row.changes}</td>
                    <td>${row.username || 'Unknown'}</td>
                </tr>`;
            });
        });
}

function importPart(code) {
    if(confirm('Import ' + code + ' from BrickLink?')) {
        const formData = new FormData();
        formData.append('code', code);
        formData.append('csrf', '<?php echo $csrf ?? ''; ?>'); // Need CSRF
        
        fetch('/admin/config/scrape_parts', { // Reuse admin endpoint
            method: 'POST',
            body: formData
        }).then(r => {
            if(r.ok) location.reload();
            else alert('Error importing');
        });
    }
}
</script>
