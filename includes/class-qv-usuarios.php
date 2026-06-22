<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class QV_Usuarios {

	public function init() {
		add_action('init', [ $this, 'crear_roles' ] );
		add_action('show_user_profile', [ $this, 'agregar_metabox_pasajero' ] );
		add_action('edit_user_profile', [ $this, 'agregar_metabox_pasajero' ] );
		add_action('personal_options_update', [ $this, 'guardar_metabox_pasajero' ] );
		add_action('edit_user_profile_update', [ $this, 'guardar_metabox_pasajero' ] );

		add_action('show_user_profile', [ $this, 'agregar_metabox_empresa' ] );
		add_action('edit_user_profile', [ $this, 'agregar_metabox_empresa' ] );
		add_action('personal_options_update', [ $this, 'guardar_metabox_empresa' ] );
		add_action('edit_user_profile_update', [ $this, 'guardar_metabox_empresa' ] );
	}

	// Crear roles Empresa y Pasajero
	public function crear_roles() {
		if (!get_role('empresa')) {
			add_role('empresa', 'Empresa', [
				'read' => true,
				'edit_posts' => false,
				'edit_viajes'            => true,
				'edit_others_viajes'     => false,
				'publish_viajes'         => false,
				'read_viaje'             => true,
				'delete_viajes'          => false,
			]);
		}
		if (!get_role('pasajero')) {
			add_role('pasajero', 'Pasajero', [
				'read' => true,
				'edit_posts' => false,
			]);
		}
	}

	// Metabox perfil Pasajero
	public function agregar_metabox_pasajero($user) {
		if (!in_array('pasajero', $user->roles)) return;

		// Obtener empresas disponibles
		$args = [
			'role' => 'empresa',
			'orderby' => 'display_name',
			'order' => 'ASC'
		];
		$empresas = get_users($args);
		$empresa_id = get_user_meta($user->ID,'empresa_id',true);
		$telefono = get_user_meta($user->ID,'telefono',true);

		?>
		<h2>Información del Pasajero</h2>
		<table class="form-table">
			<tr>
				<th><label for="empresa_id">Empresa</label></th>
				<td>
					<select name="empresa_id" id="empresa_id">
						<option value="">-- Seleccione una empresa --</option>
						<?php foreach($empresas as $empresa): ?>
							<option value="<?php echo esc_attr($empresa->ID); ?>" <?php selected($empresa_id,$empresa->ID); ?>>
								<?php echo esc_html($empresa->display_name); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="telefono">Teléfono</label></th>
				<td><input type="text" name="telefono" id="telefono" value="<?php echo esc_attr($telefono); ?>" class="regular-text"></td>
			</tr>
		</table>
		<?php
	}

	// Guardar datos del perfil Pasajero
	public function guardar_metabox_pasajero($user_id) {
		if (!current_user_can('edit_user', $user_id)) return;

		if (isset($_POST['empresa_id'])) {
			update_user_meta($user_id,'empresa_id', intval($_POST['empresa_id']));
		}
		if (isset($_POST['telefono'])) {
			update_user_meta($user_id,'telefono', sanitize_text_field($_POST['telefono']));
		}
	}

	// Metabox perfil Empresa
	public function agregar_metabox_empresa($user) {
		if (!in_array('empresa', $user->roles)) return;
		
		// CORRECCIÓN: Se obtienen los datos específicos aquí dentro
		//$telefono_empresa = get_user_meta($user->ID,'telefono_empresa',true);
		$importe_km_empresa = get_user_meta($user->ID,'importe_km_empresa',true);
		?>
		<h2>Información de la Empresa</h2>
		<table class="form-table">
<!-- 			<tr>
				<th><label for="telefono_empresa">Teléfono</label></th>
				<td><input type="text" name="telefono_empresa" id="telefono_empresa" value="<?php echo esc_attr($telefono_empresa); ?>" class="regular-text"></td>
			</tr> -->
			<tr>
				<th><label for="importe_km_empresa">Importe x KM</label></th>
				<td>
					<input type="text" name="importe_km_empresa" id="importe_km_empresa" value="<?php echo esc_attr($importe_km_empresa); ?>" class="regular-text">
					<p class="description">Importe por defecto para esta empresa, sobrescribe el importe x km indicado en Ajustes.</p>
				</td>
			</tr>
		</table>
		<?php
	}

	// Guardar datos del perfil Empresa
	public function guardar_metabox_empresa($user_id) {
		if (!current_user_can('edit_user', $user_id)) return;
		// if (isset($_POST['telefono_empresa'])) {
		// 	update_user_meta($user_id,'telefono_empresa', sanitize_text_field($_POST['telefono_empresa']));
		// }
		if (isset($_POST['importe_km_empresa'])) {
			update_user_meta($user_id,'importe_km_empresa', sanitize_text_field($_POST['importe_km_empresa']));
		}
	}
}