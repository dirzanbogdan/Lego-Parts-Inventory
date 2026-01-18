<h2>All Parts</h2>

<form method="get" class="row g-2 mb-3">
    <div class="col-12 col-md-3">
        <label class="form-label mb-1">Image</label>
        <select name="has_image" class="form-select form-select-sm">
            <option value="">Any</option>
            <option value="with" <?= ($has_image ?? '') === 'with' ? 'selected' : '' ?>>With image</option>
            <option value="without" <?= ($has_image ?? '') === 'without' ? 'selected' : '' ?>>Without image</option>
        </select>
    </div>
    <div class="col-12 col-md-4">
        <label class="form-label mb-1">Category</label>
        <select name="category_id" class="form-select form-select-sm">
            <option value="">All</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= (int)$cat['id'] ?>" <?= isset($category_id) && (int)$category_id === (int)$cat['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-12 col-md-2 d-flex align-items-end">
        <button type="submit" class="btn btn-sm btn-primary w-100">Filter</button>
    </div>
</form>
<div class="row">
    <?php foreach ($parts as $part): ?>
    <div class="col-6 col-md-4 col-lg-2 mb-3">
        <div class="card h-100">
            <div class="text-center p-2">
                <img src="<?= (!empty($part->img_url) && (strpos($part->img_url, '/images') === 0 || strpos($part->img_url, '/parts_images') === 0)) ? htmlspecialchars($part->img_url) : '/images/no-image.png' ?>" 
                     class="card-img-top part-img" 
                     alt="<?= htmlspecialchars($part->name) ?>" 
                     style="max-height: 100px; max-width: 100%; width: auto; object-fit: contain;" 
                     onerror="this.onerror=null; this.src='/images/no-image.png';">
            </div>
            <div class="card-body p-2 text-center">
                <h6 class="card-title text-truncate" title="<?= htmlspecialchars($part->name) ?>" style="font-size: 0.9rem; margin-bottom: 0.3rem;"><?= htmlspecialchars($part->name) ?></h6>
                <p class="card-text mb-1" style="font-size: 0.8rem;">
                    <span class="text-muted"><?= htmlspecialchars($part->part_num) ?></span>
                </p>
                <a href="/parts/<?= htmlspecialchars($part->part_num) ?>" class="btn btn-primary btn-sm py-0 px-2" style="font-size: 0.75rem;">View</a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<nav>
    <ul class="pagination justify-content-center">
        <?php 
            $query = $_GET;
            $prevQuery = $query;
            $prevQuery['page'] = $page - 1;
            $nextQuery = $query;
            $nextQuery['page'] = $page + 1;
        ?>
        <?php if ($page > 1): ?>
        <li class="page-item"><a class="page-link" href="/parts?<?= http_build_query($prevQuery) ?>">Previous</a></li>
        <?php endif; ?>
        
        <li class="page-item disabled"><span class="page-link">Page <?= $page ?> of <?= $totalPages ?></span></li>
        
        <?php if ($page < $totalPages): ?>
        <li class="page-item"><a class="page-link" href="/parts?<?= http_build_query($nextQuery) ?>">Next</a></li>
        <?php endif; ?>
    </ul>
</nav>
