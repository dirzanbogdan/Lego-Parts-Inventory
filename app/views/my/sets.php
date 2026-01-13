<h2>My Sets</h2>
<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if (!empty($success)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>
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
                            <?php if (!empty($set['built'])): ?>
                                <span class="badge bg-success">Built</span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="card-footer p-2">
                        <div class="d-flex justify-content-center align-items-center gap-2">
                            <a href="/sets/<?= htmlspecialchars($set['set_num']) ?>" class="btn btn-primary btn-sm">View</a>
                            <form action="/my/sets/update" method="POST" class="d-flex">
                                <input type="hidden" name="csrf" value="<?= htmlspecialchars(\App\Core\Security::csrfToken()) ?>">
                                <input type="hidden" name="set_num" value="<?= htmlspecialchars($set['set_num']) ?>">
                                <input type="number" name="quantity" class="form-control form-control-sm" value="<?= (int)$set['quantity'] ?>" min="0" style="width: 80px;">
                                <button type="submit" class="btn btn-sm btn-success">Save</button>
                            </form>
                            <form action="/my/sets/remove" method="POST" onsubmit="return confirm('Remove this set?');">
                                <input type="hidden" name="csrf" value="<?= htmlspecialchars(\App\Core\Security::csrfToken()) ?>">
                                <input type="hidden" name="set_num" value="<?= htmlspecialchars($set['set_num']) ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                            </form>
                            <?php if (empty($set['built'])): ?>
                                <form action="/my/sets/build" method="POST" onsubmit="return confirm('Construiești acest set? Vor fi scăzute piesele din My parts.');">
                                    <input type="hidden" name="csrf" value="<?= htmlspecialchars(\App\Core\Security::csrfToken()) ?>">
                                    <input type="hidden" name="set_num" value="<?= htmlspecialchars($set['set_num']) ?>">
                                    <button type="submit" class="btn btn-sm btn-warning">Built</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
