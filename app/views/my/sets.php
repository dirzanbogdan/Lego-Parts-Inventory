<h2>My Sets</h2>
<?php if (empty($sets)): ?>
    <p class="text-muted">Nu ai seturi adăugate încă.</p>
<?php else: ?>
    <div class="row">
        <?php foreach ($sets as $set): ?>
            <div class="col-6 col-md-4 col-lg-3 mb-3">
                <div class="card h-100">
                    <div class="text-center p-2">
                        <img src="<?= (!empty($set['img_url']) && (strpos($set['img_url'], '/images') === 0 || strpos($set['img_url'], '/parts_images') === 0)) ? htmlspecialchars($set['img_url']) : '/images/no-image.png' ?>" 
                             class="card-img-top set-img" 
                             alt="<?= htmlspecialchars($set['name']) ?>" 
                             style="max-height: 120px; max-width: 100%; width: auto; object-fit: contain;" 
                             onerror="this.onerror=null; this.src='/images/no-image.png';">
                    </div>
                    <div class="card-body p-2 text-center">
                        <h6 class="card-title text-truncate" title="<?= htmlspecialchars($set['name']) ?>"><?= htmlspecialchars($set['name']) ?></h6>
                        <p class="card-text mb-1" style="font-size: 0.85rem;">
                            <span class="text-muted"><?= htmlspecialchars($set['set_num']) ?> (<?= htmlspecialchars($set['year']) ?>)</span><br>
                            <span class="badge bg-secondary">Qty: <?= (int)$set['quantity'] ?></span>
                        </p>
                        <a href="/sets/<?= htmlspecialchars($set['set_num']) ?>" class="btn btn-primary btn-sm">View</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
