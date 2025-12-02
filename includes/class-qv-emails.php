<?php
if (!defined('ABSPATH')) exit;

class QV_Emails {

    public function __construct() {
        add_action('wp_insert_post', array($this, 'enviar_email_nuevo_viaje'), 10, 3);
    }

    public function enviar_email_nuevo_viaje($post_id, $post, $update) {

        if ($post->post_type !== 'viaje') return;

        // Evitar autosaves y revisiones
        if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) return;

        $meta       = $this->get_viaje_meta($post_id);
        $empresa_id = (int) get_post_meta($post_id, '_qv_empresa', true);

        // Emails de pasajero, conductor y empresa (flujo original)
        $recipients = array_filter(array(
            $this->get_user_email($meta['pasajero_id']),
            $this->get_user_email($meta['conductor_id']),
            $this->get_user_email($empresa_id),
        ));

        /** --------------------------------------------------------
         *  NUEVO: EMAIL PARA EL DUEÑO SOLO SI EL AUTOR ES EMPRESA
         *  -------------------------------------------------------- */
        $admin_email = get_option('qv_email_admin_notificaciones', '');
        $author      = get_userdata($post->post_author);

        if ($author && in_array('empresa', $author->roles) && is_email($admin_email)) {
            
            // Email especial SOLO al dueño
            $subject_admin = "La empresa {$author->display_name} creó un viaje para revisión #{$post_id}";
            $message_admin = QV_Email_Admin_Templates::viaje_revision($post->ID, $author->display_name);

            wp_mail(
                $admin_email,
                $subject_admin,
                $message_admin,
                array('Content-Type: text/html; charset=UTF-8')
            );
        }

        /** --------------------------------------------------------
         *  FLUJO ORIGINAL (NO SE TOCA)
         *  -------------------------------------------------------- */
        $recipients = array_unique($recipients);

        if (empty($recipients)) return;

        foreach ($recipients as $to) {

            $recipient_id   = $this->get_user_id_by_email($to);
            $recipient_name = $recipient_id ? $this->get_user_name($recipient_id) : 'Usuario';

            $subject = $update
                ? "Actualización en tu viaje #{$post_id}"
                : "Nuevo viaje programado #{$post_id}";

            $message = QV_Email_Templates::viaje($post_id, $meta, $recipient_name, $update);
            $headers = array('Content-Type: text/html; charset=UTF-8');

            wp_mail($to, $subject, $message, $headers);
        }
    }

    private function get_viaje_meta($viaje_id) {
        return array(
            'conductor_id'      => (int) get_post_meta($viaje_id, '_qv_conductor', true),
            'pasajero_id'       => (int) get_post_meta($viaje_id, '_qv_pasajero', true),
            'empresa_id'        => (int) get_post_meta($viaje_id, '_qv_empresa', true),
            '_qv_estado'        => get_post_meta($viaje_id, '_qv_estado', true),
            '_qv_fecha'         => get_post_meta($viaje_id, '_qv_fecha', true),
            '_qv_hora'          => get_post_meta($viaje_id, '_qv_hora', true),
            '_qv_origen'        => get_post_meta($viaje_id, '_qv_origen', true),
            '_qv_destino'       => get_post_meta($viaje_id, '_qv_destino', true),
            '_qv_pago'          => get_post_meta($viaje_id, '_qv_pago', true),
            '_qv_total_general' => get_post_meta($viaje_id, '_qv_total_general', true),
            '_qv_observaciones' => get_post_meta($viaje_id, '_qv_observaciones', true),
        );
    }

    private function get_user_email($user_id) {
        $u = get_userdata($user_id);
        return $u ? $u->user_email : '';
    }

    private function get_user_name($user_id) {
        $u = get_userdata($user_id);
        return $u ? $u->display_name : 'Usuario';
    }

    private function get_user_id_by_email($email) {
        $user = get_user_by('email', $email);
        return $user ? $user->ID : 0;
    }
}
