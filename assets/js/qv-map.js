document.addEventListener("DOMContentLoaded", function() {
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

    if (typeof google === "undefined" || !google.maps) {
        console.error("Google Maps API no está cargada.");
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
});
