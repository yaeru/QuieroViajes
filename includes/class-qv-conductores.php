<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class QV_Conductores {

    public function init() {
        add_action('init', [ $this, 'crear_rol_conductor' ] );
        add_action('show_user_profile', [ $this, 'agregar_metabox_conductor' ] );
        add_action('edit_user_profile', [ $this, 'agregar_metabox_conductor' ] );
        add_action('personal_options_update', [ $this, 'guardar_metabox_conductor' ] );
        add_action('edit_user_profile_update', [ $this, 'guardar_metabox_conductor' ] );
    }

    // Crear rol Conductor
    public function crear_rol_conductor() {
        if (!get_role('conductor')) {
            add_role('conductor', 'Conductor', [
                'read' => true,
                'edit_posts' => false,
            ]);
        }
    }

    // Metabox en perfil de usuario
    public function agregar_metabox_conductor($user) {
        if (!in_array('conductor', $user->roles)) return;
        ?>
        <h2>Información del Conductor</h2>
        <table class="form-table">
            <tr>
                <th><label for="dni">Número de DNI</label></th>
                <td><input type="number" name="dni" id="dni" value="<?php echo esc_attr(get_user_meta($user->ID,'dni',true)); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="direccion">Dirección</label></th>
                <td><input type="text" name="direccion" id="direccion" value="<?php echo esc_attr(get_user_meta($user->ID,'direccion',true)); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="celular">Número de Celular</label></th>
                <td><input type="text" name="celular" id="celular" value="<?php echo esc_attr(get_user_meta($user->ID,'celular',true)); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="registro">Registro Habilitante</label></th>
                <td><input type="text" name="registro" id="registro" value="<?php echo esc_attr(get_user_meta($user->ID,'registro',true)); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="registro_vencimiento">Fecha de Vencimiento del Registro</label></th>
                <td><input type="date" name="registro_vencimiento" id="registro_vencimiento" value="<?php echo esc_attr(get_user_meta($user->ID,'registro_vencimiento',true)); ?>"></td>
            </tr>
            <tr>
                <th><label for="auto">Auto</label></th>
                <td><input type="text" name="auto" id="auto" value="<?php echo esc_attr(get_user_meta($user->ID,'auto',true)); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="marca">Marca</label></th>
                <td><input type="text" name="marca" id="marca" value="<?php echo esc_attr(get_user_meta($user->ID,'marca',true)); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="modelo">Modelo</label></th>
                <td><input type="text" name="modelo" id="modelo" value="<?php echo esc_attr(get_user_meta($user->ID,'modelo',true)); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="anio">Año</label></th>
                <td><input type="number" name="anio" id="anio" value="<?php echo esc_attr(get_user_meta($user->ID,'anio',true)); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label>Aire acondicionado</label></th>
                <td>
                    <label><input type="radio" name="aire" value="si" <?php checked(get_user_meta($user->ID,'aire',true),'si'); ?>> Sí</label>
                    <label><input type="radio" name="aire" value="no" <?php checked(get_user_meta($user->ID,'aire',true),'no'); ?>> No</label>
                </td>
            </tr>
        </table>
        <?php
    }

    // Guardar datos del perfil
    public function guardar_metabox_conductor($user_id) {
        if (!current_user_can('edit_user', $user_id)) return;

        $campos = ['dni','direccion','celular','registro','registro_vencimiento','auto','marca','modelo','anio','aire'];

        foreach($campos as $campo) {
            if (isset($_POST[$campo])) {
                update_user_meta($user_id, $campo, sanitize_text_field($_POST[$campo]));
            }
        }
    }
}
