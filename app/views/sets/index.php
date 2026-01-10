<h2>All Sets</h2>
<div class="row">
    <?php foreach ($sets as $set): ?>
    <div class="col-md-3 mb-4">
        <div class="card h-100">
            <img src="<?= $set->img_url ?>" class="card-img-top set-img" alt="<?= htmlspecialchars($set->name) ?>" onerror="this.style.display='none'">
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
        <?php if ($page > 1): ?>
        <li class="page-item"><a class="page-link" href="/sets?page=<?= $page - 1 ?>">Previous</a></li>
        <?php endif; ?>
        
        <li class="page-item disabled"><span class="page-link">Page <?= $page ?> of <?= $totalPages ?></span></li>
        
        <?php if ($page < $totalPages): ?>
        <li class="page-item"><a class="page-link" href="/sets?page=<?= $page + 1 ?>">Next</a></li>
        <?php endif; ?>
    </ul>
</nav>
