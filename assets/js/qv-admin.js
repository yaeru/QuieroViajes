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
    }
    /* recalcular si cambia importe por km */
    var importeKmInput = document.getElementById("qv_importe_km");
    if (importeKmInput) {
        importeKmInput.addEventListener("input", calcularResumen);
    }
}

function calcularResumen() {
    var origen = document.getElementById("qv_origen")?.value;
    var destino = document.getElementById("qv_destino")?.value;
    var importeKm = document.getElementById("qv_importe_km")?.value;

    if (!origen || !destino || !importeKm) return;

    var service = new google.maps.DistanceMatrixService();
    service.getDistanceMatrix(
    {
        origins: [origen],
        destinations: [destino],
        travelMode: google.maps.TravelMode.DRIVING,
        unitSystem: google.maps.UnitSystem.METRIC
    },
    function (response, status) {
        if (status === "OK") {
            var distanciaTexto = response.rows[0].elements[0].distance.text;
            var distanciaKm = response.rows[0].elements[0].distance.value / 1000;
            var importeTotal = (distanciaKm * parseFloat(importeKm)).toFixed(2);

            document.getElementById("qv-distancia").textContent = distanciaTexto;
            document.getElementById("qv-importe").textContent = importeTotal;

                /* actualiza también los inputs hidden */
            document.getElementById("qv_distancia_input").value = distanciaKm.toFixed(2);
            document.getElementById("qv_importe_input").value = importeTotal;
        } else {
            console.error("Error en DistanceMatrix:", status);
        }
    }
    );
}

/* Ejecutar al cargar el admin y cada vez que cambien origen/destino */
document.addEventListener("DOMContentLoaded", function () {
    if (typeof google !== "undefined" && google.maps) {
        calcularResumen();

        /* recalcular al cambiar inputs */
        let origen = document.getElementById("qv_origen");
        let destino = document.getElementById("qv_destino");
        let importeKm = document.getElementById("qv_importe_km");

        [origen, destino, importeKm].forEach(function (el) {
            if (el) el.addEventListener("change", calcularResumen);
        });
    }
});
