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
- fix, Chequear que solo los usuarios puedan ver sus propios viajes
- fix, Pasajero Login redirija al listado de viajes
- Bug, importe no queda guardado de una
- bug, email subjets on update
- update, enviar email para admin cuando empresa pide viaje
- bug, distancia solo se guarda cuando hay $ km
- fix, empresa solo no puede editar estado viaje
	
### Update
- Update, email de notificaciones para emails de admin desde Viajes > Ajustes
- Update, Rol Empresa puede ver editar sus propios viajes, no asi los creados por el Admin
- Update, Metabox Gastos extra solo visible por Admin
- Update, Campo Importe x km solo visible por Aadmin
- Update, Campo conductor solo visible por Admin
- Update, Mis viajes solo para rol conductor
- Bug, Mis viajes, Importe total columna oculta
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
- Colores personalisados
- Codigo de seguridad
- Ver la opción de Abonar con Sistema Pago Link o Mercado Pago ( puede ser en una próxima etapa)
- Funciones para Pasajero

### emails
Corven Sacif
corven@corven.com.ar

Grupo Disal S.A.
grupodisal@grupodisal.com.ar

Hernan Chofer
empresaaltonivel@gmail.com