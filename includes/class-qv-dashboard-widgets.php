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
		$dias    = isset( $_GET['qv_dias'] ) ? max( 3, min( 90, intval( $_GET['qv_dias'] ) ) ) : 7;
		$metrica = ( isset( $_GET['qv_metrica'] ) && $_GET['qv_metrica'] === 'ganancias' ) ? 'ganancias' : 'viajes';
		$nonce   = wp_create_nonce( 'qv_chart_viajes' );
		$ajaxurl = esc_url( admin_url( 'admin-ajax.php' ) );

		$opciones_dias = [ 7 => 'Últimos 7 días', 14 => 'Últimos 14 días', 30 => 'Últimos 30 días' ];
		$opciones_met  = [ 'viajes' => 'Cantidad de viajes', 'ganancias' => 'Ganancias' ];
		$color   = ( $metrica === 'ganancias' ) ? '#2e7d32' : '#1a73e8';
		$nombre  = ( $metrica === 'ganancias' ) ? 'Ganancias' : 'Viajes';
		$nota    = ( $metrica === 'ganancias' ) ? 'Ganancias de viajes finalizados por día.' : 'Cantidad de viajes por día.';
		?>
		<style>
			.qv-timeline .qv-select-row { display: flex; gap: 8px; flex-wrap: wrap; }
			.qv-timeline .qv-select { margin-bottom: 8px; width: auto; flex: 1 1 auto; max-width: 220px; }
			.qv-timeline-sub { color: #727272; font-size: 12px; margin-top: 4px; }
			.qv-leyenda-inline { display: flex; gap: 14px; font-size: 11px; color: #3c434a; margin-bottom: 6px; }
			.qv-leyenda-inline .qv-ley { display: inline-flex; align-items: center; gap: 5px; }
			.qv-leyenda-inline .qv-dot { width: 10px; height: 10px; border-radius: 2px; display: inline-block; }
		</style>
		<div class="qv-timeline">
			<div class="qv-leyenda-inline"><span class="qv-ley"><span class="qv-dot" style="background:<?php echo esc_attr( $color ); ?>"></span><?php echo esc_html( $nombre ); ?></span></div>
			<div class="qv-select-row">
				<select id="qv-dias-select" class="qv-select" title="Período">
					<?php foreach ( $opciones_dias as $valor => $etiqueta ) : ?>
						<option value="<?php echo (int) $valor; ?>" <?php selected( $dias, $valor ); ?>><?php echo esc_html( $etiqueta ); ?></option>
					<?php endforeach; ?>
				</select>
				<select id="qv-metrica-select" class="qv-select" title="Métrica">
					<?php foreach ( $opciones_met as $valor => $etiqueta ) : ?>
						<option value="<?php echo esc_attr( $valor ); ?>" <?php selected( $metrica, $valor ); ?>><?php echo esc_html( $etiqueta ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div id="qv-timeline-chart"><?php echo $this->svg_viajes( $dias, $metrica ); // phpcs:ignore ?></div>
			<p class="qv-timeline-sub"><?php echo esc_html( $nota ); ?> Según fecha programada. Pasá el mouse para ver el valor.</p>
		</div>
		<script>
			window.qvDash = window.qvDash || { ajaxurl: <?php echo wp_json_encode( $ajaxurl ); ?>, nonce: <?php echo wp_json_encode( $nonce ); ?> };
			jQuery(function($){
				var $chart = $('#qv-timeline-chart');

				// Redibujar al cambiar período o métrica
				$('#qv-dias-select, #qv-metrica-select').on('change', function(){
					$.post(qvDash.ajaxurl, {
						action: 'qv_chart_viajes',
						dias: $('#qv-dias-select').val(),
						metrica: $('#qv-metrica-select').val(),
						nonce: qvDash.nonce
					}, function(r){
						if (r && r.success) { $chart.html(r.data.svg); }
					});
				});

				// Tooltip por delegación (soporta el redibujado AJAX)
				$chart.on('mouseenter', '.qv-day', function(){
					var el = this, svg = el.ownerSVGElement;
					var x = parseFloat(el.getAttribute('data-x'));
					$('#qv-tip-fecha').text(el.getAttribute('data-fecha'));
					$('#qv-tip-valor').text(el.getAttribute('data-nombre') + ': ' + el.getAttribute('data-valor'));
					var vb = svg.viewBox.baseVal, tipW = 120;
					var tx = x - tipW / 2;
					if (tx < 10) { tx = 10; }
					if (tx + tipW > vb.width - 6) { tx = vb.width - tipW - 6; }
					$('#qv-tip').attr('transform', 'translate(' + tx + ',6)').show();
				});
				$chart.on('mouseleave', '.qv-day', function(){ $('#qv-tip').hide(); });
				$chart.on('mouseleave', function(){ $('#qv-tip').hide(); });
			});
		</script>
		<?php
	}

	/**
	 * Respuesta AJAX para redibujar el gráfico según los días elegidos.
	 */
	public function ajax_chart_viajes() {
		check_ajax_referer( 'qv_chart_viajes', 'nonce' );
		$dias    = isset( $_POST['dias'] ) ? max( 3, min( 90, intval( $_POST['dias'] ) ) ) : 7;
		$metrica = ( isset( $_POST['metrica'] ) && $_POST['metrica'] === 'ganancias' ) ? 'ganancias' : 'viajes';
		wp_send_json_success( [ 'svg' => $this->svg_viajes( $dias, $metrica ) ] );
	}

	/**
	 * Genera un gráfico SVG de línea (viajes en azul o ganancias en verde) por día.
	 *
	 * @param int    $dias
	 * @param string $metrica 'viajes'|'ganancias'
	 * @return string SVG.
	 */
	public function svg_viajes( $dias, $metrica = 'viajes' ) {
		$dias    = max( 3, min( 90, intval( $dias ) ) );
		$metrica = ( $metrica === 'ganancias' ) ? 'ganancias' : 'viajes';

		$hoy    = current_time( 'Y-m-d' );
		$inicio = date( 'Y-m-d', strtotime( '-' . ( $dias - 1 ) . ' days', current_time( 'timestamp' ) ) );

		// Inicializar conteo y ganancias por día
		$por_dia = [];
		for ( $i = 0; $i < $dias; $i++ ) {
			$fecha = date( 'Y-m-d', strtotime( '-' . $i . ' days', current_time( 'timestamp' ) ) );
			$por_dia[ $fecha ] = [ 'c' => 0, 'g' => 0.0 ];
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
			if ( isset( $por_dia[ $f ] ) ) {
				$por_dia[ $f ]['c']++;

				// Ganancias: solo viajes finalizados (mismo criterio que el resumen)
				if ( get_post_meta( $id, '_qv_estado', true ) === 'finalizado' ) {
					$importe = get_post_meta( $id, '_qv_total_general', true );
					if ( $importe === '' ) {
						$importe = get_post_meta( $id, '_qv_importe_total', true );
					}
					$por_dia[ $f ]['g'] += floatval( $importe );
				}
			}
		}

		ksort( $por_dia );
		$fechas  = array_keys( $por_dia );
		$conteos = array_values( array_column( $por_dia, 'c' ) );
		$ganan   = array_values( array_column( $por_dia, 'g' ) );
		$n       = count( $fechas );

		// Serie activa
		$valores = ( $metrica === 'ganancias' ) ? $ganan : $conteos;
		$max     = max( 1, max( $valores ) );
		$color   = ( $metrica === 'ganancias' ) ? '#2e7d32' : '#1a73e8';
		$nombre  = ( $metrica === 'ganancias' ) ? 'Ganancias' : 'Viajes';

		// Layout con un eje vertical simple
		$W = 560;
		$H = 200;
		$padL   = 34;
		$padR   = 16;
		$padT   = 18;
		$padB   = 26;
		$innerW = $W - $padL - $padR;
		$innerH = $H - $padT - $padB;
		$step   = $n > 1 ? ( $innerW / ( $n - 1 ) ) : 0;

		$xf = function ( $i ) use ( $padL, $step ) { return $padL + ( $i * $step ); };
		$yf = function ( $v ) use ( $padT, $innerH, $max ) { return $padT + $innerH - ( ( $v / $max ) * $innerH ); };

		// Líneas de referencia y etiquetas del eje
		$grid = '';
		$ylab = '';
		for ( $g = 0; $g <= 4; $g++ ) {
			$fr = $g / 4;
			$gy = $padT + ( $innerH * $fr );
			$grid .= '<line x1="' . $padL . '" y1="' . round( $gy ) . '" x2="' . ( $W - $padR ) . '" y2="' . round( $gy ) . '" stroke="#eef0f2" stroke-width="1"/>';
			$ejeval = ( $metrica === 'ganancias' ) ? number_format( round( $max * $fr ), 0, ',', '.' ) : round( $max * $fr );
			$ylab .= '<text x="' . ( $padL - 5 ) . '" y="' . round( $gy + 3 ) . '" fill="#8a8f98" font-size="9" text-anchor="end" pointer-events="none">' . $ejeval . '</text>';
		}

		// Línea y puntos
		$pt = '';
		$dots = '';
		for ( $i = 0; $i < $n; $i++ ) {
			$x = round( $xf( $i ) );
			$y = round( $yf( $valores[ $i ] ) );
			$pt .= ( $i ? ' ' : '' ) . $x . ',' . $y;
			$dots .= '<circle cx="' . $x . '" cy="' . $y . '" r="3.5" fill="' . $color . '" pointer-events="none"/>';
		}

		// Etiquetas del eje X (máx ~8)
		$label_step = max( 1, (int) ceil( $n / 8 ) );
		$labels = '';
		for ( $i = 0; $i < $n; $i++ ) {
			if ( $i % $label_step !== 0 && $i !== $n - 1 ) {
				continue;
			}
			$labels .= '<text x="' . round( $xf( $i ) ) . '" y="' . ( $H - 6 ) . '" fill="#727272" font-size="10" text-anchor="middle" pointer-events="none">' . esc_html( date_i18n( 'd/m', strtotime( $fechas[ $i ] ) ) ) . '</text>';
		}

		// Regiones de hover por día (para el tooltip)
		$hover = '';
		for ( $i = 0; $i < $n; $i++ ) {
			$x = $xf( $i );
			$w = max( 6, $step * 0.95 );
			$rxl = min( max( $x - ( $w / 2 ), $padL ), ( $W - $padR ) - $w );
			$val = ( $metrica === 'ganancias' ) ? number_format( round( $valores[ $i ] ), 0, ',', '.' ) : round( $valores[ $i ] );
			$hover .= '<rect class="qv-day" x="' . round( $rxl ) . '" y="' . $padT . '" width="' . round( $w ) . '" height="' . round( $innerH ) . '" fill="transparent"'
				. ' data-x="' . round( $x ) . '" data-fecha="' . esc_attr( date_i18n( 'd/m/Y', strtotime( $fechas[ $i ] ) ) ) . '"'
				. ' data-nombre="' . esc_attr( $nombre ) . '" data-valor="' . $val . '"/>';
		}

		// Tooltip (oculto hasta el hover)
		$tip = '<g id="qv-tip" style="display:none">'
			. '<rect x="0" y="0" width="120" height="34" rx="5" fill="#1d2327" opacity="0.94"/>'
			. '<text id="qv-tip-fecha" x="7" y="14" fill="#fff" font-size="10" font-weight="bold"></text>'
			. '<text id="qv-tip-valor" x="7" y="28" fill="#cfd8dc" font-size="10"></text>'
			. '</g>';

		return '<svg viewBox="0 0 ' . $W . ' ' . $H . '" xmlns="http://www.w3.org/2000/svg" style="width:100%;height:auto;max-height:200px;font-family:-apple-system,Segoe UI,Roboto,sans-serif;">'
			. $grid . $ylab
			. '<polyline points="' . $pt . '" fill="none" stroke="' . $color . '" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round" pointer-events="none"/>'
			. $dots
			. $labels
			. $hover
			. $tip
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
