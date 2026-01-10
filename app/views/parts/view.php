<h1>Part: <?= htmlspecialchars($part->name) ?></h1>
<p class="lead">Number: <?= $part->part_num ?></p>
<p>Material: <?= $part->part_material ?></p>

<h3>Available Colors</h3>
<div class="row">
    <?php foreach ($colors as $color): ?>
    <div class="col-md-2 col-4 mb-3 text-center">
        <div class="card">
            <img src="/images/parts/parts_<?= $color['id'] ?>/<?= $part->part_num ?>.png" 
                 class="card-img-top part-img mx-auto mt-2" 
                 alt="<?= htmlspecialchars($color['name']) ?>"
                 onerror="this.src='/images/no-image.png'">
            <div class="card-body p-2">
                <small><?= htmlspecialchars($color['name']) ?></small>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
