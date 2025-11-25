<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class QV_Viajes_Utils {

    public static function get_estados_viaje() {
        return [
            'programado' => 'Programado',
            'curso'      => 'En curso',
            'finalizado' => 'Finalizado',
            'cancelado'  => 'Cancelado',
        ];
    }

    public static function get_label_estado( $clave ) {
        $estados = self::get_estados_viaje();
        return isset($estados[$clave]) ? $estados[$clave] : $clave;
    }
}
