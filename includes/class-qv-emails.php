<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class QV_Emails {

	public function __construct() {
		/* Enganchar al final del ciclo de inserción/actualización */
		add_action('wp_insert_post', array($this, 'enviar_email_nuevo_viaje'), 10, 3);
	}

	/* Envía emails cuando se crea o actualiza un viaje */
	public function enviar_email_nuevo_viaje($post_id, $post, $update) {
		/* Solo para CPT viaje */
		if ($post->post_type !== 'viaje') return;

		/*error_log("QV_Emails: disparado en wp_insert_post para viaje $post_id (update=" . ($update ? 'true' : 'false') . ")");*/

		$meta = $this->get_viaje_meta($post_id);
		$empresa_id = (int) get_post_meta($post_id, '_qv_empresa', true);

		/* Destinatarios: pasajero, conductor y operadores */
		$recipients = array_filter(array(
			$this->get_user_email($meta['pasajero_id']),
			$this->get_user_email($meta['conductor_id']),
			$this->get_user_email($empresa_id),
		));

		$recipients = array_unique(array_merge($recipients, $this->get_role_emails('operador')));

		if (empty($recipients)) {
			return;
		}

		foreach ($recipients as $to) {
			$recipient_id   = $this->get_user_id_by_email($to);
			$recipient_name = $recipient_id ? $this->get_user_name($recipient_id) : 'Usuario';

			if ($update) {
				$subject = "Actualización en tu viaje #{$post_id}";
			} else {
				$subject = "Nuevo viaje programado #{$post_id}";
			}

			$message = QV_Email_Templates::viaje($post_id, $meta, $recipient_name, $update);
			$headers = array('Content-Type: text/html; charset=UTF-8');

			wp_mail($to, $subject, $message, $headers);
		}
	}

	private function get_viaje_meta($viaje_id) {
		return array(
			'conductor_id'     => (int) get_post_meta($viaje_id, '_qv_conductor', true),
			'pasajero_id'      => (int) get_post_meta($viaje_id, '_qv_pasajero', true),
			'empresa_id'       => (int) get_post_meta($viaje_id, '_qv_empresa', true),
			'_qv_estado'       => get_post_meta($viaje_id, '_qv_estado', true),
			'_qv_fecha'        => get_post_meta($viaje_id, '_qv_fecha', true),
			'_qv_hora'         => get_post_meta($viaje_id, '_qv_hora', true),
			'_qv_origen'       => get_post_meta($viaje_id, '_qv_origen', true),
			'_qv_destino'      => get_post_meta($viaje_id, '_qv_destino', true),
			'_qv_pago'         => get_post_meta($viaje_id, '_qv_pago', true),
			'_qv_total_general'=> get_post_meta($viaje_id, '_qv_total_general', true),
			'_qv_observaciones'=> get_post_meta($viaje_id, '_qv_observaciones', true),
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

	private function get_role_emails($role) {
		$users = get_users(array('role' => $role, 'fields' => array('user_email')));
		return array_map(function($u){ return $u->user_email; }, $users);
	}
}