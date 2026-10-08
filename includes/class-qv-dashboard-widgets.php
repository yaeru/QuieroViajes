<?php
/**
 * Widgets del escritorio (dashboard) de Quiero Viajes.
 *
 * - Resumen del mes: viajes, ganancias (solo finalizados) y comparación % con el mes anterior.
 * - Top 5 empresas con más viajes.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class QV_Dashboard_Widgets {

	public function __construct() {
		add_action( 'wp_dashboard_setup', [ $this, 'registrar_widgets' ] );
		add_action( 'wp_ajax_qv_chart_viajes', [ $this, 'ajax_chart_viajes' ] );
	}

	/**
	 * Registra los widgets en el escritorio.
	 */
	public function registrar_widgets() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		wp_add_dashboard_widget(
			'qv_resumen_mes',
			'Quiero Viajes — Resumen del mes',
			[ $this, 'render_resumen_mes' ]
		);

		wp_add_dashboard_widget(
			'qv_top_empresas',
			'Quiero Viajes — Top 5 empresas',
			[ $this, 'render_top_empresas' ]
		);

		wp_add_dashboard_widget(
			'qv_timeline_viajes',
			'Quiero Viajes — Viajes en el tiempo',
			[ $this, 'render_timeline_viajes' ]
		);
	}

	/**
	 * Estadísticas de un mes basadas en la fecha programada (_qv_fecha).
	 *
	 * @param string $mes Formato Y-m.
	 * @return array { total, finalizados, ganancias }
	 */
	public function stats_mes( $mes ) {
		$args = [
			'post_type'      => 'viaje',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => [
				[ 'key' => '_qv_fecha', 'value' => $mes, 'compare' => 'LIKE' ],
			],
		];

		$ids         = get_posts( $args );
		$total       = count( $ids );
		$finalizados = 0;
		$ganancias   = 0.0;

		foreach ( $ids as $id ) {
			if ( get_post_meta( $id, '_qv_estado', true ) === 'finalizado' ) {
				$finalizados++;

				$importe = get_post_meta( $id, '_qv_total_general', true );
				if ( $importe === '' ) {
					$importe = get_post_meta( $id, '_qv_importe_total', true );
				}
				$ganancias += floatval( $importe );
			}
		}

		return [
			'total'       => $total,
			'finalizados' => $finalizados,
			'ganancias'   => $ganancias,
		];
	}

	/**
	 * Top N empresas por cantidad de viajes.
	 *
	 * @param int $limite
	 * @return array
	 */
	public function top_empresas( $limite = 5 ) {
		$args = [
			'post_type'      => 'viaje',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		];

		$ids       = get_posts( $args );
		$conteo    = [];
		$ganancias = [];

		foreach ( $ids as $id ) {
			$empresa = intval( get_post_meta( $id, '_qv_empresa', true ) );
			if ( $empresa <= 0 ) {
				continue;
			}

			$conteo[ $empresa ] = ( $conteo[ $empresa ] ?? 0 ) + 1;

			$importe = get_post_meta( $id, '_qv_total_general', true );
			if ( $importe === '' ) {
				$importe = get_post_meta( $id, '_qv_importe_total', true );
			}
			$ganancias[ $empresa ] = ( $ganancias[ $empresa ] ?? 0 ) + floatval( $importe );
		}

		arsort( $conteo );
		$top = array_slice( $conteo, 0, $limite, true );

		$filas = [];
		foreach ( $top as $empresa => $n ) {
			$usuario = get_user_by( 'id', $empresa );
			$nombre  = $usuario ? $usuario->display_name : 'Empresa #' . $empresa;
			$filas[] = [
				'nombre'    => $nombre,
				'viajes'    => $n,
				'ganancias' => $ganancias[ $empresa ] ?? 0,
			];
		}

		return $filas;
	}

	/**
	 * Render del widget resumen del mes.
	 */
	public function render_resumen_mes() {
		$mes_actual   = current_time( 'Y-m' );
		$mes_anterior = date( 'Y-m', strtotime( '-1 month', current_time( 'timestamp' ) ) );

		$actual   = $this->stats_mes( $mes_actual );
		$anterior = $this->stats_mes( $mes_anterior );

		$dif_ganancias = ( $anterior['ganancias'] > 0 )
			? ( ( $actual['ganancias'] - $anterior['ganancias'] ) / $anterior['ganancias'] ) * 100
			: null;
		$dif_total     = ( $anterior['total'] > 0 )
			? ( ( $actual['total'] - $anterior['total'] ) / $anterior['total'] ) * 100
			: null;

		$etiqueta_actual   = date_i18n( 'F Y', strtotime( $mes_actual . '-01' ) );
		$etiqueta_anterior = date_i18n( 'F Y', strtotime( $mes_anterior . '-01' ) );

		?>
		<style>
			.qv-dash-resumen { font-size: 13px; line-height: 1.9; }
			.qv-dash-resumen .qv-fila { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f0f0f1; padding: 6px 0; }
			.qv-dash-resumen .qv-fila:last-child { border-bottom: 0; }
			.qv-dash-resumen .qv-grande { font-size: 18px; font-weight: 700; }
			.qv-dash-badge { font-weight: 700; margin-left: 6px; }
			.qv-dash-sub { color: #727272; font-size: 12px; margin-top: 2px; }
		</style>
		<div class="qv-dash-resumen">
			<div class="qv-fila">
				<span>Viajes de <strong><?php echo esc_html( $etiqueta_actual ); ?></strong></span>
				<span class="qv-grande"><?php echo (int) $actual['total']; ?></span>
			</div>
			<div class="qv-fila">
				<span>Finalizados</span>
				<span><?php echo (int) $actual['finalizados']; ?></span>
			</div>
			<div class="qv-fila">
				<span>Ganancias (finalizados)</span>
				<span class="qv-grande">$<?php echo esc_html( number_format( $actual['ganancias'], 0, ',', '.' ) ); ?></span>
			</div>
			<div class="qv-fila" style="border-bottom:0;">
				<span>vs <?php echo esc_html( $etiqueta_anterior ); ?></span>
				<span>
					Ganancias <?php echo $this->badge_cambio( $dif_ganancias ); ?>
					<span class="qv-dash-sub">· Viajes <?php echo $this->badge_cambio( $dif_total ); ?></span>
				</span>
			</div>
		</div>
		<?php
	}

	/**
	 * Render del widget top 5 empresas.
	 */
	public function render_top_empresas() {
		$filas = $this->top_empresas( 5 );

		if ( empty( $filas ) ) {
			echo '<p style="color:#727272;">Todavía no hay viajes con empresa asignada.</p>';
			return;
		}

		?>
		<style>
			.qv-dash-top { width: 100%; border-collapse: collapse; font-size: 13px; }
			.qv-dash-top th, .qv-dash-top td { text-align: left; padding: 6px 8px; border-bottom: 1px solid #f0f0f1; }
			.qv-dash-top th { color: #727272; font-weight: 600; }
			.qv-dash-top .qv-num { text-align: right; }
		</style>
		<table class="qv-dash-top">
			<thead>
				<tr>
					<th>#</th>
					<th>Empresa</th>
					<th class="qv-num">Viajes</th>
					<th class="qv-num">Ganancias</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $filas as $i => $fila ) : ?>
					<tr>
						<td><?php echo (int) ( $i + 1 ); ?></td>
						<td><strong><?php echo esc_html( $fila['nombre'] ); ?></strong></td>
						<td class="qv-num"><?php echo (int) $fila['viajes']; ?></td>
						<td class="qv-num">$<?php echo esc_html( number_format( $fila['ganancias'], 0, ',', '.' ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Render del widget de línea de tiempo (gráfico de viajes por día).
	 */
	public function render_timeline_viajes() {
		$dias = isset( $_GET['qv_dias'] ) ? max( 3, min( 90, intval( $_GET['qv_dias'] ) ) ) : 7;
		$nonce = wp_create_nonce( 'qv_chart_viajes' );
		$ajaxurl = esc_url( admin_url( 'admin-ajax.php' ) );

		$opciones = [ 7 => 'Últimos 7 días', 14 => 'Últimos 14 días', 30 => 'Últimos 30 días' ];
		?>
		<style>
			.qv-timeline .qv-select { margin-bottom: 10px; width: 100%; max-width: 220px; }
			.qv-timeline-sub { color: #727272; font-size: 12px; margin-top: 4px; }
		</style>
		<div class="qv-timeline">
			<select id="qv-dias-select" class="qv-select">
				<?php foreach ( $opciones as $valor => $etiqueta ) : ?>
					<option value="<?php echo (int) $valor; ?>" <?php selected( $dias, $valor ); ?>><?php echo esc_html( $etiqueta ); ?></option>
				<?php endforeach; ?>
			</select>
			<div id="qv-timeline-chart"><?php echo $this->svg_viajes( $dias ); // phpcs:ignore ?></div>
			<p class="qv-timeline-sub">Viajes por día según fecha programada.</p>
		</div>
		<script>
			window.qvDash = window.qvDash || { ajaxurl: <?php echo wp_json_encode( $ajaxurl ); ?>, nonce: <?php echo wp_json_encode( $nonce ); ?> };
			jQuery(function($){
				$('#qv-dias-select').on('change', function(){
					$.post(qvDash.ajaxurl, { action: 'qv_chart_viajes', dias: $(this).val(), nonce: qvDash.nonce }, function(r){
						if (r && r.success) { $('#qv-timeline-chart').html(r.data.svg); }
					});
				});
			});
		</script>
		<?php
	}

	/**
	 * Respuesta AJAX para redibujar el gráfico según los días elegidos.
	 */
	public function ajax_chart_viajes() {
		check_ajax_referer( 'qv_chart_viajes', 'nonce' );
		$dias = isset( $_POST['dias'] ) ? max( 3, min( 90, intval( $_POST['dias'] ) ) ) : 7;
		wp_send_json_success( [ 'svg' => $this->svg_viajes( $dias ) ] );
	}

	/**
	 * Genera un gráfico SVG de línea con los viajes por día de los últimos $dias días.
	 *
	 * @param int $dias
	 * @return string SVG.
	 */
	public function svg_viajes( $dias ) {
		$dias = max( 3, min( 90, intval( $dias ) ) );

		$hoy    = current_time( 'Y-m-d' );
		$inicio = date( 'Y-m-d', strtotime( '-' . ( $dias - 1 ) . ' days', current_time( 'timestamp' ) ) );

		// Inicializar conteo por día
		$conteos = [];
		for ( $i = 0; $i < $dias; $i++ ) {
			$fecha = date( 'Y-m-d', strtotime( '-' . $i . ' days', current_time( 'timestamp' ) ) );
			$conteos[ $fecha ] = 0;
		}

		// Viajes del período según fecha programada (_qv_fecha)
		$args = [
			'post_type'      => 'viaje',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => [
				[ 'key' => '_qv_fecha', 'value' => [ $inicio, $hoy ], 'compare' => 'BETWEEN', 'type' => 'DATE' ],
			],
		];

		foreach ( get_posts( $args ) as $id ) {
			$f = get_post_meta( $id, '_qv_fecha', true );
			if ( isset( $conteos[ $f ] ) ) {
				$conteos[ $f ]++;
			}
		}

		ksort( $conteos );
		$valores = array_values( $conteos );
		$fechas  = array_keys( $conteos );
		$max     = max( 1, max( $valores ) );
		$n       = count( $valores );

		$W = 560;
		$H = 185;
		$pad   = 22;
		$innerW = $W - ( $pad * 2 );
		$innerH = $H - ( $pad * 2 );
		$step   = $n > 1 ? ( $innerW / ( $n - 1 ) ) : 0;

		// Puntos y línea
		$pt   = '';
		$dots = '';
		foreach ( $valores as $i => $v ) {
			$x = $pad + ( $i * $step );
			$y = $pad + $innerH - ( ( $v / $max ) * $innerH );
			$pt .= ( $i ? ' ' : '' ) . round( $x ) . ',' . round( $y );
			$dots .= '<circle cx="' . round( $x ) . '" cy="' . round( $y ) . '" r="3.5" fill="#c14242"/>';
		}

		// Líneas de referencia horizontales
		$grid = '';
		for ( $g = 0; $g <= 4; $g++ ) {
			$gy = $pad + ( $innerH * ( $g / 4 ) );
			$grid .= '<line x1="' . $pad . '" y1="' . round( $gy ) . '" x2="' . ( $W - $pad ) . '" y2="' . round( $gy ) . '" stroke="#eee" stroke-width="1"/>';
		}

		// Etiquetas del eje X (máx ~8)
		$label_step = max( 1, (int) ceil( $n / 8 ) );
		$labels = '';
		for ( $i = 0; $i < $n; $i++ ) {
			if ( $i % $label_step !== 0 && $i !== $n - 1 ) {
				continue;
			}
			$x = $pad + ( $i * $step );
			$f = date_i18n( 'd/m', strtotime( $fechas[ $i ] ) );
			$labels .= '<text x="' . round( $x ) . '" y="' . ( $H - 4 ) . '" fill="#727272" font-size="10" text-anchor="middle">' . esc_html( $f ) . '</text>';
		}

		return '<svg viewBox="0 0 ' . $W . ' ' . $H . '" xmlns="http://www.w3.org/2000/svg" style="width:100%;height:auto;max-height:200px;">'
			. $grid
			. '<polyline points="' . $pt . '" fill="none" stroke="#c14242" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round"/>'
			. $dots
			. $labels
			. '</svg>';
	}

	/**
	 * Devuelve una insignia de variación porcentual (▲ verde / ▼ rojo).
	 *
	 * @param float|null $dif
	 * @return string
	 */
	private function badge_cambio( $dif ) {
		if ( $dif === null ) {
			return '<span class="qv-dash-badge" style="color:#727272;">sin datos</span>';
		}

		$sube   = $dif >= 0;
		$color  = $sube ? '#1e9400' : '#d63638';
		$flecha = $sube ? '▲' : '▼';
		$signo  = $sube ? '+' : '';

		return '<span class="qv-dash-badge" style="color:' . $color . ';">' . $flecha . ' ' . $signo . number_format( $dif, 1, ',', '.' ) . '%</span>';
	}
}
