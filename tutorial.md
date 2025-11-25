# Quiero Viajes - Guía de Usuario

Bienvenido a **Quiero Viajes**, el sistema para gestionar viajes, pasajeros, conductores y notificaciones de manera simple y organizada.  
Este documento explica cómo cargar viajes, administrar usuarios y entender los flujos principales.

---

## 1. Roles del sistema

El plugin maneja tres tipos de usuarios:

- **Administrador**  
  Tiene control total sobre los viajes, usuarios y configuraciones. Puede crear, actualizar y eliminar viajes, asignar pasajeros y conductores, y recibir notificaciones por email.

- **Empresa**  
  Usuarios con rol *empresa* pueden crear y administrar solo sus viajes. Ven solo sus pasajeros y pueden recibir notificaciones.

- **Pasajero / Conductor**  
  Usuarios con rol *pasajero* o *conductor* son asignados a viajes. Reciben notificaciones de creación o actualización del viaje que les corresponde.

---

## 2. Agregar usuarios

1. **Empresa**
   - Ir a **Usuarios → Añadir nuevo** en el panel de administración de WordPress.
   - Asignar rol: **empresa**.
   - Guardar usuario.

2. **Pasajero**
   - Ir a **Usuarios → Añadir nuevo**.
   - Asignar rol: **pasajero**.
   - En el perfil del pasajero, asignar la **empresa** correspondiente usando el campo `empresa_id`.

3. **Conductor**
   - Ir a **Usuarios → Añadir nuevo**.
   - Asignar rol: **conductor**.
   - Guardar usuario.

> Nota: Los pasajeros y conductores deben estar asociados a una empresa para que puedan ser asignados a los viajes correspondientes.

---

## 3. Crear un viaje

1. Ir a **Viajes → Añadir nuevo**.
2. Completar los campos:
   - **Empresa**: seleccionar la empresa responsable del viaje.
   - **Pasajero**: seleccionar el pasajero correspondiente.
   - **Conductor**: seleccionar el conductor asignado.
   - **Estado**: por ejemplo, *pendiente*, *confirmado*, *finalizado*.
   - **Fecha y hora del viaje**.
   - **Comentarios adicionales** (opcional).

3. Publicar el viaje.
   - Solo al **publicar** se envían notificaciones a los involucrados.
   - Guardar como borrador no dispara emails.

---

## 4. Actualizar un viaje

1. Abrir el viaje desde **Viajes → Todos los viajes**.
2. Modificar cualquier campo (hora, fecha, estado, comentarios, pasajero, conductor).
3. Actualizar el viaje.
   - Si el viaje ya estaba publicado, se enviará un email de actualización a los involucrados.

---

## 5. Notificaciones por email

Los viajes generan notificaciones automáticas:

- **Al crear un viaje:**  
  Administrador, empresa, pasajero y conductor reciben un correo indicando que se creó un nuevo viaje.

- **Al actualizar un viaje publicado:**  
  Administrador, empresa, pasajero y conductor reciben un correo indicando qué viaje fue actualizado.

> Por ahora no se envían recordatorios antes del viaje, pero esto está previsto para futuras versiones.

---

## 6. Ajustes del sistema

Ir a **Viajes → Ajustes** para configurar:

- **Google Maps API Key**  
  Necesaria para mostrar mapas en la web o app.

- **Adicional viaje corto ($)**  
  Importe adicional aplicado a viajes de corta distancia.

- **Costo fijo por viaje ($)**  
  Valor fijo que se suma a todos los viajes.

> Todos los cambios deben guardarse con el botón **Guardar ajustes**.

---

## 7. Flujo resumido

```text
Crear usuario → Crear viaje → Publicar viaje → Email a todos los involucrados
Actualizar viaje publicado → Email de actualización a todos los involucrados
