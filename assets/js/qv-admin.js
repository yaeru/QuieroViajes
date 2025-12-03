document.addEventListener("DOMContentLoaded", function() {
	if (typeof google !== "undefined" && google.maps && google.maps.places) {
		initAutocomplete();
		calcularResumen(); // Calcular al cargar si ya hay datos
	} else {
		console.error("Google Maps API no cargó correctamente.");
	}
});

function initAutocomplete() {
	const origenInput = document.getElementById("qv_origen");
	const destinoInput = document.getElementById("qv_destino");

	if (origenInput) {
		const autocompleteOrigen = new google.maps.places.Autocomplete(origenInput);
		autocompleteOrigen.addListener("place_changed", function() {
			const place = autocompleteOrigen.getPlace();
			if (place.geometry) {
				document.getElementById("qv_origen_lat").value = place.geometry.location.lat();
				document.getElementById("qv_origen_lng").value = place.geometry.location.lng();
				document.getElementById("qv_origen").value = place.formatted_address;
				calcularResumen();
			}
		});
	}

	if (destinoInput) {
		const autocompleteDestino = new google.maps.places.Autocomplete(destinoInput);
		autocompleteDestino.addListener("place_changed", function() {
			const place = autocompleteDestino.getPlace();
			if (place.geometry) {
				document.getElementById("qv_destino_lat").value = place.geometry.location.lat();
				document.getElementById("qv_destino_lng").value = place.geometry.location.lng();
				document.getElementById("qv_destino").value = place.formatted_address;
				calcularResumen();
			}
		});
	}

	// recalcular si cambia el importe por km manualmente
	const importeKmInput = document.getElementById("qv_importe_km");
	if (importeKmInput) {
		importeKmInput.addEventListener("input", calcularResumen);
	}
}

// ---------------------------------------------------

function calcularResumen() {
	const origen = document.getElementById("qv_origen")?.value;
	const destino = document.getElementById("qv_destino")?.value;
	const importeKmInput = document.querySelector('input[name="qv_importe_km"]');

	if (!origen || !destino || !importeKmInput) return;

	const importeKm = parseFloat(String(importeKmInput.value).replace(',', '.'));
	if (isNaN(importeKm)) return;

	const service = new google.maps.DistanceMatrixService();
	service.getDistanceMatrix(
	{
		origins: [origen],
		destinations: [destino],
		travelMode: google.maps.TravelMode.DRIVING,
		unitSystem: google.maps.UnitSystem.METRIC
	},
	function (response, status) {
		if (status === "OK") {
			const element = response.rows[0].elements[0];
			if (!element || element.status !== "OK") {
				console.error("No se pudo calcular la distancia:", element && element.status);
				return;
			}

			const distanciaTexto = element.distance.text;
			const distanciaKm = element.distance.value / 1000;

			/* Adicional por viaje corto */
			let adicionalRaw = 0;
			const adicionalHidden = document.getElementById("_qv_adicional_aplicado");
			if (adicionalHidden && adicionalHidden.value !== "") {
				adicionalRaw = adicionalHidden.value;
			} else {
				const adicionalSpan = document.getElementById("qv-adicional-valor");
				if (adicionalSpan) adicionalRaw = adicionalSpan.textContent;
			}
			adicionalRaw = String(adicionalRaw).replace(/\./g, '').replace(',', '.').replace(/[^\d\.\-]/g,'');
			let adicionalNum = parseFloat(adicionalRaw);
			if (isNaN(adicionalNum)) adicionalNum = 0;

			/* Gastos extra */
			let gastosExtras = 0;
			document.querySelectorAll('#qvGastosExtraTable input[name*="[importe]"]').forEach(input => {
				const valor = parseFloat(String(input.value).replace(',', '.'));
				if (!isNaN(valor)) gastosExtras += valor;
			});
			gastosExtras = Math.ceil(gastosExtras);

				/* Cálculos (redondeo hacia arriba) */
				const importeBase = Math.ceil(distanciaKm * importeKm); // distancia × importe/km
				const adicionalAplicado = distanciaKm <= 10 ? Math.ceil(adicionalNum) : 0;
				const totalGeneral = Math.ceil(importeBase + gastosExtras + adicionalAplicado);

				/* Actualizar visuales */
				const distanciaSpan = document.getElementById("qv-distancia");
				const importeKmDisplay = document.getElementById("qv-importe-km-display");
				const importeEstimadoSpan = document.getElementById("qv-importe"); // Importe estimado (distancia * km)
				const totalContainer = document.querySelector(".qv-resumen-total"); // Total final (importe estimado + extras + adicional)
				const adicionalElemento = document.getElementById("qv-adicional");
				const adicionalValorSpan = document.getElementById("qv-adicional-valor");

				/* actualizar visuales */
				if (distanciaSpan) distanciaSpan.textContent = distanciaTexto;
				if (importeKmDisplay) importeKmDisplay.textContent = Math.ceil(importeKm).toLocaleString('es-AR');

				/* Importe estimado = SOLO distancia * importe/km (redondeado hacia arriba) */
				if (importeEstimadoSpan) {
					importeEstimadoSpan.textContent = importeBase.toLocaleString('es-AR');
				}

				/* Mostrar/ocultar adicional y su valor */
				if (adicionalElemento && adicionalValorSpan) {
					if (adicionalAplicado > 0) {
						adicionalElemento.style.display = "";
						adicionalValorSpan.textContent = adicionalAplicado.toLocaleString('es-AR');
					} else {
						adicionalElemento.style.display = "none";
					}
				}

				/* Total general (importe estimado + gastos extra + adicional) */
				if (totalContainer) {
					totalContainer.innerHTML = `<strong>Total:</strong> $${totalGeneral.toLocaleString('es-AR')}`;
				}

				/* Actualizar hidden inputs (si los usás para guardar) */
				const distanciaInput = document.getElementById("qv_distancia_input");
				const importeInputHidden = document.getElementById("qv_importe_input"); /* antes guardabas esto */
				if (distanciaInput) distanciaInput.value = distanciaKm.toFixed(2);
				if (importeInputHidden) importeInputHidden.value = importeBase; /* guardar importe estimado (solo distancia*km) */


			} else {
				console.error("Error en DistanceMatrix:", status);
			}
		}
		);
}

/* Escuchar cambios para recalcular siempre */
document.addEventListener("DOMContentLoaded", function () {
	if (typeof google !== "undefined" && google.maps) {
		calcularResumen();
		["qv_origen", "qv_destino", "qv_importe_km"].forEach(id => {
			const el = document.getElementById(id);
			if (el) el.addEventListener("change", calcularResumen);
		});

		/* También escuchar cambios en los gastos extra */
		document.body.addEventListener("input", e => {
			if (e.target.name && e.target.name.includes("gastos_extra")) {
				calcularResumen();
			}
		});
	}
});


/*---*/
jQuery(function($){

    // Filtrado dinámico de pasajeros según empresa seleccionada
    $('#qv_empresa').on('change', function(){

        const empresaID = $(this).val();

        $.post(qvAjax.ajaxurl, {
            action: 'qv_filtrar_pasajeros',
            nonce: qvAjax.nonce,
            empresa_id: empresaID
        }, function(response){

            if (!response.success) return;

            const pasajeros = response.data;
            const $select = $('select[name="qv_pasajero"]');

            $select.empty();
            $select.append('<option value="">-- Seleccionar pasajero --</option>');

            pasajeros.forEach(p => {
                $select.append(`<option value="${p.id}">${p.name}</option>`);
            });

        });

    });

});

