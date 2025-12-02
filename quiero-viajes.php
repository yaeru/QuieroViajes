<?php
/**
 * Plugin Name: Quiero Viajes
 * Plugin URI: https://quierohacertuweb.com
 * Description: Gestión de viajes con detalles, origen/destino y cálculo de importes.
 * Version: 0.1.92
 * Author: Yael Duckwen
 * Author URI: https://quierohacertuweb.com
 * License: GPL2
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'QV_PATH', plugin_dir_path( __FILE__ ) );
define( 'QV_URL', plugin_dir_url( __FILE__ ) );

// Cargar archivos principales
require_once QV_PATH . 'includes/class-qv-cpt.php';
require_once QV_PATH . 'admin/class-qv-admin.php';
require_once QV_PATH . 'admin/class-qv-frontend.php';

// Dentro de quiero-viajes.php
require_once QV_PATH . 'includes/class-qv-emails.php';
require_once QV_PATH . 'includes/class-qv-email-templates.php';
require_once QV_PATH . 'includes/class-qv-viajes-utils.php';
require_once QV_PATH . 'includes/class-qv-tablas.php';
require_once QV_PATH . 'includes/class-qv-settings.php';
require_once QV_PATH . 'includes/class-qv-conductores.php';
require_once QV_PATH . 'includes/class-qv-usuarios.php';
require_once QV_PATH . 'includes/class-qv-templates.php';


/* SOLO USUARIOS VEN LA WEB */
function restringir_acceso_solo_usuarios() {
    if ( !is_user_logged_in() && !is_page('login') ) {
        // Redirige a la página de inicio de sesión si no están logueados
        wp_redirect( wp_login_url() );
        exit;
    }
}
add_action( 'template_redirect', 'restringir_acceso_solo_usuarios' );

// Cambiar logo en el login de WordPress
add_action('login_enqueue_scripts', function() {
    ?>
    <style type="text/css">
        /* Cambiar el logo */
        #login h1 a {
            background-image: url('https://remisesaltonivel.com.ar/wp-content/uploads/2024/12/Logo-Alto-Nivel-small.png');
            background-size: contain;
            width: 100%;
            height: 80px; /* ajustar según tu logo */
        }
    </style>
    <?php
});


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
	if ( is_admin() ) {
		new QV_Settings_Page();
	}

    // En frontend y backend (para interceptar plantillas)
	$qv_templates = new QV_Templates();
	$qv_templates->init();

	$qv_emails = new QV_Emails();
}
add_action( 'plugins_loaded', 'qv_init_plugin' );


/* Limpieza Wordpress opcional */
add_action( 'admin_menu', function() {
	remove_menu_page( 'edit.php' );
	remove_menu_page( 'edit-comments.php' );
	//remove_menu_page( 'tools.php' );
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