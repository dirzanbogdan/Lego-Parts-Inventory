<?php $title = 'Piese'; ?>
<div class="header-actions" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
    <h2>Piese</h2>
    <form method="get" action="/parts" class="search-form" style="display:flex; gap:10px;">
        <input type="text" name="q" value="<?php echo htmlspecialchars($query ?? ''); ?>" placeholder="Elastic Search..." style="padding:5px;">
        <button type="submit" class="btn">Cauta</button>
    </form>
</div>

<table class="data-table">
    <thead>
        <tr>
            <th>Image</th>
            <th>Item No.</th>
            <th>Description</th>
            <th>Category</th>
            <th>Related Items</th>
            <th>Actiuni</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($parts)): ?>
            <tr><td colspan="6">Nu s-au gasit piese.</td></tr>
        <?php else: ?>
            <?php foreach ($parts as $p): ?>
                <tr>
                    <td>
                        <?php if (!empty($p['image_url'])): ?>
                            <img src="<?php echo htmlspecialchars($p['image_url']); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>" width="50" style="object-fit:contain;">
                        <?php else: ?>
                            <span class="no-img">No Img</span>
                        <?php endif; ?>
                    </td>
                    <td><a href="/parts/view?id=<?php echo (int)$p['id']; ?>"><?php echo htmlspecialchars($p['part_code']); ?></a></td>
                    <td><?php echo htmlspecialchars($p['name']); ?></td>
                    <td><?php echo htmlspecialchars($p['category_name'] ?? '-'); ?></td>
                    <td>
                        <?php 
                            $rels = json_decode($p['related_items'] ?? '[]', true);
                            echo count($rels) . ' Items';
                        ?>
                    </td>
                    <td><a href="/parts/view?id=<?php echo (int)$p['id']; ?>" class="btn-small">Detalii</a></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<div class="pagination" style="margin-top:20px;">
    <?php $currPage = max(1, (int)($_GET['page'] ?? 1)); ?>
    <?php if ($currPage > 1): ?>
        <a href="/parts?page=<?php echo $currPage - 1; ?>&q=<?php echo urlencode($query ?? ''); ?>" class="btn">Previous</a>
    <?php endif; ?>
    <?php if (count($parts) >= 50): ?>
        <a href="/parts?page=<?php echo $currPage + 1; ?>&q=<?php echo urlencode($query ?? ''); ?>" class="btn">Next</a>
    <?php endif; ?>
</div>

<div class="manual-add" style="margin-top:40px; border-top:1px solid #ddd; padding-top:20px;">
    <h3>Adauga piesa manual</h3>
    <form method="post" action="/parts/create">
        <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
        <div class="form-row">
            <input type="text" name="name" placeholder="Nume" required>
            <input type="text" name="part_code" placeholder="Cod BrickLink" required>
            <input type="text" name="image_url" placeholder="Image URL">
        </div>
        <button type="submit" class="btn">Salveaza</button>
    </form>
</div>
