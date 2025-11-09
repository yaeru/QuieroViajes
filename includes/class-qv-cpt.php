<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class QV_CPT {

	public function register() {
		add_action( 'init', [ $this, 'register_viajes' ] );
		add_action( 'init', [ $this, 'register_empresas' ] );
	}

	public function register_viajes() {
		$labels = [
			'name' => 'Viajes',
			'singular_name' => 'Viaje',
			'menu_name' => 'Viajes',
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
			'name' => 'Empresas',
			'singular_name' => 'Empresa',
			'menu_name' => 'Empresas',
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

