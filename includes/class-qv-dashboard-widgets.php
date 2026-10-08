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
