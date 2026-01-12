<h2>Search Results for "<?= htmlspecialchars($query) ?>"</h2>

<?php if (empty($results)): ?>
    <p>No results found.</p>
<?php else: ?>
    <div class="row">
        <?php foreach ($results as $item): ?>
            <?php if ($type === 'sets'): ?>
                <div class="col-6 col-md-4 col-lg-2 mb-3">
                    <div class="card h-100">
                        <div class="text-center p-2">
                            <img src="<?= (!empty($item->img_url) && (strpos($item->img_url, '/images') === 0 || strpos($item->img_url, '/parts_images') === 0)) ? htmlspecialchars($item->img_url) : '/images/no-image.png' ?>" class="card-img-top set-img" alt="<?= htmlspecialchars($item->name) ?>" style="max-height: 100px; max-width: 100%; width: auto; object-fit: contain;" onerror="this.onerror=null; this.src='/images/no-image.png';">
                        </div>
                        <div class="card-body p-2 text-center">
                            <h6 class="card-title text-truncate" title="<?= htmlspecialchars($item->name) ?>" style="font-size: 0.9rem; margin-bottom: 0.3rem;"><?= htmlspecialchars($item->name) ?></h6>
                            <p class="card-text mb-1" style="font-size: 0.8rem;">
                                <span class="text-muted"><?= $item->set_num ?></span><br>
                                <?php if (isset($item->num_parts)): ?>
                                    <span class="badge <?= $item->num_parts == 0 ? 'bg-danger' : 'bg-secondary' ?>"><?= $item->num_parts ?> parts</span>
                                <?php endif; ?>
                            </p>
                            <a href="/sets/<?= $item->set_num ?>" class="btn btn-primary btn-sm py-0 px-2" style="font-size: 0.75rem;">View</a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="col-6 col-md-4 col-lg-2 mb-3">
                    <div class="card h-100">
                        <div class="text-center p-2">
                            <img src="<?= (!empty($item->img_url) && (strpos($item->img_url, '/images') === 0 || strpos($item->img_url, '/parts_images') === 0)) ? htmlspecialchars($item->img_url) : '/images/no-image.png' ?>" class="card-img-top part-img" alt="<?= htmlspecialchars($item->name) ?>" style="max-height: 100px; max-width: 100%; width: auto; object-fit: contain;" onerror="this.onerror=null; this.src='/images/no-image.png';">
                        </div>
                        <div class="card-body p-2 text-center">
                            <h6 class="card-title text-truncate" title="<?= htmlspecialchars($item->name) ?>" style="font-size: 0.9rem; margin-bottom: 0.3rem;"><?= htmlspecialchars($item->name) ?></h6>
                            <p class="card-text mb-1" style="font-size: 0.8rem;">
                                <span class="text-muted"><?= $item->part_num ?></span>
                            </p>
                            <a href="/parts/<?= $item->part_num ?>" class="btn btn-primary btn-sm py-0 px-2" style="font-size: 0.75rem;">View Details</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
