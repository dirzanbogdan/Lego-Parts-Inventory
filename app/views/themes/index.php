<h2>All Themes (Tree View)</h2>

<style>
.theme-tree-container {
    column-count: 3;
    column-gap: 20px;
}
@media (max-width: 992px) {
    .theme-tree-container {
        column-count: 2;
    }
}
@media (max-width: 576px) {
    .theme-tree-container {
        column-count: 1;
    }
}
.theme-tree ul {
    list-style-type: none;
    padding-left: 20px;
    margin: 0;
}
/* Ensure top-level items don't break across columns */
.theme-tree > ul > li {
    break-inside: avoid;
    page-break-inside: avoid;
    margin-bottom: 15px;
    border: 1px solid #eee;
    padding: 10px;
    border-radius: 5px;
    background-color: #fafafa;
}
.theme-tree li {
    margin: 3px 0;
    position: relative;
}
/* Connector lines for nested items */
.theme-tree li::before {
    content: '';
    position: absolute;
    top: 0;
    left: -15px;
    border-left: 1px solid #ccc;
    border-bottom: 1px solid #ccc;
    width: 15px;
    height: 12px;
}
.theme-tree > ul > li::before {
    display: none;
}
/* Hide root level connectors within the container */
.theme-tree > ul {
    padding-left: 0;
}

.theme-card {
    display: inline-block;
    padding: 2px 5px;
}
</style>

<div class="theme-tree theme-tree-container">
<?php
function renderTree($nodes) {
    if (empty($nodes)) return;
    echo '<ul>';
    foreach ($nodes as $theme) {
        echo '<li>';
        
        echo '<div class="theme-card">';
        echo '<strong>' . htmlspecialchars($theme->name) . '</strong> ';
        echo '<a href="/sets?theme_id=' . $theme->id . '" class="btn btn-sm btn-link p-0" style="font-size: 0.85em;">Sets</a>';
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
