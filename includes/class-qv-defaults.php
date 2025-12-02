<?php
if (!defined('ABSPATH')) exit;

class QV_Default_Values {

    public function __construct() {
        add_action('save_post_viaje', [$this, 'set_default_importe_km'], 10, 3);
    }

    public function set_default_importe_km($post_id, $post, $update) {

        // solo viajes
        if ($post->post_type !== 'viaje') return;

        // evitar autosaves, revisiones y actualizaciones
        if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id) || $update) return;

        // si ya tiene valor, no tocarlo
        if (get_post_meta($post_id, '_qv_importe_km', true) !== '') return;

        // traer el valor por defecto desde ajustes
        $default = get_option('qv_importe_km_default', 0);

        // guardar meta
        update_post_meta($post_id, '_qv_importe_km', $default);
    }
}

new QV_Default_Values();
