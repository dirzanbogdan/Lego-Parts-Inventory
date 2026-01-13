<div class="row mb-4">
    <div class="col-md-4 text-center">
        <img src="<?= (!empty($set->img_url) && (strpos($set->img_url, '/images') === 0 || strpos($set->img_url, '/parts_images') === 0)) ? htmlspecialchars($set->img_url) : '/images/no-image.png' ?>" class="img-fluid rounded" alt="<?= htmlspecialchars($set->name) ?>" onerror="this.onerror=null; this.src='/images/no-image.png';">
    </div>
    <div class="col-md-8">
        <h1><?= htmlspecialchars($set->name) ?></h1>
        <p class="lead">Set: <?= $set->set_num ?></p>
        <p>Year: <?= $set->year ?></p>
        <p>Theme: <a href="/search?type=sets&q=<?= urlencode($set->theme_name ?? '') ?>"><?= htmlspecialchars($set->theme_name ?? $set->theme_id) ?></a></p>
        <p>Parts: <?= $set->num_parts ?></p>
        <form action="/my/sets/add" method="POST" class="mt-2">
            <input type="hidden" name="set_num" value="<?= htmlspecialchars($set->set_num) ?>">
            <input type="hidden" name="quantity" value="1">
            <button type="submit" class="btn btn-success btn-sm">Add to My sets</button>
        </form>
    </div>
</div>

<?php if (empty($inventory)): ?>
    <div class="alert alert-warning">
        No inventory details found for this set. Please ensure the database is populated.
    </div>
<?php else: ?>
    <h3>Inventory</h3>
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th>Image</th>
                <th>Part Num</th>
                <th>Color</th>
                <th>Part Name</th>
                <th>Qty</th>
                <th>Owned</th>
                <th>Spare</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($inventory as $item): ?>
            <tr class="<?= ($item['user_quantity'] >= $item['quantity']) ? 'table-success' : '' ?>">
                <td>
                    <img src="<?= (!empty($item['img_url']) && (strpos($item['img_url'], '/images') === 0 || strpos($item['img_url'], '/parts_images') === 0)) ? htmlspecialchars($item['img_url']) : ((!empty($item['generic_img_url']) && (strpos($item['generic_img_url'], '/images') === 0 || strpos($item['generic_img_url'], '/parts_images') === 0)) ? htmlspecialchars($item['generic_img_url']) : '/images/no-image.png') ?>" 
                         class="part-img" 
                         alt="Part Image"
                         onerror="this.onerror=null; this.src='/images/no-image.png';"
                         style="max-width: 50px;">
                </td>
                <td><a href="/parts/<?= $item['part_num'] ?>"><?= $item['part_num'] ?></a></td>
                <td>
                    <span class="badge" style="background-color: #<?= $item['rgb'] ?>; color: <?= (hexdec($item['rgb']) > 0x888888) ? '#000' : '#FFF' ?>;">
                        <?= htmlspecialchars($item['color_name']) ?>
                    </span>
                </td>
                <td><?= htmlspecialchars($item['part_name']) ?></td>
                <td><?= $item['quantity'] ?></td>
                <td>
                    <?= $item['user_quantity'] ?>
                    <?php if ($item['user_quantity'] < $item['quantity']): ?>
                        <span class="text-danger">(-<?= $item['quantity'] - $item['user_quantity'] ?>)</span>
                    <?php endif; ?>
                </td>
                <td><?= $item['is_spare'] ? 'Yes' : 'No' ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
