<?php
if (!defined('ABSPATH')) exit;
get_header();

global $post;

$empresa_id = get_post_meta($post->ID, '_qv_empresa', true);
$fecha = get_post_meta($post->ID, '_qv_fecha', true);
$hora = get_post_meta($post->ID, '_qv_hora', true);
$origen = get_post_meta($post->ID, '_qv_origen', true);
$destino = get_post_meta($post->ID, '_qv_destino', true);

/* Para el mapa */
$origen_lat = get_post_meta($post->ID, '_qv_origen_lat', true);
$origen_lng = get_post_meta($post->ID, '_qv_origen_lng', true);
$destino_lat = get_post_meta($post->ID, '_qv_destino_lat', true);
$destino_lng = get_post_meta($post->ID, '_qv_destino_lng', true);

$distancia = get_post_meta($post->ID, '_qv_distancia', true);

$estado = get_post_meta($post->ID, '_qv_estado', true);
$importe_km = get_post_meta($post->ID, '_qv_importe_km', true);
$observaciones = get_post_meta($post->ID, '_qv_observaciones', true);

$conductor_id = get_post_meta($post->ID, '_qv_conductor', true);
$conductor = $conductor_id ? get_user_by('id', $conductor_id) : null;

?>

<pre>
	<?php
	$metas = get_post_meta( $post->ID );
	foreach ( $metas as $key => $value ) {
		echo esc_html($key) . ': ' . esc_html(is_array($value) ? implode(', ', $value) : $value) . "\n";
	}
	?>
</pre>
<header class="qv-header">
	<h2>
		🏷 #123456 - 📅<?php echo esc_html($fecha); ?> - 🕐 <?php echo esc_html($hora); ?> hs
	</h2>
	<h3><strong>Estado:</strong> <?php echo esc_html( ucfirst( $estado ) ); ?></h3>
</header>

<div class="viaje-details qv-grid qv-grid-2-3">
	<aside class="col">
		<div class="qv-card">
			<article id="qv-chip-info">
				<div class="qv-chip">
					<!-- <p class="qv-chip-icon qv-chip-distancia">
						Distancia<br>
						<span class="qv-resaltado"><?php echo $distancia ? esc_html($distancia . ' km') : 'No disponible'; ?></span>
					</p> -->
					<!-- <p class="qv-chip-icon qv-chip-importe">
						Importe total<br>
						<span class="qv-resaltado"><?php echo $importe_total ? '$ ' . esc_html(number_format($importe_total, 2)) : '$ -'; ?></span>
					</p> -->
					<!-- <p class="qv-chip-icon qv-chip-importe">Importe x km<br>
						<span class="qv-resaltado">$ <?php echo esc_html($importe_km); ?></span>
					</p> -->
				</div>

				<div class="qv-chip">
					<p class="qv-chip-icon qv-chip-distancia">
						Origen lat<br>
						<span class="qv-resaltado"><?php echo $origen_lat ? esc_html($origen_lat . ' km') : 'No disponible'; ?></span>
					</p>
				</div>
				<div class="qv-chip">
					<p class="qv-chip-icon qv-chip-origen">Origen <br>
						<span class="qv-resaltado"><?php echo esc_html($origen); ?></span>
					</p>

					<p class="qv-chip-icon qv-chip-destino">Destino <br>
						<span class="qv-resaltado"><?php echo esc_html($destino); ?></span>
					</p>
				</div>
			</article>
			<hr>
			<article id="qvChipConductor" class="qv-chip-viaje qv-chip-viaje-perfil">
				<?php if ($conductor): ?>
					<div class="qv-grid">
						<figure class="qv-avatar">
							<img src="https://secure.gravatar.com/avatar/696b67f778b73fa27f200715f32c055b934c433781081dc64f3103783dc6d403?s=100&d=mm&r=g" width="100" height="100">
						</figure>
						<div>
							<p class="qv-perfil-name">
								<?php echo esc_html($conductor->display_name); ?>
							</p>
							<p>
								<?php echo esc_html(get_user_meta($conductor->ID, 'marca', true)); ?> <?php echo esc_html(get_user_meta($conductor->ID, 'modelo', true)); ?> - AAA-123456786
							</p>
						</div>
					</div>
					<a href="#" class="qv-perfil-action">
						Llamar Conductor <?php echo esc_html(get_user_meta($conductor->ID, 'celular', true)); ?>
					</a>
				<?php else: ?>
					<p><em>No hay conductor asignado aún.</em></p>
				<?php endif; ?>
			</article>
			<article id="qvChipPasajero" class="qv-chip-viaje qv-chip-viaje-perfil">
				<?php
				$pasajeros = get_users([
					'role'       => 'pasajero',
					'meta_key'   => 'empresa_id',
					'meta_value' => $empresa_id,
					'number'     => 1,
				]);

				$pasajero = !empty($pasajeros) ? $pasajeros[0] : null;

				if ($pasajero):
					$telefono = get_user_meta($pasajero->ID, 'telefono', true);
					$empresa_id_usuario = (int) get_user_meta($pasajero->ID, 'empresa_id', true);
					$empresa_nombre = 'Sin empresa asignada';

					if ($empresa_id_usuario && get_post_type($empresa_id_usuario) === 'empresa') {
						$empresa_nombre = get_the_title($empresa_id_usuario);
					}

					$avatar_url = get_avatar_url($pasajero->ID, ['size' => 100]);
					?>
					<div class="qv-grid">
						<figure class="qv-avatar">
							<img src="<?php echo esc_url($avatar_url); ?>" alt="<?php echo esc_attr($pasajero->display_name); ?>" width="100" height="100">
						</figure>
						<div>
							<p class="qv-perfil-name">
								<?php echo esc_html($pasajero->display_name); ?>
							</p>
							<ul class="qv-list">
								<li><strong>Teléfono:</strong> <?php echo esc_html($telefono ?: 'No disponible'); ?></li>
								<li><strong>Empresa:</strong> <?php echo esc_html($empresa_nombre); ?></li>
							</ul>
						</div>
					</div>

					<?php if ($telefono): ?>
						<a href="tel:<?php echo esc_attr($telefono); ?>" class="qv-perfil-action">
							Llamar Pasajero <?php echo esc_html($pasajero->display_name); ?>
						</a>
					<?php endif; ?>

				<?php else: ?>
					<p><em>No hay pasajeros registrados para esta empresa.</em></p>
				<?php endif; ?>
			</article>

			<?php if ( $observaciones ) : ?>
				<article id="qvChipObservaciones" class="qv-chip-viaje">
					<p class="">Observaciones <br>
						<span class="qv-resaltado"><?php echo esc_html($observaciones); ?></span>
					</p>
				</article>
			<?php endif; ?>
		</div>
	</aside>
	<main id="qvMainMap" class="col">
		<div class="qv-card">
			<div id="qvChipMap" style="width: 100%; min-height: 400px;"
			data-origen-lat="<?php echo esc_attr($origen_lat); ?>"
			data-origen-lng="<?php echo esc_attr($origen_lng); ?>"
			data-destino-lat="<?php echo esc_attr($destino_lat); ?>"
			data-destino-lng="<?php echo esc_attr($destino_lng); ?>">
		</div>
	</div>
</main>
</div>

<?php get_footer(); ?>