<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class QV_CPT {

	public function register() {
		add_action( 'init', [ $this, 'register_viajes' ] );
		//add_action( 'init', [ $this, 'register_empresas' ] );
	}

	public function register_viajes() {

		$labels = [
			'name'                  => 'Viajes',
			'singular_name'         => 'Viaje',
			'menu_name'             => 'Viajes',
			'name_admin_bar'        => 'Viaje',
			'add_new'               => 'Añadir nuevo',
			'add_new_item'          => 'Añadir nuevo viaje',
			'edit_item'             => 'Editar viaje',
			'new_item'              => 'Nuevo viaje',
			'view_item'             => 'Ver viaje',
			'view_items'            => 'Ver viajes',
			'search_items'          => 'Buscar viajes',
			'not_found'             => 'No se encontraron viajes',
			'not_found_in_trash'    => 'No hay viajes en la papelera',
			'all_items'             => 'Todos los viajes',
		];

		$args = [
			'labels' => $labels,
			'public' => true,
			'menu_icon' => 'dashicons-location-alt',
			'supports' => ['title'],
			'has_archive' => true,
		];

		register_post_type( 'viaje', $args );
	}

	public function register_empresas() {

		$labels = [
			'name'                  => 'Empresas',
			'singular_name'         => 'Empresa',
			'menu_name'             => 'Empresas',
			'name_admin_bar'        => 'Empresa',
			'add_new'               => 'Añadir nueva',
			'add_new_item'          => 'Añadir nueva empresa',
			'edit_item'             => 'Editar empresa',
			'new_item'              => 'Nueva empresa',
			'view_item'             => 'Ver empresa',
			'view_items'            => 'Ver empresas',
			'search_items'          => 'Buscar empresas',
			'not_found'             => 'No se encontraron empresas',
			'not_found_in_trash'    => 'No hay empresas en la papelera',
			'all_items'             => 'Todas las empresas',
		];

		$args = [
			'labels' => $labels,
			'public' => true,
			'menu_icon' => 'dashicons-building',
			'supports' => ['title'],
			'has_archive' => true,
		];

		register_post_type( 'empresa', $args );
	}
}

/* Ocultar menú empresas a usuarios no administradores */
add_action('admin_menu', function() {
	if ( ! current_user_can('manage_options') ) {
		remove_menu_page('edit.php?post_type=empresa');
	}
}, 999);
