<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class QV_Email_Templates {

    /**
     * Template para notificar un viaje (nuevo o actualización)
     * @param int $viaje_id
     * @param array $meta
     * @param string $recipient_name
     * @param bool $update  true si es actualización, false si es nuevo
     */
    public static function viaje($viaje_id, $meta, $recipient_name, $update = false) {
    	$site_name = get_bloginfo('name');
    	$permalink = get_permalink($viaje_id);

    	if ($update) {
    		$titulo  = "Actualización en tu viaje";
    		$mensaje = "Se han actualizado los detalles de tu viaje:";
    	} else {
    		$titulo  = "Nuevo viaje programado";
    		$mensaje = "Se ha registrado un nuevo viaje con los siguientes detalles:";
    	}

    	$html = "
    	<div style='font-family:Arial,sans-serif; color:#333; line-height:1.5;'>
    	<h2 style='color:#0073aa;font-size: 1.8em;'>{$titulo}</h2>
    	<p>Hola <strong>{$recipient_name}</strong>,</p>
    	<p>{$mensaje}</p>
    	<table style='border-collapse:collapse; width:100%;margin-top: 10px;'>
    	<tr><td><strong>Estado:</strong></td><td>{$meta['_qv_estado']}</td></tr>
    	<tr><td><strong>Fecha:</strong></td><td>{$meta['_qv_fecha']}</td></tr>
    	<tr><td><strong>Hora:</strong></td><td>{$meta['_qv_hora']}</td></tr>
    	<tr><td><strong>Origen:</strong></td><td>{$meta['_qv_origen']}</td></tr>
    	<tr><td><strong>Destino:</strong></td><td>{$meta['_qv_destino']}</td></tr>
    	<!-- Pago e Importe comentados -->
    	</table>
    	<p><strong>Observaciones:</strong> {$meta['_qv_observaciones']}</p>

    	<!-- Botón al viaje -->
    	<p style='margin-top:20px;'>
    	<a href='{$permalink}' 
    	style='display:inline-block; margin-bottom: 10px; padding:10px 20px; background-color:#0073aa; color:#fff; text-decoration:none; border-radius:4px;'>
    	Ver detalles del viaje
    	</a>
    	</p>

    	<p style='text-align: center;'>Gracias por usar <strong>{$site_name}</strong>.</p>
    	</div>";

    	return $html;
    }
}