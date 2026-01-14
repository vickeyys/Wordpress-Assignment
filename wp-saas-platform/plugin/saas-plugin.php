<?php
/*
Plugin Name: SaaS Platform Plugin
Description: Shows platform banner
Version: 1.0
*/

add_action('wp_footer', function() {
    echo "<p style='text-align:center;color:gray;'>Powered by SaaS Platform</p>";
});
