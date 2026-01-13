<h2>My Parts</h2>
<?php if (empty($parts)): ?>
    <p class="text-muted">Nu ai piese în inventar încă.</p>
<?php else: ?>
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th>Image</th>
                <th>Part</th>
                <th>Color</th>
                <th>Quantity</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($parts as $row): ?>
                <tr>
                    <td>
                        <img src="<?= (!empty($row['img_url']) && (strpos($row['img_url'], '/images') === 0 || strpos($row['img_url'], '/parts_images') === 0)) ? htmlspecialchars($row['img_url']) : ((!empty($row['generic_img_url']) && (strpos($row['generic_img_url'], '/images') === 0 || strpos($row['generic_img_url'], '/parts_images') === 0)) ? htmlspecialchars($row['generic_img_url']) : '/images/no-image.png') ?>" 
                             class="part-img" 
                             alt="Part Image"
                             onerror="this.onerror=null; this.src='/images/no-image.png';"
                             style="max-width: 50px;">
                    </td>
                    <td>
                        <div class="fw-bold text-truncate" style="max-width: 220px;"><?= htmlspecialchars($row['part_name']) ?></div>
                        <small class="text-muted"><?= htmlspecialchars($row['part_num']) ?></small>
                    </td>
                    <td>
                        <span class="badge" style="background-color: #<?= htmlspecialchars($row['rgb']) ?>; color: <?= (hexdec($row['rgb']) > 0x888888) ? '#000' : '#FFF' ?>;">
                            <?= htmlspecialchars($row['color_name']) ?>
                        </span>
                    </td>
                    <td>
                        <form action="/parts/update" method="POST" class="d-flex">
                            <input type="hidden" name="part_num" value="<?= htmlspecialchars($row['part_num']) ?>">
                            <input type="hidden" name="color_id" value="<?= (int)$row['color_id'] ?>">
                            <input type="number" name="quantity" class="form-control form-control-sm me-2" value="<?= (int)$row['quantity'] ?>" min="0" style="width: 90px;">
                            <button type="submit" class="btn btn-sm btn-primary">Save</button>
                        </form>
                    </td>
                    <td>
                        <a href="/parts/<?= htmlspecialchars($row['part_num']) ?>" class="btn btn-sm btn-outline-secondary">Details</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
