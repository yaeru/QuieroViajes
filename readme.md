# Quiero Viajes
Admin de viajes empresariales con google maps para wordpress.

## Instucciones

### API Key
Activar API en google cloud con las siguientes bibliotecas
- Places API
- Places API (New)
- Directions API
- Distance Matrix API
- Maps JavaScript API

### Cargar API en Wordpress
API Key en wp-config.php, ejemplo define( 'QV_GOOGLE_MAPS_API_KEY', 'YOURAPIKEYHERE' );



## Listado de tareas

### Pendiente


3-Intentar que el importe, desde la vista Chofer o Conductor, no se vea ningún importe, ya que hago una diferencia importante en el tema tarifario, (lo habíamos conversado).

6-Que el sistema detecte que es un viaje corto (10 o 15 km) y le agregue un adicional de $35.000 (que este valor se pueda modificar). Daría unos 45000 mil (10.000 + 35.000) que es un viaje de Merlo a Caba , por ejemplo.

7-Si se puede ver alguna foto del auto, tipo App Uber, una fotito de baja calidad poder subir el administrador, asi le aparece al pasajero cuando le confirmar que auto va, (si es muy difícil, lo hacemos mas adelante ).

8-Estoy mareado en la parte de quien cierra el viaje el administrador?
luego le llega un mail al pasajero con los datos del viaje, y el importe final?
El pasajero debe aprobar esto?
Se puede modificar este valor final? (Por si surge alguna diferencia de valor, de lo que dice el pasajero y el administrador)

9-¿se puede poner un valor de translado fijo? Muchos viajes son fijos. calculo que el administrador puede poner un valor fijo

MAS ADELANTE
10-Ver  la opción de Abonar con Sistema Pago Link o Mercado Pago ( puede ser en
una próxima etapa)

### No entendi
4-Revisar: donde se está cargando el viaje, aparece "valor 1"

### Listo
1-Instalar Google Maps en vez del actual,  ya que es un grupo muy reducido, el personal que va a utilizar el sistema. Estuve haciendo un recuento de los viajes, y entre las 3 empresas que me pedirían los traslados en esta página, no superan los 300 viajes mensuales

2-Faltaría que al pasajero le aparezca el icono de whatsapp con el numero del chofer para que pueda hablar con é. (donde esta, cuanto le falta)

5-Cuando pones "publicar," a veces carga los peajes en el resultado final, a veces lo carga, a
veces no lo carga ( Ver esto )