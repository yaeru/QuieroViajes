<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class QV_Admin {

	public function init() {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
		add_action( 'add_meta_boxes', [ $this, 'register_metaboxes' ] );
		add_action( 'save_post', [ $this, 'save_detalles_metabox' ] );
		add_action( 'save_post', [ $this, 'save_origen_destino_metabox' ] );
		add_action( 'save_post', [ $this, 'save_gastos_extra_metabox' ] );
		add_action( 'save_post', [ $this, 'save_resumen_metabox' ] );

		/* Avisos de error Google Maps */
		add_action( 'admin_head', [ $this, 'gmaps_auth_failure_handler' ] );
		add_action( 'admin_notices', [ $this, 'gmaps_admin_notice' ] );
	}

	public function enqueue_admin_scripts( $hook ) {
		global $post;
		if ( $hook == 'post-new.php' || $hook == 'post.php' ) {
			if ( $post->post_type === 'viaje' ) {

				$api_key = defined('QV_GOOGLE_MAPS_API_KEY') ? QV_GOOGLE_MAPS_API_KEY : '';

				wp_enqueue_script(
					'google-maps',
					'https://maps.googleapis.com/maps/api/js?key=' . esc_attr( get_option('qv_google_maps_api_key') ) . '&libraries=places',
					[],
					null,
					true
				);

				wp_enqueue_script(
					'qv-admin',
					QV_URL . 'assets/js/qv-admin.js',
					[],
					'1.0',
					true
				);
			}
		}
	}

	/* Inyecta handler para capturar errores de Google Maps */
	public function gmaps_auth_failure_handler() {
		?>
		<script>
			window.gm_authFailure = function() {
				console.error("Error con la API Key de Google Maps.");
				var event = new Event("gm_auth_failure");
				window.dispatchEvent(event);
			};
		</script>
		<?php
	}

	/* Muestra aviso en admin si hay error */
	public function gmaps_admin_notice() {
		?>
		<div class="notice notice-error is-dismissible qv-gmaps-error" style="display:none;">
			<p><strong>⚠️ Error con la API de Google Maps:</strong> Verificá tu API Key en Google Cloud Console.</p>
		</div>
		<script>
			window.addEventListener("gm_auth_failure", function() {
				document.querySelector(".qv-gmaps-error").style.display = "block";
			});
		</script>
		<?php
	}

	public function register_metaboxes() {
		add_meta_box(
			'qv_origen_destino',
			'Origen y Destino',
			[ $this, 'render_origen_destino_metabox' ],
			'viaje',
			'normal',
			'high'
		);

		add_meta_box(
			'qv_detalles_viaje',
			'Detalles del viaje',
			[ $this, 'render_detalles_metabox' ],
			'viaje',
			'normal',
			'high'
		);

		add_meta_box(
			'qv_pasajero_empresa',
			'Pasajero y Empresa',
			[ $this, 'render_pasajero_empresa_metabox' ],
			'viaje',
			'normal',
			'high'
		);

		if ( current_user_can('manage_options') ) {
			add_meta_box(
				'viaje_gastos_extra',
				'Gastos Extra',
				[ $this, 'render_gastos_extra_metabox' ],
				'viaje',
				'normal',
				'default'
			);
		}

		add_meta_box(
			'qv_resumen_viaje',
			'Resumen del viaje',
			[ $this, 'render_resumen_metabox' ],
			'viaje',
			'side',
			'default'
		);
	}

	/* Metabox Origen destino */
	public function render_origen_destino_metabox( $post ) {
		wp_nonce_field( 'qv_save_origen_destino', 'qv_origen_destino_nonce' );

		$origen      = get_post_meta( $post->ID, '_qv_origen', true );
		$origen_lat  = get_post_meta( $post->ID, '_qv_origen_lat', true );
		$origen_lng  = get_post_meta( $post->ID, '_qv_origen_lng', true );

		$destino     = get_post_meta( $post->ID, '_qv_destino', true );
		$destino_lat = get_post_meta( $post->ID, '_qv_destino_lat', true );
		$destino_lng = get_post_meta( $post->ID, '_qv_destino_lng', true );
		$importe_km    = get_post_meta( $post->ID, '_qv_importe_km', true );
		?>
		<table class="form-table qv-metabox">
			<tbody>
				<tr>
					<th>
						<label>Origen:</label>
					</th>
					<td>
						<input type="text" id="qv_origen" name="qv_origen" value="<?php echo esc_attr( $origen ); ?>" style="width:100%;">
						<input type="hidden" id="qv_origen_lat" name="qv_origen_lat" value="<?php echo esc_attr( $origen_lat ); ?>">
						<input type="hidden" id="qv_origen_lng" name="qv_origen_lng" value="<?php echo esc_attr( $origen_lng ); ?>">
					</td>
				</tr>
				<tr>
					<th>
						<label>Destino:</label>
					</th>
					<td>
						<input type="text" id="qv_destino" name="qv_destino" value="<?php echo esc_attr( $destino ); ?>" style="width:100%;">
						<input type="hidden" id="qv_destino_lat" name="qv_destino_lat" value="<?php echo esc_attr( $destino_lat ); ?>">
						<input type="hidden" id="qv_destino_lng" name="qv_destino_lng" value="<?php echo esc_attr( $destino_lng ); ?>">
					</td>
				</tr>
				<tr id="qvImportePorKm">
					<?php if ( current_user_can('administrator') ) : ?>
						<th>
							<label>Importe por km:</label>
						</th>
						<td>
							<input type="number" id="qv_importe_km" step="0.01" name="qv_importe_km" value="<?php echo esc_attr( $importe_km ); ?>">
						</td>
					<?php endif; ?>
				</tr>
			</tbody>
		</table>

		<?php
	}

	/* Guardar metabox Origen destino */
	public function save_origen_destino_metabox( $post_id ) {
		if ( ! isset( $_POST['qv_origen_destino_nonce'] ) || ! wp_verify_nonce( $_POST['qv_origen_destino_nonce'], 'qv_save_origen_destino' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
		if ( ! current_user_can( 'edit_post', $post_id ) ) return;

		$fields = [
			'qv_origen'      => '_qv_origen',
			'qv_origen_lat'  => '_qv_origen_lat',
			'qv_origen_lng'  => '_qv_origen_lng',
			'qv_destino'     => '_qv_destino',
			'qv_destino_lat' => '_qv_destino_lat',
			'qv_destino_lng' => '_qv_destino_lng',
			'qv_importe_km'    => '_qv_importe_km',
		];

		foreach ( $fields as $form_field => $meta_key ) {
			if ( isset( $_POST[$form_field] ) ) {
				update_post_meta( $post_id, $meta_key, sanitize_text_field( $_POST[$form_field] ) );
			}
		}
	}

	/* Metabox Detalles del Viaje */
	public function render_detalles_metabox( $post ) {
		wp_nonce_field( 'qv_save_detalles', 'qv_detalles_nonce' );

		$estado        = get_post_meta( $post->ID, '_qv_estado', true );
		$fecha         = get_post_meta( $post->ID, '_qv_fecha', true );
		$hora          = get_post_meta( $post->ID, '_qv_hora', true );
		$empresa_id    = get_post_meta( $post->ID, '_qv_empresa', true );
		
		$pago          = get_post_meta( $post->ID, '_qv_pago', true );
		$observaciones = get_post_meta( $post->ID, '_qv_observaciones', true );
		$conductor_id  = get_post_meta( $post->ID, '_qv_conductor', true );

		/* Conductores */
		$conductores = get_users( [ 'role' => 'conductor' ] );

		?>
		<table class="form-table qv-metabox">
			<tbody>
				<tr>
					<th>
						<label>Estado:</label>
					</th>
					<td>
						<select name="qv_estado">
							<?php foreach ( QV_Viajes_Utils::get_estados_viaje() as $key => $label ) : ?>
								<option value="<?php echo esc_attr($key); ?>" <?php selected( $key, $estado ); ?>>
									<?php echo esc_html( $label ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th>
						<label>Fecha programada:</label>
					</th>
					<td>
						<input type="date" name="qv_fecha" value="<?php echo esc_attr( $fecha ); ?>">
					</td>
				</tr>
				<tr>
					<th>
						<label>Hora programada:</label>
					</th>
					<td>
						<input type="time" name="qv_hora" value="<?php echo esc_attr( $hora ); ?>">
					</td>
				</tr>
				
				<tr>
					<th>
						<label>Forma de pago:</label>
					</th>
					<td>
						<select name="qv_pago">
							<?php
							$formas_pago = [ 'efectivo' => 'Efectivo', 'transferencia' => 'Transferencia', 'cuentaCorriente' => 'Cuenta Corriente', 'tarjeta' => 'Tarjeta' ];
							foreach ( $formas_pago as $key => $label ) {
								echo '<option value="'.esc_attr($key).'" '.selected($pago, $key, false).'>'.esc_html($label).'</option>';
							}
							?>
						</select>
					</td>
				</tr>
				<tr>
					<th>
						<label>Observaciones:</label>
					</th>
					<td>
						<textarea name="qv_observaciones" rows="4" style="width:100%;"><?php echo esc_textarea( $observaciones ); ?></textarea>
					</td>
				</tr>
				<tr id="qvConductor">
					<?php if ( current_user_can('administrator') ) : ?>
						<th>
							<label>Conductor:</label>
						</th>
						<td>
							<select name="qv_conductor">
								<option value="">-- Seleccionar --</option>
								<?php foreach ( $conductores as $conductor ) : ?>
									<option value="<?php echo esc_attr( $conductor->ID ); ?>" <?php selected( $conductor_id, $conductor->ID ); ?>>
										<?php echo esc_html( $conductor->display_name ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					<?php endif; ?>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/* Guardar metabox Detalles */
	public function save_detalles_metabox( $post_id ) {
		if ( ! isset( $_POST['qv_detalles_nonce'] ) || ! wp_verify_nonce( $_POST['qv_detalles_nonce'], 'qv_save_detalles' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
		if ( ! current_user_can( 'edit_post', $post_id ) ) return;

		$fields = [
			'qv_estado'        => '_qv_estado',
			'qv_fecha'         => '_qv_fecha',
			'qv_hora'          => '_qv_hora',
			'qv_empresa'       => '_qv_empresa',
			'qv_pago'          => '_qv_pago',
			'qv_observaciones' => '_qv_observaciones',
			'qv_conductor'     => '_qv_conductor',
		];

		foreach ( $fields as $form_field => $meta_key ) {
			if ( isset( $_POST[$form_field] ) ) {
				update_post_meta( $post_id, $meta_key, sanitize_text_field( $_POST[$form_field] ) );
			}
		}
	}

	/* Metabox Pasajero */
	public function render_pasajero_empresa_metabox( $post ) {
		wp_nonce_field( 'qv_save_pasajero_empresa', 'qv_pasajero_empresa_nonce' );

		$empresa_id  = get_post_meta( $post->ID, '_qv_empresa', true );
		$pasajero_id = get_post_meta( $post->ID, '_qv_pasajero', true );
		$current_user = wp_get_current_user();

		/* Si el usuario actual es empresa, forzamos solo su empresa */
		$empresas = [];
		if ( in_array( 'empresa', (array) $current_user->roles ) ) {
			$empresas[] = $current_user;
		} else {
			$empresas = get_users( [ 'role' => 'empresa', 'orderby' => 'display_name', 'order' => 'ASC' ] );
		}

		/* Filtrar pasajeros */
		$pasajeros_args = [ 'role' => 'pasajero', 'orderby' => 'display_name', 'order' => 'ASC' ];
		$pasajeros = get_users( $pasajeros_args );

		/* Si el usuario empresa está logueado, filtrar pasajeros de su empresa */
		if ( in_array( 'empresa', (array) $current_user->roles ) ) {
			$empresa_id = $current_user->ID;
			$pasajeros = array_filter( $pasajeros, function( $u ) use ( $empresa_id ) {
				return get_user_meta( $u->ID, 'empresa_id', true ) == $empresa_id;
			});
		} else if ( $empresa_id ) {
			$pasajeros = array_filter( $pasajeros, function( $u ) use ( $empresa_id ) {
				return get_user_meta( $u->ID, 'empresa_id', true ) == $empresa_id;
			});
		}

		?>
		<table class="form-table qv-metabox">
			<tbody>
				<tr>
					<th><label>Empresa:</label></th>
					<td>
						<select name="qv_empresa" id="qv_empresa" <?php echo in_array('empresa', (array)$current_user->roles) ? 'disabled' : ''; ?>>
							<option value="">-- Seleccionar --</option>
							<?php foreach ( $empresas as $empresa ) : ?>
								<option value="<?php echo esc_attr( $empresa->ID ); ?>" <?php selected( $empresa_id, $empresa->ID ); ?>>
									<?php echo esc_html( $empresa->display_name ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<?php if ( in_array('empresa', (array)$current_user->roles) ) : ?>
							<input type="hidden" name="qv_empresa" value="<?php echo esc_attr( $empresa_id ); ?>">
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th><label>Pasajero:</label></th>
					<td>
						<select name="qv_pasajero">
							<option value="">-- Seleccionar pasajero --</option>
							<?php foreach ( $pasajeros as $p ) : ?>
								<option value="<?php echo esc_attr( $p->ID ); ?>" <?php selected( $pasajero_id, $p->ID ); ?>>
									<?php echo esc_html( $p->display_name ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/* Guardar metabox Pasajero */
	public function save_pasajero_empresa_metabox( $post_id ) {
		if ( ! isset( $_POST['qv_pasajero_empresa_nonce'] ) || ! wp_verify_nonce( $_POST['qv_pasajero_empresa_nonce'], 'qv_save_pasajero_empresa' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
		if ( ! current_user_can( 'edit_post', $post_id ) ) return;

		if ( isset($_POST['qv_empresa']) ) {
			update_post_meta( $post_id, '_qv_empresa', sanitize_text_field( $_POST['qv_empresa'] ) );
		}

		if ( isset($_POST['qv_pasajero']) ) {
			update_post_meta( $post_id, '_qv_pasajero', sanitize_text_field( $_POST['qv_pasajero'] ) );
		}
	}	

	/* Metabox Gastos extra */
	public function render_gastos_extra_metabox( $post ) {
		wp_nonce_field( 'guardar_gastos_extra', 'gastos_extra_nonce' );

		$gastos_extra = get_post_meta( $post->ID, '_gastos_extra', true );
		if ( ! is_array( $gastos_extra ) ) {
			$gastos_extra = [ [ 'descripcion' => '', 'importe' => '' ] ];
		}
		?>

		<table id="qvGastosExtraTable" class="widefat">
			<thead>
				<tr>
					<th>Descripción</th>
					<th>Importe ($)</th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $gastos_extra as $index => $gasto ) : ?>
					<tr>
						<td><input type="text" name="gastos_extra[<?php echo $index; ?>][descripcion]" value="<?php echo esc_attr( $gasto['descripcion'] ); ?>" /></td>
						<td><input type="number" step="0.01" name="gastos_extra[<?php echo $index; ?>][importe]" value="<?php echo esc_attr( $gasto['importe'] ); ?>" /></td>
						<td><button type="button" class="remove-row">🗑️</button></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<p><button type="button" class="button" id="add-gasto-extra">+ Añadir otro gasto</button></p>

		<script>
			document.addEventListener('DOMContentLoaded', () => {
				const tableBody = document.querySelector('#qvGastosExtraTable tbody');
				const addButton = document.querySelector('#add-gasto-extra');

				addButton.addEventListener('click', () => {
					const index = tableBody.querySelectorAll('tr').length;
					const newRow = document.createElement('tr');
					newRow.innerHTML = `
				<td><input type="text" name="gastos_extra[${index}][descripcion]" value="" /></td>
				<td><input type="number" step="0.01" name="gastos_extra[${index}][importe]" value="" /></td>
				<td><button type="button" class="remove-row">🗑️</button></td>
					`;
					tableBody.appendChild(newRow);
				});

				tableBody.addEventListener('click', e => {
					if (e.target.classList.contains('remove-row')) {
						e.target.closest('tr').remove();
					}
				});
			});
		</script>
		<?php
	}

	/* Guardar metabox Gastos extra */
	public function save_gastos_extra_metabox( $post_id ) {
		if ( ! isset( $_POST['gastos_extra_nonce'] ) || ! wp_verify_nonce( $_POST['gastos_extra_nonce'], 'guardar_gastos_extra' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
		if ( ! current_user_can( 'edit_post', $post_id ) ) return;

		if ( isset( $_POST['gastos_extra'] ) && is_array( $_POST['gastos_extra'] ) ) {
			$sanitized = array_map( function( $item ) {
				return [
					'descripcion' => sanitize_text_field( $item['descripcion'] ?? '' ),
					'importe'     => floatval( $item['importe'] ?? 0 ),
				];
			}, $_POST['gastos_extra'] );

			update_post_meta( $post_id, '_gastos_extra', $sanitized );
		} else {
			delete_post_meta( $post_id, '_gastos_extra' );
		}
	}

	public function render_resumen_metabox( $post ) {
		/* Obtener valores guardados */
		$origen       = get_post_meta( $post->ID, '_qv_origen', true );
		$origen_lat   = get_post_meta( $post->ID, '_qv_origen_lat', true );
		$origen_lng   = get_post_meta( $post->ID, '_qv_origen_lng', true );
		$destino      = get_post_meta( $post->ID, '_qv_destino', true );
		$destino_lat  = get_post_meta( $post->ID, '_qv_destino_lat', true );
		$destino_lng  = get_post_meta( $post->ID, '_qv_destino_lng', true );
		$importe_km   = get_post_meta( $post->ID, '_qv_importe_km', true );
		$distancia    = get_post_meta( $post->ID, '_qv_distancia', true );
		$distancia    = floatval( $distancia );

		/* Obtener adicional configurado */
		$adicional_viaje_corto = get_option( 'qv_adicional_viaje_corto', 0 );
		$adicional_viaje_corto = floatval( $adicional_viaje_corto );

		/* Gastos extra */
		$gastos_extra = get_post_meta( $post->ID, '_gastos_extra', true );
		if ( ! is_array( $gastos_extra ) ) $gastos_extra = [];

		$importe_estimado = get_post_meta( $post->ID, '_qv_importe', true );
		if ( $importe_estimado === '' || $importe_estimado === null ) {
			$importe_estimado = get_post_meta( $post->ID, '_qv_importe_total', true );
		}
		if ( $importe_estimado === '' || $importe_estimado === null ) {
			$importe_estimado = get_post_meta( $post->ID, '_qv_importe_estimado', true );
		}
		/* asegurarnos número */
		$importe_estimado = $importe_estimado !== '' && $importe_estimado !== null ? floatval( $importe_estimado ) : 0.0;

		/* Sumar gastos extra */
		$total_gastos = 0.0;
		foreach ( $gastos_extra as $gasto ) {
			if ( isset( $gasto['importe'] ) && $gasto['importe'] !== '' ) {
				$total_gastos += floatval( $gasto['importe'] );
			}
		}
		$adicional_aplicado = get_post_meta( $post->ID, '_qv_adicional_aplicado', true );
		$total_general = round( floatval( $importe_estimado ) + $total_gastos + $adicional_aplicado, 2 );

		?>
		<div id="qvResumen" data-adicional-viaje-corto="<?php echo esc_attr($adicional_viaje_corto); ?>">
			<p><strong>Origen:</strong> <?php echo esc_html($origen); ?></p>
			<p><strong>Destino:</strong> <?php echo esc_html($destino); ?></p>

			<!-- Hidden inputs para lat/lng -->
			<div style="display:none" data-origen-lat="<?php echo esc_attr($origen_lat); ?>" data-origen-lng="<?php echo esc_attr($origen_lng); ?>" data-destino-lat="<?php echo esc_attr($destino_lat); ?>" data-destino-lng="<?php echo esc_attr($destino_lng); ?>">
			</div>

			<div style="display:none" data-importe-km="<?php echo esc_attr($importe_km); ?>">
			</div>

			<input type="hidden" id="_qv_adicional_aplicado" value="<?php echo esc_attr($adicional_aplicado); ?>" />
			<input type="hidden" name="qv_distancia" id="qv_distancia_input" value="">
			<input type="hidden" name="qv_importe" id="qv_importe_input" value="">
			<input type="hidden" name="qv_total_general" id="" value="<?php echo esc_attr($total_general); ?>">

			<hr>
			<p><strong>Distancia:</strong> <span id="qv-distancia">-</span></p>
			<p><strong>Importe por Km:</strong> $<span id="qv-importe-km-display"><?php echo esc_html($importe_km); ?></span></p>
			<p><strong>Importe estimado:</strong> $<span id="qv-importe"><?php echo esc_html( number_format( $importe_estimado, 2 ) ); ?></span></p>

			<?php if ( ! empty( $gastos_extra ) ) : ?>
				<hr>
				<p><strong>Gastos extra:</strong></p>
				<ul class="qv-resumen-extras">
					<?php foreach ( $gastos_extra as $gasto ) : 
						if ( empty( $gasto['descripcion'] ) && ( ! isset($gasto['importe']) || $gasto['importe'] === '' ) ) continue; ?>
						<li>
							<?php echo esc_html( $gasto['descripcion'] ); ?> —
							<strong>$<?php echo esc_html( number_format( floatval( $gasto['importe'] ), 2 ) ); ?></strong>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<p id="qv-adicional" style="<?php echo ($adicional_aplicado > 0) ? '' : 'display:none;'; ?>">
				<strong>Adicional por viaje corto:</strong> 
				$<span id="qv-adicional-valor"><?php echo esc_html( number_format( $adicional_aplicado, 2 ) ); ?></span>
			</p>

			<hr>
			<p class="qv-resumen-total"><strong>Total:</strong> $<?php echo esc_html( number_format( $total_general, 2 ) ); ?></p>

			<?php /* echo '<pre style="max-height:500px;overflow:auto;">';
			print_r( get_post_meta( $post->ID ) );
			echo '</pre>' */ ?>
		</div>
		<?php
	}

	/* Guardar distancia e importe calculados desde el metabox Resumen */
	public function save_resumen_metabox( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
		if ( ! current_user_can( 'edit_post', $post_id ) ) return;

		if ( isset( $_POST['qv_distancia'] ) ) {
			update_post_meta( $post_id, '_qv_distancia', floatval( $_POST['qv_distancia'] ) );
		}

		if ( isset( $_POST['qv_importe'] ) ) {
			update_post_meta( $post_id, '_qv_importe', floatval( $_POST['qv_importe'] ) );
		}
		if ( isset( $_POST['qv_total_general'] ) ) {
			update_post_meta( $post_id, '_qv_total_general', floatval( $_POST['qv_total_general'] ) );
		}
	}
}

add_action('save_post', function( $post_id ) {

	/* Evitar autosaves */
	if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
	if ( get_post_type($post_id) !== 'viaje' ) return;

	/* 1) GUARDAR CAMPOS DEL METABOX */

	/* Empresa */
	if (isset($_POST['qv_empresa'])) {
		update_post_meta($post_id, '_qv_empresa', intval($_POST['qv_empresa']));
	}

	/* Pasajero */
	if (isset($_POST['qv_pasajero'])) {
		update_post_meta($post_id, '_qv_pasajero', intval($_POST['qv_pasajero']));
	}

	/* Conductor */
	if (isset($_POST['qv_conductor'])) {
		update_post_meta($post_id, '_qv_conductor', intval($_POST['qv_conductor']));
	}

	/* Estado */
	if (isset($_POST['qv_estado'])) {
		update_post_meta($post_id, '_qv_estado', sanitize_text_field($_POST['qv_estado']));
	}

	/* Fecha */
	if (isset($_POST['qv_fecha'])) {
		update_post_meta($post_id, '_qv_fecha', sanitize_text_field($_POST['qv_fecha']));
	}

	/* Hora */
	if (isset($_POST['qv_hora'])) {
		update_post_meta($post_id, '_qv_hora', sanitize_text_field($_POST['qv_hora']));
	}

	/* Origen */
	if (isset($_POST['qv_origen'])) {
		update_post_meta($post_id, '_qv_origen', sanitize_text_field($_POST['qv_origen']));
	}

	/* Destino */
	if (isset($_POST['qv_destino'])) {
		update_post_meta($post_id, '_qv_destino', sanitize_text_field($_POST['qv_destino']));
	}

	/* 2) PROCESAR DISTANCIA + ADICIONAL  */

	/* Obtener distancia guardada */
	$distancia = get_post_meta($post_id, '_qv_distancia', true);
	$distancia = floatval(str_replace(',', '.', (string)$distancia));

	/* Obtener adicional configurado */
	$adicional_viaje_corto = floatval(get_option('qv_adicional_viaje_corto', 0));

	/* Inicializar */
	$adicional_aplicado = 0.0;

	/* Aplicar adicional si corresponde */
	if ($distancia > 0 && $distancia <= 10 && $adicional_viaje_corto > 0) {
		$adicional_aplicado = $adicional_viaje_corto;
	}

	/* Guardar resultado */
	update_post_meta($post_id, '_qv_adicional_aplicado', $adicional_aplicado);

}, 20, 1);

