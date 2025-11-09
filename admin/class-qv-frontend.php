<?php 
if ( ! defined( 'ABSPATH' ) ) exit;

add_action('wp_enqueue_scripts', function() {
    if (is_singular('viaje')) {
    	$api_key = defined('QV_GOOGLE_MAPS_API_KEY') ? QV_GOOGLE_MAPS_API_KEY : '';
        // Cargar Google Maps API
        wp_enqueue_script(
            'google-maps',
            //'https://maps.googleapis.com/maps/api/js?key=AIzaSyAq60a-o17nMZ5QYINh87y1tmDObUADLAs&libraries=places',
            'https://maps.googleapis.com/maps/api/js?key=' . esc_attr($api_key) . '&libraries=places',
            [],
            null,
            true
        );

        // Cargar tu JS de mapa
        wp_enqueue_script(
            'qv-map',
            //plugin_dir_url(__FILE__) . '/../assets/js/qv-map.js',
            QV_URL . 'assets/js/qv-map.js',
            ['google-maps'],
            '1.0.0',
            true
        );
    }
});


?>