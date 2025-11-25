<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class QV_Templates {

	public function init() {
		add_filter('template_include', [ $this, 'cargar_template_viaje' ]);
	}

	public function cargar_template_viaje($template) {
		if ( is_singular('viaje') ) {

			// Usar siempre el mismo template
			$nuevo_template = plugin_dir_path(__FILE__) . '../templates/single-viaje.php';

			if ( file_exists($nuevo_template) ) {
				return $nuevo_template;
			}
		}

		return $template;
	}
}
