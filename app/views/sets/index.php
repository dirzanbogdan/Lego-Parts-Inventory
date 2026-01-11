<h2>All Sets</h2>
<div class="row">
    <?php foreach ($sets as $set): ?>
    <div class="col-md-3 mb-4">
        <div class="card h-100">
            <div class="text-center p-3">
                <img src="<?= htmlspecialchars($set->img_url ?? '/images/no-image.png') ?>" class="card-img-top set-img" alt="<?= htmlspecialchars($set->name) ?>" style="max-height: 150px; width: auto;" onerror="this.src='/images/no-image.png'">
            </div>
            <div class="card-body">
                <h5 class="card-title"><?= htmlspecialchars($set->name) ?></h5>
                <p class="card-text">
                    <small class="text-muted"><?= $set->set_num ?> (<?= $set->year ?>)</small>
                </p>
                <a href="/sets/<?= $set->set_num ?>" class="btn btn-primary btn-sm">View</a>
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
