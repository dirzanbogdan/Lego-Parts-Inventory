<h2>All Themes</h2>
<div class="row">
    <?php foreach ($themes as $theme): ?>
    <div class="col-md-4 mb-3">
        <div class="card h-100">
            <?php if (!empty($theme->img_url)): ?>
            <div class="text-center p-3">
                <img src="<?= htmlspecialchars($theme->img_url) ?>" class="card-img-top" alt="<?= htmlspecialchars($theme->name) ?>" style="max-height: 150px; width: auto;">
            </div>
            <?php endif; ?>
            <div class="card-body">
                <h5 class="card-title"><?= htmlspecialchars($theme->name) ?></h5>
                <?php if (!empty($theme->parent_name)): ?>
                <h6 class="card-subtitle mb-2 text-muted">Parent: <?= htmlspecialchars($theme->parent_name) ?></h6>
                <?php endif; ?>
                <a href="/sets?theme_id=<?= $theme->id ?>" class="btn btn-outline-primary btn-sm mt-2">View Sets</a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<nav>
    <ul class="pagination justify-content-center">
        <?php if ($page > 1): ?>
        <li class="page-item"><a class="page-link" href="/themes?page=<?= $page - 1 ?>">Previous</a></li>
        <?php endif; ?>
        
        <li class="page-item disabled"><span class="page-link">Page <?= $page ?> of <?= $totalPages ?></span></li>
        
        <?php if ($page < $totalPages): ?>
        <li class="page-item"><a class="page-link" href="/themes?page=<?= $page + 1 ?>">Next</a></li>
        <?php endif; ?>
    </ul>
</nav>
