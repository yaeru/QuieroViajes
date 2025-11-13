<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class QV_Tablas {

	public function __construct() {
		add_filter( 'manage_edit-viaje_columns', [ $this, 'agregar_columnas' ] );
		add_action( 'manage_viaje_posts_custom_column', [ $this, 'mostrar_columnas' ], 10, 2 );
		add_filter( 'manage_edit-viaje_sortable_columns', [ $this, 'columnas_ordenables' ] );

		// Filtros personalizados
		add_action( 'restrict_manage_posts', [ $this, 'agregar_filtros' ] );
		add_action( 'pre_get_posts', [ $this, 'filtrar_viajes' ] );
	}

	/**
	 * Define las nuevas columnas del listado
	 */
	public function agregar_columnas( $columns ) {
		unset( $columns['date'] );

		$new = [];
		$new['cb']               = $columns['cb'];
		$new['id']               = __( 'ID', 'quiero-viajes' );
		$new['title']            = __( 'Título', 'quiero-viajes' );
		$new['estado']           = __( 'Estado', 'quiero-viajes' );
		$new['fecha_programada'] = __( 'Fecha Programada', 'quiero-viajes' );
		$new['empresa']          = __( 'Empresa', 'quiero-viajes' );
		$new['importe_total']    = __( 'Importe total', 'quiero-viajes' );
		$new['forma_pago']       = __( 'Forma de pago', 'quiero-viajes' );

		return $new;
	}

	/**
	 * Muestra los valores en cada columna
	 */
	public function mostrar_columnas( $column, $post_id ) {
		switch ( $column ) {

			case 'id':
			echo (int) $post_id;
			break;

			case 'estado':
			$estado = get_post_meta( $post_id, '_qv_estado', true );
			echo $estado ? esc_html( ucfirst( $estado ) ) : '-';
			break;

			case 'fecha_programada':
			$fecha = get_post_meta( $post_id, '_qv_fecha', true );
			$hora  = get_post_meta( $post_id, '_qv_hora', true );

			if ( $fecha ) {
				$fecha_formateada = date_i18n( 'd/m/Y', strtotime( $fecha ) );
				echo esc_html( $fecha_formateada );
				if ( $hora ) echo ' a las ' . esc_html( $hora ) . ' hs';
			} else {
				echo '-';
			}
			break;

			case 'empresa':
			$empresa_id = get_post_meta( $post_id, '_qv_empresa', true );
			if ( $empresa_id ) {
				$usuario = get_user_by( 'id', $empresa_id );
				if ( $usuario && ! empty( $usuario->display_name ) ) {
					echo esc_html( $usuario->display_name );
				} else {
					echo '-';
				}
			} else {
				echo '-';
			}
			break;

			case 'importe_total':
			$total = get_post_meta( $post_id, '_qv_total_general', true );
			if ( $total === '' ) $total = get_post_meta( $post_id, '_qv_importe_total', true );
			if ( $total ) {
				echo '$' . number_format( ceil( floatval( $total ) ), 0, ',', '.' );
			} else {
				echo '-';
			}
			break;

			case 'forma_pago':
			$forma_pago = get_post_meta( $post_id, '_qv_pago', true );
			echo $forma_pago ? esc_html( ucfirst( $forma_pago ) ) : '-';
			break;
		}
	}

	/**
	 * Define columnas ordenables
	 */
	public function columnas_ordenables( $columns ) {
		$columns['id']               = 'ID';
		$columns['fecha_programada'] = 'fecha_programada';
		$columns['importe_total']    = 'importe_total';
		return $columns;
	}

	/**
	 * Agrega filtros personalizados arriba del listado
	 */
	public function agregar_filtros( $post_type ) {
		if ( $post_type !== 'viaje' ) return;

		// Filtro Empresa (obtener IDs de usuario desde los viajes existentes)
		global $wpdb;
		$empresa_ids = $wpdb->get_col("
			SELECT DISTINCT meta_value 
			FROM {$wpdb->postmeta}
			WHERE meta_key = '_qv_empresa' 
			AND meta_value != ''
			");

		$empresa_actual = isset( $_GET['filtro_empresa'] ) ? intval( $_GET['filtro_empresa'] ) : '';

		echo '<select name="filtro_empresa">';
		echo '<option value="">Todas las empresas</option>';

		if ( ! empty( $empresa_ids ) ) {
			foreach ( $empresa_ids as $empresa_id ) {
				$usuario = get_user_by( 'id', (int) $empresa_id );
				if ( $usuario ) {
					printf(
						'<option value="%d" %s>%s</option>',
						$usuario->ID,
						selected( $empresa_actual, $usuario->ID, false ),
						esc_html( $usuario->display_name )
					);
				}
			}
		}

		echo '</select>';


		// Filtro Estado
		$estados = [ 'programado', 'confirmado', 'curso', 'finalizado', 'cancelado' ];
		$estado_actual = isset( $_GET['filtro_estado'] ) ? sanitize_text_field( $_GET['filtro_estado'] ) : '';

		echo '<select name="filtro_estado">';
		echo '<option value="">Todos los estados</option>';
		foreach ( $estados as $estado ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $estado ),
				selected( $estado_actual, $estado, false ),
				ucfirst( $estado )
			);
		}
		echo '</select>';

		// Filtro Forma de Pago
		$formas = [ 'efectivo', 'transferencia', 'tarjeta', 'cuenta corriente' ];
		$pago_actual = isset( $_GET['filtro_pago'] ) ? sanitize_text_field( $_GET['filtro_pago'] ) : '';

		echo '<select name="filtro_pago">';
		echo '<option value="">Todas las formas de pago</option>';
		foreach ( $formas as $forma ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $forma ),
				selected( $pago_actual, $forma, false ),
				ucfirst( $forma )
			);
		}
		echo '</select>';
	}

	/**
	 * Aplica los filtros a la query principal
	 */
	public function filtrar_viajes( $query ) {
		global $pagenow;

		if ( ! is_admin() || $pagenow !== 'edit.php' || $query->get('post_type') !== 'viaje' ) {
			return;
		}

		$meta_query = [];

		// Empresa
		if ( ! empty( $_GET['filtro_empresa'] ) ) {
			$meta_query[] = [
				'key'   => '_qv_empresa',
				'value' => intval( $_GET['filtro_empresa'] ),
			];
		}

		// Estado
		if ( ! empty( $_GET['filtro_estado'] ) ) {
			$meta_query[] = [
				'key'   => '_qv_estado',
				'value' => sanitize_text_field( $_GET['filtro_estado'] ),
			];
		}

		// Forma de pago
		if ( ! empty( $_GET['filtro_pago'] ) ) {
			$meta_query[] = [
				'key'   => '_qv_pago',
				'value' => sanitize_text_field( $_GET['filtro_pago'] ),
			];
		}

		if ( ! empty( $meta_query ) ) {
			$query->set( 'meta_query', $meta_query );
		}
	}
}

