<h2>Search Results for "<?= htmlspecialchars($query) ?>"</h2>

<?php if (empty($results)): ?>
    <p>No results found.</p>
<?php else: ?>
    <div class="row">
        <?php foreach ($results as $item): ?>
            <?php if ($type === 'sets'): ?>
                <div class="col-md-3 mb-4">
                    <div class="card h-100">
                        <img src="<?= (!empty($item->img_url) && (strpos($item->img_url, '/images') === 0 || strpos($item->img_url, '/parts_images') === 0)) ? htmlspecialchars($item->img_url) : '/images/no-image.png' ?>" class="card-img-top set-img" alt="<?= htmlspecialchars($item->name) ?>" onerror="this.onerror=null; this.src='/images/no-image.png';">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($item->name) ?></h5>
                            <p class="card-text"><?= $item->set_num ?></p>
                            <a href="/sets/<?= $item->set_num ?>" class="btn btn-primary btn-sm">View</a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="col-md-3 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($item->name) ?></h5>
                            <p class="card-text"><?= $item->part_num ?></p>
                            <a href="/parts/<?= $item->part_num ?>" class="btn btn-primary btn-sm">View Details</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
