<?php
/**
 * Plugin Name: Quiero Viajes
 * Plugin URI: https://quierohacertuweb.com
 * Description: Gestión de viajes con detalles, origen/destino y cálculo de importes.
 * Version: 0.1.4
 * Author: Yael Duckwen
 * Author URI: https://quierohacertuweb.com
 * License: GPL2
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'QV_PATH', plugin_dir_path( __FILE__ ) );
define( 'QV_URL', plugin_dir_url( __FILE__ ) );
define( 'QV_API', "PEPE" );

// Cargar archivos principales
require_once QV_PATH . 'includes/class-qv-cpt.php';
require_once QV_PATH . 'admin/class-qv-admin.php';
require_once QV_PATH . 'admin/class-qv-frontend.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-qv-conductores.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-qv-usuarios.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-qv-templates.php';

// Cargar estilos del frontend del plugin
function qv_enqueue_frontend_styles() {
	if ( ! is_admin() ) {
		wp_enqueue_style(
			'qv-frontend-style',
			plugin_dir_url( __FILE__ ) . 'assets/css/style.css',
			array(),
			filemtime( plugin_dir_path( __FILE__ ) . 'assets/css/style.css' )
		);
	}
}
add_action( 'wp_enqueue_scripts', 'qv_enqueue_frontend_styles' );

// Cargar estilos solo en el backend (área de administración)
function qv_enqueue_admin_styles( $hook ) {
	global $post_type;
	if ( $post_type === 'viaje' ) {
		wp_enqueue_style(
			'qv-admin-style',
			plugin_dir_url( __FILE__ ) . 'assets/css/admin.css',
			array(),
			filemtime( plugin_dir_path( __FILE__ ) . 'assets/css/admin.css' )
		);
	}
}
add_action( 'admin_enqueue_scripts', 'qv_enqueue_admin_styles' );



// Inicializar plugin
function qv_init_plugin() {
    // Registrar CPTs
	$cpt = new QV_CPT();
	$cpt->register();

    // Solo en admin (metaboxes, gestión de usuarios, etc.)
	if ( is_admin() ) {
		$admin = new QV_Admin();
		$admin->init();

		$qv_conductores = new QV_Conductores();
		$qv_conductores->init();

		$qv_usuarios = new QV_Usuarios();
		$qv_usuarios->init();
	}

    // En frontend y backend (para interceptar plantillas)
	$qv_templates = new QV_Templates();
	$qv_templates->init();
}
add_action( 'plugins_loaded', 'qv_init_plugin' );



/* Limpieza Wordpress opcional */
// Quitar menús de WordPress innecesarios
add_action( 'admin_menu', function() {
    remove_menu_page( 'edit.php' );          // Entradas
    remove_menu_page( 'edit-comments.php' ); // Comentarios
});
add_action( 'admin_head', function() {
    // Ocultar secciones del perfil con CSS
	echo '<style>
	select#role,
	select#display_name {width: 25em;}
	tr.user-admin-color-wrap,
	tr.user-comment-shortcuts-wrap,
	tr.user-admin-bar-front-wrap,
	tr.user-language-wrap,
	tr.user-description-wrap {
		display: none !important;
	}
	</style>';
});
