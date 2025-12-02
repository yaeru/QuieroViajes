<?php
if (!defined('ABSPATH')) exit;

class QV_Email_Admin_Templates {

	/**
	 * Email para cuando una empresa envía un viaje para revisión
	 */
	public static function viaje_revision($viaje_id, $empresa_name) {

		$site_name = get_bloginfo('name');
		$edit_link = get_admin_url(null, "post.php?post={$viaje_id}&action=edit");

		$html = "
		<div style='font-family:Arial,sans-serif; color:#333; line-height:1.6;'>
			<h2 style='color:#d63638;'>Nuevo viaje enviado para revisión</h2>

			<p>La empresa <strong>{$empresa_name}</strong> ha enviado un viaje para revisión.</p>

			<p><strong>ID del viaje:</strong> {$viaje_id}</p>

			<p style='margin-top:20px;'>
				<a href='{$edit_link}'
				style='display:inline-block; padding:10px 18px; background:#d63638; color:#fff;
					   text-decoration:none; border-radius:4px;'>
				Revisar viaje ahora
				</a>
			</p>

			<p style='margin-top:25px; text-align:center;color:#777;'>
				{$site_name}
			</p>
		</div>";

		return $html;
	}
}
