<?php
/* Envío centralizado con modo debug */

if (!function_exists('qv_send_mail')) {

	function qv_send_mail( $to, $subject, $message, $headers = [], $attachments = [] ) {

		$debug = get_option('qv_debug_mails', 0);

		$payload = [
			'to'          => $to,
			'subject'     => $subject,
			'message'     => $message,
			'headers'     => $headers,
			'attachments' => $attachments,
		];

		/* Si está en modo debug NO enviamos correo */
		if ( $debug ) {

			error_log("=== QV_MAIL_DEBUG ===");
			error_log(print_r($payload, true));
			error_log("=== FIN QV_MAIL_DEBUG ===");

			return true; /* ddd: simulamos envío */
		}

		/* ddd: Envío normal */
		return wp_mail( $to, $subject, $message, $headers, $attachments );
	}

}
