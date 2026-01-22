<?php

// Theme functions.php like file
// Add hooks and customizations here

// Example: Add a custom hook
add_action('cms_init', function() {
    // Custom initialization
});

// Enqueue theme assets
add_action('cms_enqueue_scripts', function() {
    // Enqueue template.css and template.js
    // Assuming the CMS has a way to enqueue
    // For now, just include
    echo '<link rel="stylesheet" href="' . asset('themes/default/template.css') . '">';
    echo '<script src="' . asset('themes/default/template.js') . '"></script>';
});