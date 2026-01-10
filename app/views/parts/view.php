<h1>Part: <?= htmlspecialchars($part->name) ?></h1>
<p class="lead">Number: <?= $part->part_num ?></p>
<p>Material: <?= $part->part_material ?></p>

<h3>Available Colors</h3>
<div class="row">
    <?php foreach ($colors as $color): ?>
    <div class="col-md-3 col-6 mb-4 text-center">
        <div class="card h-100">
            <img src="/parts_images/parts_<?= $color['id'] ?>/<?= $part->part_num ?>.png" 
                 class="card-img-top part-img mx-auto mt-2" 
                 alt="<?= htmlspecialchars($color['name']) ?>"
                 onerror="this.src='/images/no-image.png'"
                 style="max-width: 100px;">
            <div class="card-body p-2">
                <h6 class="card-title"><?= htmlspecialchars($color['name']) ?></h6>
                <p class="card-text">Owned: <strong><?= $color['user_quantity'] ?></strong></p>
                
                <form action="/parts/update" method="POST" class="d-flex justify-content-center">
                    <input type="hidden" name="part_num" value="<?= $part->part_num ?>">
                    <input type="hidden" name="color_id" value="<?= $color['id'] ?>">
                    <input type="number" name="quantity" class="form-control form-control-sm me-1" value="<?= $color['user_quantity'] ?>" min="0" style="width: 70px;">
                    <button type="submit" class="btn btn-sm btn-primary">Save</button>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
