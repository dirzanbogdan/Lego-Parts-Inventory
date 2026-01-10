<h1>Welcome to Lego Inventory</h1>
<div class="row">
    <?php foreach ($sets as $set): ?>
    <div class="col-md-3 mb-4">
        <div class="card h-100">
            <img src="<?= $set->img_url ?>" class="card-img-top set-img" alt="<?= htmlspecialchars($set->name) ?>" onerror="this.style.display='none'">
            <div class="card-body">
                <h5 class="card-title"><?= htmlspecialchars($set->name) ?></h5>
                <p class="card-text">
                    <small class="text-muted"><?= $set->set_num ?> (<?= $set->year ?>)</small><br>
                    <?= $set->num_parts ?> parts
                </p>
                <a href="/sets/<?= $set->set_num ?>" class="btn btn-primary btn-sm">View Inventory</a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<div class="text-center mt-3">
    <a href="/sets" class="btn btn-secondary">Browse All Sets</a>
</div>
