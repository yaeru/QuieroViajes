# Quiero Viajes
Administrador de viajes empresariales con Google Maps para Wordpress.

## Instucciones

### API Key
Paso 1, crear API en google cloud con las siguientes bibliotecas
- Places API
- Places API (New)
- Directions API
- Distance Matrix API
- Maps JavaScript API

Paso 2, cargar la API Key en menu Admin de Wordpress Viajes > Ajustes

## Roadmap

### Etapa 1
- Bug high, importe no queda guardado de una
- Bug high, se muestran pasajeros de otras empresas cuando soy admin
- fix high, Chequear que solo los usuarios puedan ver sus propios viajes


	
### Update
- Update, Ahora el Importe por KM es un valor general, que se puede guardar desde Menu Viajes > Submenu Ajustes
- Update, cuando una empresa crea un viaje, se envia un email a la casilla de Notificaciones
- Update, La casilla de Notificaciones se define desde Menu Viajes > Submenu Ajustes
- Update, Rol Empresa puede ver editar sus propios viajes, no asi los creados por el Admin
- Update, Al crear viaje el metabox Gastos extra solo visible por Admin
- Update, Al crear viaje el campo Importe x km solo editable por Admin
- Update, Al crear viaje el campo conductor solo visible por Admin
- Update, Al crear viaje el campo Estado solo puede editar el Admin
- Update, Menu Mis viajes solo se muestra al Conductor
- Bug, asuntos de email correctos
- Bug, Mis viajes para conductores, Importe total columna oculta
- Bug, distancia solo se guarda cuando hay $ km. Ahora que siempre hay un km no pasa mas
- Logo en el Iniciar sesion
- Pagina visible solo para logueados
- Foto perfiles
- Historial viaje para chofer
- Añadir importe al pasajero, Nota estimado
- Sin empresa asignada bug
- Un solo template con condicionales
- Instalar Google Maps en vez del actual,  ya que es un grupo muy reducido, el personal que va a utilizar el sistema. Estuve haciendo un recuento de los viajes, y entre las 3 empresas que me pedirían los traslados en esta página, no superan los 300 viajes mensuales
- Faltaría que al pasajero le aparezca el icono de whatsapp con el numero del chofer para que pueda hablar con é. (donde esta, cuanto le falta)
- Cuando pones "publicar," a veces carga los peajes en el resultado final, a veces lo carga, a
veces no lo carga ( Ver esto )
- Que el sistema detecte que es un viaje corto (10 o 15 km) y le agregue un adicional de $35.000 (que este valor se pueda modificar). Daría unos 45000 mil (10.000 + 35.000) que es un viaje de Merlo a Caba , por ejemplo.
- Intentar que el importe, desde la vista Chofer o Conductor, no se vea ningún importe, ya que hago una diferencia importante en el tema tarifario, (lo habíamos conversado).
- Si se puede ver alguna foto del auto, tipo App Uber, una fotito de baja calidad poder subir el administrador, asi le aparece al pasajero cuando le confirmar que auto va, (si es muy difícil, lo hacemos mas adelante ).
- Revisar: donde se está cargando el viaje, aparece "valor 1"
- Borrar campo Auto
- tabla de viajes
- exportar csv

### Etapa 2
- Login de frontend
- Funciones para Pasajero
-- solo frontend
-- Perfil
-- Dashboard
-- Pasajero Login redirija al listado de viajes
- Colores personalisados
- Codigo de seguridad
- Ver la opción de Abonar con Sistema Pago Link o Mercado Pago ( puede ser en una próxima etapa)


### emails
Corven Sacif
corven@corven.com.ar

Grupo Disal S.A.
grupodisal@grupodisal.com.ar

Hernan Chofer
empresaaltonivel@gmail.com