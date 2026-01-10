<?php
// Create symlink for images
$target = __DIR__ . '/../parts and sets/png';
$link = __DIR__ . '/parts_images';

if (file_exists($link)) {
    echo "Link already exists.\n";
} else {
    if (symlink($target, $link)) {
        echo "Symlink created successfully: $link -> $target\n";
    } else {
        echo "Failed to create symlink.\n";
        // Fallback: Try relative path if absolute fails
        $targetRelative = '../parts and sets/png';
        if (symlink($targetRelative, $link)) {
             echo "Symlink created successfully (relative): $link -> $targetRelative\n";
        } else {
             echo "Failed to create relative symlink too.\n";
        }
    }
}
