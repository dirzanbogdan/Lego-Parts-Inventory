<div class="row mb-4">
    <div class="col-12">
        <a href="/identify" class="btn btn-outline-secondary">&larr; Upload Another</a>
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm sticky-top" style="top: 20px;">
            <div class="card-header">
                <h5 class="mb-0">Uploaded Image</h5>
            </div>
            <div class="card-body p-0">
                <img src="<?= $uploadedImage ?>" class="img-fluid rounded-bottom" alt="Uploaded parts">
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3>Identified Parts</h3>
            <?php if (!empty($results)): ?>
            <form action="/identify/add-all" method="POST">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars(\App\Core\Security::csrfToken()) ?>">
                <input type="hidden" name="items" value="<?= htmlspecialchars(json_encode($results)) ?>">
                <button type="submit" class="btn btn-success">
                    Add All to My Parts
                </button>
            </form>
            <?php endif; ?>
        </div>

        <?php if (empty($results)): ?>
            <div class="alert alert-warning">No parts identified. Try a clearer image.</div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($results as $item): ?>
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-body d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <img src="<?= htmlspecialchars($item['img_url']) ?>" alt="<?= htmlspecialchars($item['part_name']) ?>" 
                                     class="img-fluid" style="width: 80px; height: 80px; object-fit: contain;">
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h5 class="mb-1"><?= htmlspecialchars($item['part_name']) ?></h5>
                                <p class="mb-1 text-muted small">
                                    Part #: <strong><?= htmlspecialchars($item['part_num']) ?></strong> | 
                                    Color: <span class="badge" style="background-color: #<?= $item['color_rgb'] ?>; color: <?= (hexdec($item['color_rgb']) > 0x7FFFFF) ? '#000' : '#fff' ?>;">
                                        <?= htmlspecialchars($item['color_name']) ?>
                                    </span>
                                </p>
                                <div class="badge bg-info text-dark">Confidence: <?= $item['confidence'] ?>%</div>
                            </div>
                            <div class="ms-3 text-end" style="min-width: 150px;">
                                <form action="/identify/add" method="POST" class="d-flex align-items-center justify-content-end gap-2">
                                    <input type="hidden" name="csrf" value="<?= htmlspecialchars(\App\Core\Security::csrfToken()) ?>">
                                    <input type="hidden" name="part_num" value="<?= htmlspecialchars($item['part_num']) ?>">
                                    <input type="hidden" name="color_id" value="<?= htmlspecialchars($item['color_id']) ?>">
                                    
                                    <div class="input-group input-group-sm" style="width: 100px;">
                                        <span class="input-group-text">Qty</span>
                                        <input type="number" name="quantity" class="form-control" value="<?= $item['quantity'] ?>" min="1">
                                    </div>
                                    
                                    <button type="submit" class="btn btn-sm btn-primary" title="Add to My Parts">
                                        Add
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