new QV_Tablas();

//// EXPORTAR VIAJES ////

// Función para generar el archivo CSV y forzar su descarga
// Función para generar el archivo CSV y forzar su descarga
function remiseria_download_viajes_csv() {
	if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'empresa' ) ) {
		return;
	}

	$meta_query = array();

    // Aplicar filtros activos desde la URL (GET)
	if ( ! empty( $_GET['filtro_empresa'] ) ) {
		$meta_query[] = array(
			'key'     => '_qv_empresa',
			'value'   => intval( $_GET['filtro_empresa'] ),
			'compare' => '='
		);
	}

	if ( ! empty( $_GET['filtro_estado'] ) ) {
		$meta_query[] = array(
			'key'     => '_qv_estado',
			'value'   => sanitize_text_field( $_GET['filtro_estado'] ),
			'compare' => '='
		);
	}

	if ( ! empty( $_GET['filtro_pago'] ) ) {
		$meta_query[] = array(
			'key'     => '_qv_pago',
			'value'   => sanitize_text_field( $_GET['filtro_pago'] ),
			'compare' => '='
		);
	}

    // Base de la query
	$args = array(
		'post_type'      => 'viaje',
		'posts_per_page' => -1,
		'post_status'    => 'any'
	);

	if ( ! empty( $meta_query ) ) {
		$args['meta_query'] = $meta_query;
	}

    // Si es empresa, forzar filtro por su ID
	if ( current_user_can( 'empresa' ) ) {
		$empresa_id = get_current_user_id();
		$args['meta_query'][] = array(
			'key'     => '_qv_empresa',
			'value'   => $empresa_id,
			'compare' => '='
		);
	}

	$viajes = get_posts( $args );


	header( 'Content-Type: text/csv; charset=utf-8' );
	$nombre_sitio = sanitize_title( get_bloginfo('name') );
	$fecha_actual = date_i18n( 'Y-m-d_H-i-s' );
	$filename = "{$nombre_sitio}-viajes-{$fecha_actual}.csv";
	header( 'Content-Disposition: attachment; filename="' . $filename . '"' );


	$output = fopen( 'php://output', 'w' );

	fputcsv( $output, array( 'ID', 'Título', 'Estado', 'Empresa', 'Fecha Programada', 'Importe Total', 'Forma de Pago' ) );

	foreach ( $viajes as $viaje ) {
		$viaje_id       = $viaje->ID;
		$titulo         = html_entity_decode( wp_strip_all_tags( $viaje->post_title ) );
		$estado         = get_post_meta( $viaje_id, '_qv_estado', true );
		$empresa_id     = get_post_meta( $viaje_id, '_qv_empresa', true );
		$fecha          = get_post_meta( $viaje_id, '_qv_fecha', true );
		$hora           = get_post_meta( $viaje_id, '_qv_hora', true );
		$forma_pago     = get_post_meta( $viaje_id, '_qv_pago', true );

        // Buscar importe en cualquiera de los dos posibles campos
		$importe = get_post_meta( $viaje_id, '_qv_total_general', true );
		if ( $importe === '' ) {
			$importe = get_post_meta( $viaje_id, '_qv_importe_total', true );
		}

        // Formatear importe si existe
		if ( $importe !== '' && is_numeric( $importe ) ) {
			$importe = '$' . number_format( ceil( floatval( $importe ) ), 0, ',', '.' );
		} else {
			$importe = '-';
		}

        // Formatear fecha y hora
		$fecha_programada = $fecha;
		if ( ! empty( $hora ) ) {
			$fecha_programada .= ' ' . $hora;
		}

        // Obtener nombre de empresa
		$empresa_user = $empresa_id ? get_user_by( 'id', $empresa_id ) : null;
		$empresa_nombre = $empresa_user ? $empresa_user->display_name : '-';

		fputcsv( $output, array(
			$viaje_id,
			$titulo,
			$estado,
			$empresa_nombre,
			$fecha_programada,
			$importe,
			$forma_pago
		), ',', '"' );
	}

	fclose( $output );
	exit;
}

