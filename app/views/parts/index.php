<h2>All Parts</h2>
<div class="row">
    <?php foreach ($parts as $part): ?>
    <div class="col-md-3 mb-4">
        <div class="card h-100">
            <div class="text-center p-3">
                <img src="<?= !empty($part->img_url) ? htmlspecialchars($part->img_url) : '/images/no-image.png' ?>" 
                     class="card-img-top part-img" 
                     alt="<?= htmlspecialchars($part->name) ?>" 
                     style="max-height: 150px; width: auto;" 
                     onerror="this.onerror=null; this.src='/images/no-image.png';">
            </div>
            <div class="card-body">
                <h5 class="card-title"><?= htmlspecialchars($part->name) ?></h5>
                <p class="card-text">
                    <small class="text-muted"><?= htmlspecialchars($part->part_num) ?></small>
                </p>
                <a href="/parts/<?= htmlspecialchars($part->part_num) ?>" class="btn btn-primary btn-sm">View</a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<nav>
    <ul class="pagination justify-content-center">
        <?php if ($page > 1): ?>
        <li class="page-item"><a class="page-link" href="/parts?page=<?= $page - 1 ?>">Previous</a></li>
        <?php endif; ?>
        
        <li class="page-item disabled"><span class="page-link">Page <?= $page ?> of <?= $totalPages ?></span></li>
        
        <?php if ($page < $totalPages): ?>
        <li class="page-item"><a class="page-link" href="/parts?page=<?= $page + 1 ?>">Next</a></li>
        <?php endif; ?>
    </ul>
</nav>
