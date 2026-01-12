<h2>All Themes (Tree View)</h2>

<style>
.theme-tree ul {
    list-style-type: none;
    padding-left: 20px;
}
.theme-tree li {
    margin: 5px 0;
    position: relative;
}
.theme-tree li::before {
    content: '';
    position: absolute;
    top: 0;
    left: -15px;
    border-left: 1px solid #ccc;
    border-bottom: 1px solid #ccc;
    width: 15px;
    height: 15px;
}
.theme-tree > ul > li::before {
    display: none;
}
.theme-card {
    display: inline-block;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 5px 10px;
    background: #fff;
    min-width: 200px;
}
.theme-img {
    height: 30px;
    width: 30px;
    object-fit: contain;
    margin-right: 5px;
    vertical-align: middle;
}
</style>

<div class="theme-tree">
<?php
function renderTree($nodes) {
    if (empty($nodes)) return;
    echo '<ul>';
    foreach ($nodes as $theme) {
        echo '<li>';
        
        $img = (!empty($theme->img_url) && (strpos($theme->img_url, '/images') === 0 || strpos($theme->img_url, '/parts_images') === 0)) 
            ? htmlspecialchars($theme->img_url) 
            : '/images/no-image.png';
            
        echo '<div class="theme-card">';
        echo '<img src="' . $img . '" class="theme-img" onerror="this.onerror=null; this.src=\'/images/no-image.png\';">';
        echo '<strong>' . htmlspecialchars($theme->name) . '</strong> ';
        echo '<a href="/sets?theme_id=' . $theme->id . '" class="btn btn-sm btn-link">View Sets</a>';
        echo '</div>';

        if (!empty($theme->children)) {
            renderTree($theme->children);
        }
        echo '</li>';
    }
    echo '</ul>';
}

renderTree($themesTree ?? []);
?>
</div>
