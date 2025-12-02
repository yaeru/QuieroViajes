<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class QV_Settings_Page {

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
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

		register_setting( 'qv_settings_group', 'qv_email_admin_notificaciones', [
			'type'              => 'string',
			'sanitize_callback' => function( $email ) {
				$email = sanitize_email( $email );
				return is_email( $email ) ? $email : '';
			},
			'default'			=> '',
		] );


		register_setting( 'qv_settings_group', 'qv_debug_mails', [
			'type'              => 'boolean',
			'sanitize_callback' => function($v){ return $v ? 1 : 0; },
			'default'           => 0,
		] );

	}

	/* Renderizar la página de ajustes */
	public function render_settings_page() {
		$api_key   = get_option( 'qv_google_maps_api_key', '' );
		$adicional = get_option( 'qv_adicional_viaje_corto', 0 );
		$email_notificaciones = get_option( 'qv_email_admin_notificaciones', 0 );
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

					<tr>
						<th scope="row"><label for="qv_email_admin_notificaciones">email_notificaciones</label></th>
						<td>
							<input type="email" name="qv_email_admin_notificaciones" id="qv_email_admin_notificaciones"
							value="<?php echo esc_attr( $email_notificaciones ); ?>" class="regular-text" />
							<p class="description">email_notificaciones al que se enviaran las notificaciones de nuevos viajes por parte de Empresas.</p>
						</td>
					</tr>

					<tr>
						<th scope="row"><label for="qv_debug_mails">Modo Debug de Emails</label></th>
						<td>
							<label>
								<input type="checkbox"
								name="qv_debug_mails"
								id="qv_debug_mails"
								value="1"
								<?php checked(1, get_option('qv_debug_mails', 0)); ?> />

								No enviar emails (solo registrar en debug.log)
							</label>
							<p class="description">
								Si está activado, ningún correo se enviará realmente. Todos los envíos quedarán registrados en debug.log.
							</p>
						</td>
					</tr>

				</table>

				<?php submit_button( 'Guardar ajustes' ); ?>
			</form>
		</div>
		<?php
	}


}