// Botón para descargar CSV
// Botón para descargar CSV con los filtros activos
function remiseria_add_csv_download_button() {
	global $typenow;

	if ( $typenow === 'viaje' ) {
        // Mantener los filtros actuales de la URL
		$query_args = array(
			'action' => 'remiseria_download_csv'
		);

		if ( ! empty( $_GET['filtro_empresa'] ) ) {
			$query_args['filtro_empresa'] = intval( $_GET['filtro_empresa'] );
		}

		if ( ! empty( $_GET['filtro_estado'] ) ) {
			$query_args['filtro_estado'] = sanitize_text_field( $_GET['filtro_estado'] );
		}

		if ( ! empty( $_GET['filtro_pago'] ) ) {
			$query_args['filtro_pago'] = sanitize_text_field( $_GET['filtro_pago'] );
		}

		$download_url = add_query_arg( $query_args, admin_url( 'admin-ajax.php' ) );

		echo '<div class="alignleft actions">';
		echo '<a href="' . esc_url( $download_url ) . '" class="button button-primary">Descargar CSV</a>';
		echo '</div>';
	}
}

add_action( 'restrict_manage_posts', 'remiseria_add_csv_download_button' );

// Acción AJAX
add_action( 'wp_ajax_remiseria_download_csv', 'remiseria_download_viajes_csv' );
