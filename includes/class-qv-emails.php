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

		// Meta y status actual
		$meta = $this->get_viaje_meta($post_id);
		$current_status = get_post_status($post_id);

		// Quién ejecuta la acción (actor)
		$current_user = wp_get_current_user();
		$current_is_admin = $current_user && ( in_array('administrator', (array)$current_user->roles) || current_user_can('manage_options') );
		$current_is_empresa = $current_user && in_array('empresa', (array)$current_user->roles);

		// Quién es el autor (owner del post)
		$author = get_userdata($post->post_author);
		$author_is_empresa = $author && in_array('empresa', (array)$author->roles);

		/**
		 * 1) Si EL QUE EJECUTA es una empresa y el post está en 'pending'
		 *    -> enviar 1 email AL DUEÑO y NADA MÁS.
		 *    (esto cubre el flow: empresa crea -> envia para revisión)
		 */
		if ($current_is_empresa && $current_status === 'pending') {

			// evitar duplicados por auto-draft -> draft -> pending
			if (get_post_meta($post_id, '_qv_revision_enviada', true)) {
				return;
			}

			update_post_meta($post_id, '_qv_revision_enviada', 1);

			$admin_email = get_option('qv_email_admin_notificaciones', '');
			if (is_email($admin_email)) {

				$subject_admin = "La empresa {$current_user->display_name} creó un viaje para revisión #{$post_id}";
				// Usamos template dedicado al dueño
				if (class_exists('QV_Email_Admin_Templates')) {
					$message_admin = QV_Email_Admin_Templates::viaje_revision($post_id, $current_user->display_name);
				} else {
					// Fallback simple si no existe la clase
					$message_admin = "<p>La empresa <strong>{$current_user->display_name}</strong> ha enviado un viaje para revisión. ID: {$post_id}</p>";
				}

				wp_mail(
					$admin_email,
					$subject_admin,
					$message_admin,
					array('Content-Type: text/html; charset=UTF-8')
				);
			}

			return; // NO enviar nada más en este caso
		}

		/**
		 * 2) Si QUIÉN EJECUTA es ADMIN y el post está publicado -> enviar a implicados.
		 *    Esto cubre:
		 *      - admin crea y publica un viaje (new, publish)
		 *      - admin publica un viaje creado por empresa (pending -> publish)
		 *      - admin actualiza un viaje ya publicado (publish -> publish) (wp_insert_post update=true)
		 */
		if ($current_is_admin && $current_status === 'publish') {

			// destinatarios: pasajero, conductor, empresa (NO dueño)
			$empresa_id = (int) get_post_meta($post_id, '_qv_empresa', true);

			$recipients = array_filter(array(
				$this->get_user_email($meta['pasajero_id']),
				$this->get_user_email($meta['conductor_id']),
				$this->get_user_email($empresa_id),
			));

			$recipients = array_unique($recipients);

			if (empty($recipients)) return;

			foreach ($recipients as $to) {

				$recipient_id   = $this->get_user_id_by_email($to);
				$recipient_name = $recipient_id ? $this->get_user_name($recipient_id) : 'Usuario';

				// Si $update === true => es una actualización; si false => creación
				$subject = $update
					? "Actualización en tu viaje #{$post_id}"
					: "Nuevo viaje programado #{$post_id}";

				$message = QV_Email_Templates::viaje($post_id, $meta, $recipient_name, $update);
				$headers = array('Content-Type: text/html; charset=UTF-8');

				wp_mail($to, $subject, $message, $headers);
			}

			return;
		}

		// En todos los demás casos: no enviamos nada (ej. empresa guardando drafts, o otros roles)
		return;
	}

	/* HELPERS */
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
		$u = get_user_by('email', $email);
		return $u ? $u->ID : 0;
	}
}
