// Espera a que Google Maps esté COMPLETAMENTE cargado (carga asíncrona)
let qvMapaIniciado = false;

function qvMapaListo() {
    return typeof google !== "undefined" && google.maps && google.maps.LatLngBounds && google.maps.Map;
}

function qvIniciarMapa() {
    if (qvMapaIniciado) return false;
    if (!qvMapaListo()) return false;
    qvMapaIniciado = true;
    qvTrazoMapa();
    return true;
}

// Callback que Google Maps invoca cuando la API terminó de inicializar (loading=async&callback=qvGmapsCallback)
window.qvGmapsCallback = qvIniciarMapa;

document.addEventListener("DOMContentLoaded", function() {
    qvIniciarMapa();
    // Respaldo por si el callback asíncrono aún no se disparó
    let intentos = 0;
    const intervalo = setInterval(function(){
        if (qvIniciarMapa() || intentos++ > 30) {
            clearInterval(intervalo);
        }
    }, 200);
});

// Trazado del mapa (se ejecuta cuando Google Maps está disponible)
function qvTrazoMapa() {
    const mapContainer = document.getElementById("qvChipMap");
    if (!mapContainer) return;

    // Leer coordenadas desde atributos del div
    const origenLat = parseFloat(mapContainer.dataset.origenLat);
    const origenLng = parseFloat(mapContainer.dataset.origenLng);
    const destinoLat = parseFloat(mapContainer.dataset.destinoLat);
    const destinoLng = parseFloat(mapContainer.dataset.destinoLng);

    if (!origenLat || !origenLng || !destinoLat || !destinoLng) {
        console.warn("Faltan coordenadas para mostrar el mapa del viaje.");
        return;
    }

    // Inicializar el mapa centrado entre ambos puntos
    const bounds = new google.maps.LatLngBounds();
    const map = new google.maps.Map(mapContainer, {
        zoom: 8,
        mapTypeId: "roadmap"
    });

    const origenPos = new google.maps.LatLng(origenLat, origenLng);
    const destinoPos = new google.maps.LatLng(destinoLat, destinoLng);

    const markerOrigen = new google.maps.Marker({
        position: origenPos,
        map,
        label: "A",
        title: "Origen"
    });

    const markerDestino = new google.maps.Marker({
        position: destinoPos,
        map,
        label: "B",
        title: "Destino"
    });

    bounds.extend(origenPos);
    bounds.extend(destinoPos);
    map.fitBounds(bounds);

    // Trazar la ruta entre origen y destino
    const directionsService = new google.maps.DirectionsService();
    const directionsRenderer = new google.maps.DirectionsRenderer({
        map,
        suppressMarkers: true,
        polylineOptions: { strokeColor: "#007bff", strokeWeight: 4 }
    });

    directionsService.route(
        {
            origin: origenPos,
            destination: destinoPos,
            travelMode: google.maps.TravelMode.DRIVING
        },
        (result, status) => {
            if (status === "OK") {
                directionsRenderer.setDirections(result);
            } else {
                console.error("Error al trazar ruta:", status);
            }
        }
    );
}

