<?php
add_action( 'admin_enqueue_scripts', 'comments_mover_assets');
if ( ! function_exists( 'comments_mover_assets' ) ) {
    function comments_mover_assets() {
        wp_enqueue_style( 'comments-mover-style', plugins_url('css/admin-style.css', __FILE__) );
    }
}
?>