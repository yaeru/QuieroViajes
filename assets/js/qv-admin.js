document.addEventListener("DOMContentLoaded", function() {
    if (typeof google !== "undefined" && google.maps && google.maps.places) {
        initAutocomplete();
        calcularResumen(); /* calcular al cargar si ya hay datos*/
    } else {
        console.error("Google Maps API no cargó correctamente.");
    }
});

function initAutocomplete() {
    var origenInput = document.getElementById("qv_origen");
    var destinoInput = document.getElementById("qv_destino");
    if (origenInput) {
        var autocompleteOrigen = new google.maps.places.Autocomplete(origenInput);
        autocompleteOrigen.addListener("place_changed", function() {
            var place = autocompleteOrigen.getPlace();
            if (place.geometry) {
                document.getElementById("qv_origen_lat").value = place.geometry.location.lat();
                document.getElementById("qv_origen_lng").value = place.geometry.location.lng();
                document.getElementById("qv_origen").value = place.formatted_address; /* dirección completa*/
                calcularResumen();
            }
        });
    }
    if (destinoInput) {
        var autocompleteDestino = new google.maps.places.Autocomplete(destinoInput);
        autocompleteDestino.addListener("place_changed", function() {
            var place = autocompleteDestino.getPlace();
            if (place.geometry) {
                document.getElementById("qv_destino_lat").value = place.geometry.location.lat();
                document.getElementById("qv_destino_lng").value = place.geometry.location.lng();
                document.getElementById("qv_destino").value = place.formatted_address; /* dirección completa*/
                calcularResumen();
            }
        });
    } /* recalcular si cambia importe por km*/
    var importeKmInput = document.getElementById("qv_importe_km");
    if (importeKmInput) {
        importeKmInput.addEventListener("input", calcularResumen);
    }
}

function calcularResumen() {
    var origenLat = parseFloat(document.getElementById("qv_origen_lat")?.value);
    var origenLng = parseFloat(document.getElementById("qv_origen_lng")?.value);
    var destinoLat = parseFloat(document.getElementById("qv_destino_lat")?.value);
    var destinoLng = parseFloat(document.getElementById("qv_destino_lng")?.value);
    var importeKm = parseFloat(document.getElementById("qv_importe_km")?.value);
    if (!origenLat || !origenLng || !destinoLat || !destinoLng || isNaN(importeKm)) {
        document.getElementById("qv-distancia").textContent = "-";
        document.getElementById("qv-importe").textContent = "-";
        return;
    }
    var service = new google.maps.DistanceMatrixService();
    service.getDistanceMatrix({
        origins: [new google.maps.LatLng(origenLat, origenLng)],
        destinations: [new google.maps.LatLng(destinoLat, destinoLng)],
        travelMode: google.maps.TravelMode.DRIVING,
        /* distancia por ruta en auto */
        unitSystem: google.maps.UnitSystem.METRIC
    }, function(response, status) {
        if (status === "OK") {
            var element = response.rows[0].elements[0];
            if (element.status === "OK") {
                var distanciaTexto = element.distance.text; /* ej: "12.3 km" */
                var distanciaKm = element.distance.value / 1000; /* metros → km */
                var importeTotal = (distanciaKm * importeKm).toFixed(2);
                document.getElementById("qv-distancia").textContent = distanciaTexto;
                document.getElementById("qv-importe").textContent = importeTotal;
            } else {
                console.error("DistanceMatrix element error:", element.status);
                document.getElementById("qv-distancia").textContent = "-";
                document.getElementById("qv-importe").textContent = "-";
            }
        } else {
            console.error("DistanceMatrix error:", status);
            document.getElementById("qv-distancia").textContent = "-";
            document.getElementById("qv-importe").textContent = "-";
        }
    });
}