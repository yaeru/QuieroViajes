<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class QV_Tablas {

	public function __construct() {
		add_filter( 'manage_edit-viaje_columns', [ $this, 'agregar_columnas' ] );
		add_action( 'manage_viaje_posts_custom_column', [ $this, 'mostrar_columnas' ], 10, 2 );
		add_filter( 'manage_edit-viaje_sortable_columns', [ $this, 'columnas_ordenables' ] );
		add_action( 'pre_get_posts', [ $this, 'filtrar_viajes_por_empresa' ] );

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

	/**
	 * Filtrar viajes por empresa en el admin
	 */
	public function filtrar_viajes_por_empresa( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) return;

		global $pagenow;
		if ( $pagenow !== 'edit.php' ) return;
		if ( $query->get( 'post_type' ) !== 'viaje' ) return;

		/* Si el usuario es una empresa, mostrar solo sus viajes */
		if ( current_user_can( 'empresa' ) ) {
			$empresa_id = get_current_user_id();
			$meta_query = [
				[
					'key'     => '_qv_empresa',
					'value'   => $empresa_id,
					'compare' => '=',
				],
			];
			$query->set( 'meta_query', $meta_query );
		}
	}

}

new QV_Tablas();

//// EXPORTAR VIAJES ////

/**
 * Construye y ejecuta la query de viajes para exportar, respetando
 * TODOS los filtros activos de la URL (empresa, estado, pago y mes).
 *
 * @return array Lista de objetos WP_Post.
 */
function remiseria_get_viajes_export() {
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

	// Filtro por mes (dropdown de fechas de WordPress: p. ej. 202506)
	$mes_seleccionado = isset( $_GET['m'] ) ? preg_replace( '/[^0-9]/', '', $_GET['m'] ) : '';
	if ( $mes_seleccionado !== '' && strlen( $mes_seleccionado ) >= 6 ) {
		$args['date_query'] = array(
			array(
				'year'  => (int) substr( $mes_seleccionado, 0, 4 ),
				'month' => (int) substr( $mes_seleccionado, 4, 2 ),
			)
		);
	}

	// Si es empresa, forzar filtro por su ID
	if ( current_user_can( 'empresa' ) ) {
		$meta_query[] = array(
			'key'     => '_qv_empresa',
			'value'   => get_current_user_id(),
			'compare' => '='
		);
	}

	if ( ! empty( $meta_query ) ) {
		$args['meta_query'] = $meta_query;
	}

	return get_posts( $args );
}

/**
 * Devuelve las filas (cabecera + datos) listas para exportar.
 *
 * @param array $viajes Lista de objetos WP_Post.
 * @return array
 */
function remiseria_viajes_export_rows( $viajes ) {
	$rows   = array();
	$rows[] = array( 'ID', 'Título', 'Estado', 'Empresa', 'Fecha Programada', 'Importe Total', 'Forma de Pago' );

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
		$empresa_user   = $empresa_id ? get_user_by( 'id', $empresa_id ) : null;
		$empresa_nombre = $empresa_user ? $empresa_user->display_name : '-';

		$rows[] = array(
			$viaje_id,
			$titulo,
			$estado,
			$empresa_nombre,
			$fecha_programada,
			$importe,
			$forma_pago
		);
	}

	return $rows;
}

// Función para generar el archivo CSV y forzar su descarga
function remiseria_download_viajes_csv() {
	if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'empresa' ) ) {
		return;
	}

	$viajes = remiseria_get_viajes_export();
	$rows   = remiseria_viajes_export_rows( $viajes );

	header( 'Content-Type: text/csv; charset=utf-8' );
	$nombre_sitio = sanitize_title( get_bloginfo('name') );
	$fecha_actual = date_i18n( 'Y-m-d_H-i-s' );
	$filename = "{$nombre_sitio}-viajes-{$fecha_actual}.csv";
	header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

	// BOM UTF-8 para que Excel abra bien los acentos en el CSV
	echo "\xEF\xBB\xBF";

	$output = fopen( 'php://output', 'w' );
	foreach ( $rows as $fila ) {
		fputcsv( $output, $fila, ',', '"' );
	}
	fclose( $output );
	exit;
}

// Función para generar el archivo Excel (SpreadsheetML/XML) y forzar su descarga
function remiseria_download_viajes_excel() {
	if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'empresa' ) ) {
		return;
	}

	$viajes = remiseria_get_viajes_export();
	$rows   = remiseria_viajes_export_rows( $viajes );

	$nombre_sitio = sanitize_title( get_bloginfo('name') );
	$fecha_actual = date_i18n( 'Y-m-d_H-i-s' );
	$filename = "{$nombre_sitio}-viajes-{$fecha_actual}.xls";

	header( 'Content-Type: application/vnd.ms-excel; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

	echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
	echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n";
	echo '<Worksheet ss:Name="Viajes">' . "\n";
	echo '<Table>' . "\n";

	foreach ( $rows as $fila ) {
		echo '<Row>' . "\n";
		foreach ( $fila as $celda ) {
			// Limpiar caracteres de control y escapar XML
			$valor = preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', (string) $celda );
			$valor = htmlspecialchars( $valor, ENT_QUOTES, 'UTF-8' );
			echo '<Cell><Data ss:Type="String">' . $valor . '</Data></Cell>' . "\n";
		}
		echo '</Row>' . "\n";
	}

	echo '</Table>' . "\n";
	echo '</Worksheet>' . "\n";
	echo '</Workbook>';
	exit;
}

// Botones para descargar CSV y Excel
function remiseria_add_export_buttons() {
	global $typenow;

	if ( $typenow === 'viaje' ) {
		// Mantener los filtros actuales de la URL
		$query_args = array();

		if ( ! empty( $_GET['filtro_empresa'] ) ) {
			$query_args['filtro_empresa'] = intval( $_GET['filtro_empresa'] );
		}

		if ( ! empty( $_GET['filtro_estado'] ) ) {
			$query_args['filtro_estado'] = sanitize_text_field( $_GET['filtro_estado'] );
		}

		if ( ! empty( $_GET['filtro_pago'] ) ) {
			$query_args['filtro_pago'] = sanitize_text_field( $_GET['filtro_pago'] );
		}

		// Filtro por mes (dropdown de fechas de WordPress)
		if ( ! empty( $_GET['m'] ) ) {
			$query_args['m'] = sanitize_text_field( $_GET['m'] );
		}

		$csv_args   = array_merge( $query_args, array( 'action' => 'remiseria_download_csv' ) );
		$excel_args = array_merge( $query_args, array( 'action' => 'remiseria_download_excel' ) );

		$csv_url   = add_query_arg( $csv_args, admin_url( 'admin-ajax.php' ) );
		$excel_url = add_query_arg( $excel_args, admin_url( 'admin-ajax.php' ) );

		echo '<div class="alignleft actions">';
		echo '<a href="' . esc_url( $csv_url ) . '" class="button button-primary">Descargar CSV</a> ';
		echo '<a href="' . esc_url( $excel_url ) . '" class="button">Descargar Excel</a>';
		echo '</div>';
	}
}

add_action( 'restrict_manage_posts', 'remiseria_add_export_buttons' );

// Acciones AJAX
add_action( 'wp_ajax_remiseria_download_csv', 'remiseria_download_viajes_csv' );
add_action( 'wp_ajax_remiseria_download_excel', 'remiseria_download_viajes_excel' );
