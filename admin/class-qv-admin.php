<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class QV_Admin {

	public function init() {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
		add_action( 'add_meta_boxes', [ $this, 'register_metaboxes' ] );
		add_action( 'save_post', [ $this, 'save_detalles_metabox' ] );
		add_action( 'save_post', [ $this, 'save_origen_destino_metabox' ] );
		add_action( 'save_post', [ $this, 'save_resumen_metabox' ] );

		// Página de ajustes
		add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );

		// Avisos de error Google Maps
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

	// Inyecta handler para capturar errores de Google Maps
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

	// Muestra aviso en admin si hay error
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

		add_meta_box(
			'qv_resumen_viaje',
			'Resumen del viaje',
			[ $this, 'render_resumen_metabox' ],
			'viaje',
			'side',
			'default'
		);
	}

	/* Metabox Detalles del Viaje */
	public function render_detalles_metabox( $post ) {
		wp_nonce_field( 'qv_save_detalles', 'qv_detalles_nonce' );

		$estado        = get_post_meta( $post->ID, '_qv_estado', true );
		$fecha         = get_post_meta( $post->ID, '_qv_fecha', true );
		$hora          = get_post_meta( $post->ID, '_qv_hora', true );
		$empresa_id    = get_post_meta( $post->ID, '_qv_empresa', true );
		$importe_km    = get_post_meta( $post->ID, '_qv_importe_km', true );
		$pago          = get_post_meta( $post->ID, '_qv_pago', true );
		$observaciones = get_post_meta( $post->ID, '_qv_observaciones', true );
		$conductor_id  = get_post_meta( $post->ID, '_qv_conductor', true );

        // Conductores
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
							<?php
							$estados = [ 'programado' => 'Programado', 'curso' => 'En curso', 'finalizado' => 'Finalizado', 'cancelado' => 'Cancelado' ];
							foreach ( $estados as $key => $label ) {
								echo '<option value="'.esc_attr($key).'" '.selected($estado, $key, false).'>'.esc_html($label).'</option>';
							}
							?>
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
						<label>Importe por km:</label>
					</th>
					<td>
						<input type="number" step="0.01" name="qv_importe_km" value="<?php echo esc_attr( $importe_km ); ?>">
					</td>
				</tr>
				<tr>
					<th>
						<label>Forma de pago:</label>
					</th>
					<td>
						<select name="qv_pago">
							<?php
							$formas_pago = [ 'efectivo' => 'Efectivo', 'transferencia' => 'Transferencia', 'tarjeta' => 'Tarjeta' ];
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
				<tr>
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
			'qv_importe_km'    => '_qv_importe_km',
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

		// Si el usuario actual es empresa, forzamos solo su empresa
		$empresas = [];
		if ( in_array( 'empresa', (array) $current_user->roles ) ) {
			$empresas[] = $current_user;
		} else {
			$empresas = get_users( [ 'role' => 'empresa', 'orderby' => 'display_name', 'order' => 'ASC' ] );
		}

		// Filtrar pasajeros
		$pasajeros_args = [ 'role' => 'pasajero', 'orderby' => 'display_name', 'order' => 'ASC' ];
		$pasajeros = get_users( $pasajeros_args );

		// Si el usuario empresa está logueado, filtrar pasajeros de su empresa
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


	/* Metabox Origen destino */
	public function render_origen_destino_metabox( $post ) {
		wp_nonce_field( 'qv_save_origen_destino', 'qv_origen_destino_nonce' );

		$origen      = get_post_meta( $post->ID, '_qv_origen', true );
		$origen_lat  = get_post_meta( $post->ID, '_qv_origen_lat', true );
		$origen_lng  = get_post_meta( $post->ID, '_qv_origen_lng', true );

		$destino     = get_post_meta( $post->ID, '_qv_destino', true );
		$destino_lat = get_post_meta( $post->ID, '_qv_destino_lat', true );
		$destino_lng = get_post_meta( $post->ID, '_qv_destino_lng', true );
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
		];

		foreach ( $fields as $form_field => $meta_key ) {
			if ( isset( $_POST[$form_field] ) ) {
				update_post_meta( $post_id, $meta_key, sanitize_text_field( $_POST[$form_field] ) );
			}
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

		?>
		<div id="qv-resumen-box">
			<p><strong>Origen:</strong> <?php echo esc_html($origen); ?></p>
			<p><strong>Destino:</strong> <?php echo esc_html($destino); ?></p>
			<p><strong>Importe por Km:</strong> $<span id="qv-importe-km-display"><?php echo esc_html($importe_km); ?></span></p>

			<!-- Hidden inputs para lat/lng -->
			<input type="hidden" id="qv_origen_lat" value="<?php echo esc_attr($origen_lat); ?>" />
			<input type="hidden" id="qv_origen_lng" value="<?php echo esc_attr($origen_lng); ?>" />
			<input type="hidden" id="qv_destino_lat" value="<?php echo esc_attr($destino_lat); ?>" />
			<input type="hidden" id="qv_destino_lng" value="<?php echo esc_attr($destino_lng); ?>" />
			<input type="hidden" id="qv_importe_km" value="<?php echo esc_attr($importe_km); ?>" />

			<hr>
			<p><strong>Distancia:</strong> <span id="qv-distancia">-</span></p>
			<p><strong>Importe estimado:</strong> $<span id="qv-importe">-</span></p>
		</div>
		<?php
	}


	/* Guardar distancia e importe calculados desde el metabox Resumen */
	public function save_resumen_metabox( $post_id ) {
		/* No hacer nada si es autosave, ni permisos, ni nonce */
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
		if ( ! current_user_can( 'edit_post', $post_id ) ) return;

		/* Obtenemos valores enviados por JS en inputs hidden */
		if ( isset($_POST['qv_distancia']) ) {
			$distancia = floatval($_POST['qv_distancia']);
			update_post_meta( $post_id, '_qv_distancia', $distancia );
		}

		if ( isset($_POST['qv_importe']) ) {
			$importe = floatval($_POST['qv_importe']);
			update_post_meta( $post_id, '_qv_importe', $importe );
		}
	}

	/* Añadir submenú dentro del CPT "Viajes" */
	public function add_settings_page() {
		add_submenu_page(
			'edit.php?post_type=viaje',
			'Ajustes de Viajes',
			'Ajustes',
			'manage_options',
			'qv-settings',
			[ $this, 'render_settings_page' ]
		);
	}

	/* Registrar los ajustes */
	public function register_settings() {
		register_setting( 'qv_settings_group', 'qv_google_maps_api_key', [
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		] );

		register_setting( 'qv_settings_group', 'qv_adicional_viaje_corto', [
			'type'              => 'number',
			'sanitize_callback' => 'floatval',
			'default'           => 0,
		] );
	}

	/* Renderizar la página de ajustes */
	public function render_settings_page() {
		$api_key   = get_option( 'qv_google_maps_api_key', '' );
		$adicional = get_option( 'qv_adicional_viaje_corto', 0 );
		?>
		<div class="wrap">
			<h1>Ajustes de Quiero Viajes</h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'qv_settings_group' ); ?>
				<?php do_settings_sections( 'qv_settings_group' ); ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="qv_google_maps_api_key">Google Maps API Key</label></th>
						<td>
							<input type="text" name="qv_google_maps_api_key" id="qv_google_maps_api_key"
							value="<?php echo esc_attr( $api_key ); ?>" class="regular-text" />
							<p class="description">Tu clave de la API de Google Maps (no se mostrará públicamente).</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="qv_adicional_viaje_corto">Adicional viaje corto ($)</label></th>
						<td>
							<input type="number" step="0.01" name="qv_adicional_viaje_corto" id="qv_adicional_viaje_corto"
							value="<?php echo esc_attr( $adicional ); ?>" class="small-text" />
							<p class="description">Importe adicional aplicado a viajes cortos (en pesos).</p>
						</td>
					</tr>
				</table>

				<?php submit_button( 'Guardar ajustes' ); ?>
			</form>
		</div>
		<?php
	}

}


