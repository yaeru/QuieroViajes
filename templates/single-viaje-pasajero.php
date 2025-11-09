<?php
if (!defined('ABSPATH')) exit;
get_header();

global $post;

$empresa_id = get_post_meta($post->ID, '_qv_empresa', true);
$fecha = get_post_meta($post->ID, '_qv_fecha_programada', true);
$hora = get_post_meta($post->ID, '_qv_hora_programada', true);
$origen = get_post_meta($post->ID, '_qv_origen', true);
$destino = get_post_meta($post->ID, '_qv_destino', true);
$estado = get_post_meta($post->ID, '_qv_estado', true);
$conductor_id = get_post_meta($post->ID, '_qv_conductor', true);

$conductor = $conductor_id ? get_user_by('id', $conductor_id) : null;
?>

<div class="qv-viaje pasajero" style="max-width:800px;margin:40px auto;">
    <h1>Detalles del Viaje</h1>
    <p><strong>Estado:</strong> <?php echo esc_html($estado ?: 'Sin especificar'); ?></p>
    <p><strong>Fecha:</strong> <?php echo esc_html($fecha); ?> a las <?php echo esc_html($hora); ?></p>
    <p><strong>Origen:</strong> <?php echo esc_html($origen); ?></p>
    <p><strong>Destino:</strong> <?php echo esc_html($destino); ?></p>

    <?php if ($conductor): ?>
        <hr>
        <h3>Conductor asignado</h3>
        <p><strong>Nombre:</strong> <?php echo esc_html($conductor->display_name); ?></p>
        <p><strong>Celular:</strong> <?php echo esc_html(get_user_meta($conductor->ID, 'celular', true)); ?></p>
        <p><strong>Auto:</strong> <?php echo esc_html(get_user_meta($conductor->ID, 'auto', true)); ?></p>
        <p><strong>Modelo:</strong> <?php echo esc_html(get_user_meta($conductor->ID, 'marca', true)); ?> <?php echo esc_html(get_user_meta($conductor->ID, 'modelo', true)); ?> (<?php echo esc_html(get_user_meta($conductor->ID, 'anio', true)); ?>)</p>
    <?php else: ?>
        <p><em>No hay conductor asignado aún.</em></p>
    <?php endif; ?>
</div>

<?php get_footer(); ?>
