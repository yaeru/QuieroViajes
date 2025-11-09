<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class QV_Usuarios {

    public function init() {
        add_action('init', [ $this, 'crear_roles' ] );
        add_action('show_user_profile', [ $this, 'agregar_metabox_pasajero' ] );
        add_action('edit_user_profile', [ $this, 'agregar_metabox_pasajero' ] );
        add_action('personal_options_update', [ $this, 'guardar_metabox_pasajero' ] );
        add_action('edit_user_profile_update', [ $this, 'guardar_metabox_pasajero' ] );
    }

    // Crear roles Empresa y Pasajero
    public function crear_roles() {
        if (!get_role('empresa')) {
            add_role('empresa', 'Empresa', [
                'read' => true,
                'edit_posts' => true,
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
}
