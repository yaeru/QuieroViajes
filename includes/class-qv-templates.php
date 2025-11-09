<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class QV_Templates {

    public function init() {
        add_filter('template_include', [ $this, 'cargar_template_viaje' ]);
    }

    public function cargar_template_viaje($template) {
        if (is_singular('viaje')) {

            $user = wp_get_current_user();

            // Roles que usan la vista "conductor"
            $roles_conductor = ['conductor', 'empresa', 'administrator'];

            // Determinar la ruta del template
            if (in_array('pasajero', $user->roles)) {
                $nuevo_template = plugin_dir_path(__FILE__) . '../templates/single-viaje-pasajero.php';
            } else if (array_intersect($roles_conductor, $user->roles)) {
                $nuevo_template = plugin_dir_path(__FILE__) . '../templates/single-viaje-conductor.php';
            }

            // Si existe el archivo, usarlo
            if (isset($nuevo_template) && file_exists($nuevo_template)) {
                return $nuevo_template;
            }
        }

        return $template;
    }
}
