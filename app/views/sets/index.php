<h2>All Sets</h2>

<form method="get" class="row g-2 mb-3">
    <div class="col-12 col-md-3">
        <label class="form-label mb-1">Theme</label>
        <select name="theme_id" class="form-select form-select-sm">
            <option value="">All</option>
            <?php foreach ($themes as $theme): ?>
                <option value="<?= (int)$theme->id ?>" <?= isset($theme_id) && (int)$theme_id === (int)$theme->id ? 'selected' : '' ?>>
                    <?= htmlspecialchars($theme->name) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-12 col-md-3">
        <label class="form-label mb-1">Image</label>
        <select name="has_image" class="form-select form-select-sm">
            <option value="">Any</option>
            <option value="with" <?= ($has_image ?? '') === 'with' ? 'selected' : '' ?>>With image</option>
            <option value="without" <?= ($has_image ?? '') === 'without' ? 'selected' : '' ?>>Without image</option>
        </select>
    </div>
    <div class="col-6 col-md-2">
        <label class="form-label mb-1">Year from</label>
        <input type="number" name="year_from" class="form-control form-control-sm" value="<?= htmlspecialchars($year_from ?? '') ?>">
    </div>
    <div class="col-6 col-md-2">
        <label class="form-label mb-1">Year to</label>
        <input type="number" name="year_to" class="form-control form-control-sm" value="<?= htmlspecialchars($year_to ?? '') ?>">
    </div>
    <div class="col-12 col-md-2 d-flex align-items-end">
        <div class="form-check me-2">
            <input class="form-check-input" type="checkbox" name="can_build" value="1" id="can_build" <?= !empty($can_build) ? 'checked' : '' ?>>
            <label class="form-check-label" for="can_build">
                Can be built
            </label>
        </div>
        <button type="submit" class="btn btn-sm btn-primary ms-auto">Filter</button>
    </div>
</form>

<div class="row">
    <?php foreach ($sets as $set): ?>
    <div class="col-6 col-md-4 col-lg-2 mb-3">
        <div class="card h-100">
            <div class="text-center p-2">
                <img src="<?= (!empty($set->img_url) && (strpos($set->img_url, '/images') === 0 || strpos($set->img_url, '/parts_images') === 0)) ? htmlspecialchars($set->img_url) : '/images/no-image.png' ?>" class="card-img-top set-img" alt="<?= htmlspecialchars($set->name) ?>" style="max-height: 100px; max-width: 100%; width: auto; object-fit: contain;" onerror="this.onerror=null; this.src='/images/no-image.png'">
            </div>
            <div class="card-body p-2 text-center">
                <h6 class="card-title text-truncate" title="<?= htmlspecialchars($set->name) ?>" style="font-size: 0.9rem; margin-bottom: 0.3rem;"><?= htmlspecialchars($set->name) ?></h6>
                <p class="card-text mb-1" style="font-size: 0.8rem;">
                    <span class="text-muted"><?= $set->set_num ?> (<?= $set->year ?>)</span><br>
                    <span class="badge <?= $set->num_parts == 0 ? 'bg-danger' : 'bg-secondary' ?>"><?= $set->num_parts ?> parts</span>
                </p>
                <a href="/sets/<?= $set->set_num ?>" class="btn btn-primary btn-sm py-0 px-2" style="font-size: 0.75rem;">View</a>
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
        <li class="page-item"><a class="page-link" href="/sets?<?= http_build_query($prevQuery) ?>">Previous</a></li>
        <?php endif; ?>
        
        <li class="page-item disabled"><span class="page-link">Page <?= $page ?> of <?= $totalPages ?></span></li>
        
        <?php if ($page < $totalPages): ?>
        <li class="page-item"><a class="page-link" href="/sets?<?= http_build_query($nextQuery) ?>">Next</a></li>
        <?php endif; ?>
    </ul>
</nav>
