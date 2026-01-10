<div class="row mb-4">
    <div class="col-md-4">
        <img src="<?= $set->img_url ?>" class="img-fluid rounded" alt="<?= htmlspecialchars($set->name) ?>">
    </div>
    <div class="col-md-8">
        <h1><?= htmlspecialchars($set->name) ?></h1>
        <p class="lead">Set: <?= $set->set_num ?></p>
        <p>Year: <?= $set->year ?></p>
        <p>Theme ID: <?= $set->theme_id ?></p>
        <p>Parts: <?= $set->num_parts ?></p>
    </div>
</div>

<h3>Inventory</h3>
<table class="table table-striped table-hover">
    <thead>
        <tr>
            <th>Image</th>
            <th>Part Num</th>
            <th>Color</th>
            <th>Part Name</th>
            <th>Qty</th>
            <th>Spare</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($inventory as $item): ?>
        <tr>
            <td>
                <img src="/images/parts/parts_<?= $item['color_id'] ?>/<?= $item['part_num'] ?>.png" 
                     class="part-img" 
                     alt="Part Image"
                     onerror="this.src='/images/no-image.png'">
            </td>
            <td><a href="/parts/<?= $item['part_num'] ?>"><?= $item['part_num'] ?></a></td>
            <td>
                <span class="badge" style="background-color: #<?= $item['rgb'] ?>; color: <?= (hexdec($item['rgb']) > 0x888888) ? '#000' : '#FFF' ?>;">
                    <?= htmlspecialchars($item['color_name']) ?>
                </span>
            </td>
            <td><?= htmlspecialchars($item['part_name']) ?></td>
            <td><?= $item['quantity'] ?></td>
            <td><?= $item['is_spare'] ? 'Yes' : 'No' ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
