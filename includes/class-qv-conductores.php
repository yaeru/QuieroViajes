<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class QV_Conductores {

	public function init() {
		add_action('init', [ $this, 'crear_rol_conductor' ] );
		add_action('show_user_profile', [ $this, 'agregar_metabox_conductor' ] );
		add_action('edit_user_profile', [ $this, 'agregar_metabox_conductor' ] );
		add_action('personal_options_update', [ $this, 'guardar_metabox_conductor' ] );
		add_action('edit_user_profile_update', [ $this, 'guardar_metabox_conductor' ] );

		/* Asegurar que el formulario de edición de usuario soporte subida de archivos */
		add_action('user_edit_form_tag', [ $this, 'user_edit_form_enctype' ] );
	}

	/* Crear rol Conductor */
	public function crear_rol_conductor() {
		if (! get_role('conductor') ) {
			add_role('conductor', 'Conductor', [
				'read' => true,
				'edit_posts' => false,
			]);
		}
	}

	/* Añadir enctype multipart/form-data al formulario de edición de usuario */
	public function user_edit_form_enctype() {
		/* Esto añade enctype al <form> del perfil para permitir uploads */
		echo ' enctype="multipart/form-data"';
	}

	/* Metabox en perfil de usuario */
	public function agregar_metabox_conductor( $user ) {
		if ( ! in_array( 'conductor', (array) $user->roles ) ) return;

		/* Obtener attachment_id y URL si existe */
		$foto_auto_id  = get_user_meta( $user->ID, 'foto_auto', true );
		$foto_auto_url = $foto_auto_id ? wp_get_attachment_url( $foto_auto_id ) : '';
		?>
		<h2>Información del Conductor</h2>
		<table class="form-table">
			<tr>
				<th><label for="dni">Número de DNI</label></th>
				<td><input type="number" name="dni" id="dni" value="<?php echo esc_attr( get_user_meta( $user->ID, 'dni', true ) ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th><label for="direccion">Dirección</label></th>
				<td><input type="text" name="direccion" id="direccion" value="<?php echo esc_attr( get_user_meta( $user->ID, 'direccion', true ) ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th><label for="celular">Número de Celular</label></th>
				<td><input type="text" name="celular" id="celular" value="<?php echo esc_attr( get_user_meta( $user->ID, 'celular', true ) ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th><label for="registro">Registro Habilitante</label></th>
				<td><input type="text" name="registro" id="registro" value="<?php echo esc_attr( get_user_meta( $user->ID, 'registro', true ) ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th><label for="registro_vencimiento">Fecha de Vencimiento del Registro</label></th>
				<td><input type="date" name="registro_vencimiento" id="registro_vencimiento" value="<?php echo esc_attr( get_user_meta( $user->ID, 'registro_vencimiento', true ) ); ?>"></td>
			</tr>
			<tr>
				<th><label for="marca">Marca</label></th>
				<td><input type="text" name="marca" id="marca" value="<?php echo esc_attr( get_user_meta( $user->ID, 'marca', true ) ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th><label for="modelo">Modelo</label></th>
				<td><input type="text" name="modelo" id="modelo" value="<?php echo esc_attr( get_user_meta( $user->ID, 'modelo', true ) ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th><label for="patente">Patente</label></th>
				<td><input type="text" name="patente" id="patente" value="<?php echo esc_attr( get_user_meta( $user->ID, 'patente', true ) ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th><label for="anio">Año</label></th>
				<td><input type="number" name="anio" id="anio" value="<?php echo esc_attr( get_user_meta( $user->ID, 'anio', true ) ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th><label>Aire acondicionado</label></th>
				<td>
					<label><input type="radio" name="aire" value="si" <?php checked( get_user_meta( $user->ID, 'aire', true ), 'si' ); ?>> Sí</label>
					<label><input type="radio" name="aire" value="no" <?php checked( get_user_meta( $user->ID, 'aire', true ), 'no' ); ?>> No</label>
				</td>
			</tr>

			<tr>
				<th><label for="foto_auto">Foto del Vehículo</label></th>
				<td>
					<?php if ( $foto_auto_url ) : ?>
						<div style="margin-bottom:8px;">
							<img src="<?php echo esc_url( $foto_auto_url ); ?>" alt="Foto del vehículo" style="max-width:150px; height:auto; display:block; margin-bottom:6px;">
							<label><input type="checkbox" name="delete_foto_auto" value="1"> Eliminar foto actual</label>
						</div>
					<?php endif; ?>

					<input type="file" name="foto_auto" id="foto_auto" accept="image/*">
					<p class="description">Subí una foto del vehículo. Podés reemplazarla o eliminar la existente.</p>
				</td>
			</tr>
		</table>
		<?php
	}

	/* Guardar datos del perfil */
	public function guardar_metabox_conductor( $user_id ) {
		if ( ! current_user_can( 'edit_user', $user_id ) ) return;

		/* Campos sencillos de texto/fecha/number/radio */
		$campos = [ 'dni', 'direccion', 'celular', 'registro', 'registro_vencimiento', 'marca', 'modelo', 'anio', 'patente', 'aire' ];

		foreach ( $campos as $campo ) {
			if ( isset( $_POST[ $campo ] ) ) {
				update_user_meta( $user_id, $campo, sanitize_text_field( wp_unslash( $_POST[ $campo ] ) ) );
			}
		}

		/* Manejo de la foto del vehículo */
		/* 1) Si se pidió eliminarla -> borramos attachment y meta */
		if ( isset( $_POST['delete_foto_auto'] ) && $_POST['delete_foto_auto'] ) {
			$old_id = get_user_meta( $user_id, 'foto_auto', true );
			if ( $old_id ) {
				wp_delete_attachment( intval( $old_id ), true );
				delete_user_meta( $user_id, 'foto_auto' );
			}
		}

		/* 2) Si se subió un nuevo archivo -> procesarlo y reemplazar (o añadir) */
		if ( isset( $_FILES['foto_auto'] ) && ! empty( $_FILES['foto_auto']['name'] ) ) {

			/* Incluir utilidades de WP para uploads y attachments */
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';

			/* Subir archivo (test_form => false permite subir desde este formulario de perfil) */
			$uploaded = wp_handle_upload( $_FILES['foto_auto'], [ 'test_form' => false ] );

			if ( isset( $uploaded['error'] ) ) {
				/* En caso de error en la subida, no rompemos la actualización del perfil - opcional: podrías notificar */
			} else {
				$filename = $uploaded['file'];
				$filetype = wp_check_filetype( basename( $filename ), null );

				$attachment = [
					'post_mime_type' => $filetype['type'],
					'post_title'     => sanitize_file_name( basename( $filename ) ),
					'post_content'   => '',
					'post_status'    => 'inherit',
				];

				/* Insertar attachment en la biblioteca */
				$attach_id = wp_insert_attachment( $attachment, $filename );

				if ( ! is_wp_error( $attach_id ) ) {
					/* Generar metadatos y actualizar */
					$attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
					wp_update_attachment_metadata( $attach_id, $attach_data );

					/* Si había una foto anterior, eliminarla para no dejar basura */
					$old_id = get_user_meta( $user_id, 'foto_auto', true );
					if ( $old_id && $old_id != $attach_id ) {
						wp_delete_attachment( intval( $old_id ), true );
					}

					/* Guardar nuevo attachment id en meta de usuario */
					update_user_meta( $user_id, 'foto_auto', intval( $attach_id ) );
				}
			}
		}
	}
}
