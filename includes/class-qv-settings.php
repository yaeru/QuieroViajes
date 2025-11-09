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

		register_setting( 'qv_settings_group', 'qv_costo_viaje_fijo', [
			'type'              => 'number',
			'sanitize_callback' => 'floatval',
			'default'           => 0,
		] );
	}

	/* Renderizar la página de ajustes */
	public function render_settings_page() {
		$api_key   = get_option( 'qv_google_maps_api_key', '' );
		$adicional = get_option( 'qv_adicional_viaje_corto', 0 );
		$costo_fijo = get_option( 'qv_costo_viaje_fijo', 0 );
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
						<th scope="row"><label for="qv_costo_viaje_fijo">Costo fijo por viaje ($)</label></th>
						<td>
							<input type="number" step="0.01" name="qv_costo_viaje_fijo" id="qv_costo_viaje_fijo"
							value="<?php echo esc_attr( $costo_fijo ); ?>" class="small-text" />
							<p class="description">Importe costo_fijo aplicado a viajes cortos (en pesos).</p>
						</td>
					</tr>
				</table>

				<?php submit_button( 'Guardar ajustes' ); ?>
			</form>
		</div>
		<?php
	}
}
