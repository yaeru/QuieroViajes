jQuery(function($){

	// Inicialización del sistema al cargar la página
	qvEsperarMapasAdmin();

	// Registrar listeners globales para cambios en inputs clave
	["qv_origen", "qv_destino", "qv_importe_km"].forEach(id => {
		const el = document.getElementById(id);
		if (el) el.addEventListener("change", calcularResumen);
	});

	// TARIFA PLANA: al activarla se deshabilita el importe por km
	const tarifaPlanaCheck = document.getElementById("qv_tarifa_plana");
	const tarifaPlanaMonto = document.getElementById("qv_tarifa_plana_monto");
	if (tarifaPlanaCheck) {
		const actualizarEstadoTarifaPlana = function(){
			const activa = tarifaPlanaCheck.checked;
			const importeKmEl = document.getElementById("qv_importe_km");
			if (importeKmEl) importeKmEl.disabled = activa;
			if (tarifaPlanaMonto) tarifaPlanaMonto.disabled = !activa;
		};
		tarifaPlanaCheck.addEventListener("change", function(){
			actualizarEstadoTarifaPlana();
			calcularResumen();
		});
		if (tarifaPlanaMonto) tarifaPlanaMonto.addEventListener("input", calcularResumen);
		// Aplicar el estado guardado al cargar (p. ej. al reabrir un viaje ya tildado)
		actualizarEstadoTarifaPlana();
	}

	// Escuchar cambios dinámicos en la tabla de gastos extra
	document.body.addEventListener("input", e => {
		if (e.target.name && e.target.name.includes("gastos_extra")) {
			calcularResumen();
		}
	});

	// GASTOS EXTRA: añadir y eliminar filas (delegación en document, funciona siempre)
	$(document).on('click', '#add-gasto-extra', function(){
		const $tbody = $('#qvGastosExtraTable tbody');
		if (!$tbody.length) return;
		const index = $tbody.find('tr').length;
		$tbody.append(
			'<tr>' +
			'<td><input type="text" name="gastos_extra[' + index + '][descripcion]" value="" /></td>' +
			'<td><input type="number" step="0.01" name="gastos_extra[' + index + '][importe]" value="" /></td>' +
			'<td><button type="button" class="remove-row">🗑️</button></td>' +
			'</tr>'
		);
		calcularResumen();
	});

	$(document).on('click', '.remove-row', function(){
		$(this).closest('tr').remove();
		// Renumerar índices para que el guardado quede limpio y sin colisiones
		$('#qvGastosExtraTable tbody tr').each(function(i){
			$(this).find('input').attr('name', function(){
				return this.name.replace(/gastos_extra\[\d+\]/, 'gastos_extra[' + i + ']');
			});
		});
		calcularResumen();
	});

	// ---------------------------------------------------
	// CAMBIO DINÁMICO DE EMPRESA (Precios + Pasajeros)
	// ---------------------------------------------------
	
	// Guardamos el importe con el que abrió el formulario (el default de ajustes)
	const $importeInput = $('#qv_importe_km');
	const importeInicial = $importeInput.val();

	$('#qv_empresa').on('change', function(){
		const empresaID = $(this).val();

		// 1. MODIFICACIÓN: Actualizar el importe por KM SOLO si la empresa tiene uno propio
		const optionSeleccionada = $(this).find('option:selected');
		const nuevoImporte = optionSeleccionada.attr('data-importe-km');

		// Si la empresa tiene un importe personalizado cargado (no está vacío ni es undefined)
		if (nuevoImporte !== undefined && nuevoImporte !== '') {
			$importeInput.val(nuevoImporte);
		} else {
			// Si la empresa NO tiene importe personalizado, restauramos el valor inicial de ajustes
			$importeInput.val(importeInicial);
		}
		
		// Actualizar el texto del resumen lateral con el valor que quedó definido
		const valorActual = $importeInput.val();
		const $importeDisplay = $('#qv-importe-km-display');
		if ($importeDisplay.length && valorActual) {
			const numImporte = parseFloat(valorActual.replace(',', '.'));
			$importeDisplay.text(isNaN(numImporte) ? valorActual : numImporte.toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
		}

		// Disparar el cálculo completo del viaje
		calcularResumen();

		// 2. Filtrado dinámico de pasajeros (AJAX)
		const $select = $('select[name="qv_pasajero"]');

		if (!empresaID) {
			$select.empty().append('<option value="">-- Seleccionar pasajero --</option>').prop('disabled', true);
			return;
		}

		$.post(qvAjax.ajaxurl, {
			action: 'qv_filtrar_pasajeros',
			nonce: qvAjax.nonce,
			empresa_id: empresaID
		}, function(response){
			if (!response.success) return;

			const pasajeros = response.data;

			$select.empty();
			$select.append('<option value="">-- Seleccionar pasajero --</option>');

			pasajeros.forEach(p => {
				$select.append(`<option value="${p.id}">${p.name}</option>`);
			});

			$select.prop('disabled', false);
		});
	});

});

// ---------------------------------------------------
// FUNCIONES GLOBALES (Mantienen Vanilla JS independiente)
// ---------------------------------------------------

// Espera a que Google Maps esté disponible y recién ahí inicializa autocompletado y cálculo
function qvIniciarMapasAdmin() {
	if (typeof google !== "undefined" && google.maps && google.maps.places) {
		initAutocomplete();
		calcularResumen(); // Calcular al cargar si ya hay datos previos
		return true;
	}
	return false;
}

function qvEsperarMapasAdmin() {
	if (qvIniciarMapasAdmin()) return;
	// Reintentar hasta que la API asíncrona termine de cargar
	setTimeout(qvEsperarMapasAdmin, 150);
}

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

	// Recalcular si cambia el importe por km manualmente (Admin editando)
	const importeKmInput = document.getElementById("qv_importe_km");
	if (importeKmInput) {
		importeKmInput.addEventListener("input", calcularResumen);
	}
}

function calcularResumen() {
	// TARIFA PLANA: importe fijo, ignora distancia e importe por km
	const tarifaPlanaCheck = document.getElementById("qv_tarifa_plana");
	const tarifaPlanaMonto = document.getElementById("qv_tarifa_plana_monto");

	if (tarifaPlanaCheck && tarifaPlanaCheck.checked) {
		const monto = parseFloat(String(tarifaPlanaMonto ? tarifaPlanaMonto.value : '').replace(',', '.'));
		const montoValido = !isNaN(monto) && monto > 0;

		/* Gastos extra (siguen funcionando de forma normal) */
		let gastosExtras = 0;
		document.querySelectorAll('#qvGastosExtraTable input[name*="[importe]"]').forEach(input => {
			const valor = parseFloat(String(input.value).replace(',', '.'));
			if (!isNaN(valor)) gastosExtras += valor;
		});
		gastosExtras = Math.ceil(gastosExtras);

		const base = montoValido ? monto : 0;
		const totalGeneral = Math.ceil(base + gastosExtras);

		/* Visuales */
		const totalContainer = document.querySelector(".qv-resumen-total");
		if (totalContainer) totalContainer.innerHTML = `<strong>Total:</strong> $${totalGeneral.toLocaleString('es-AR')}`;

		const importeEstimadoSpan = document.getElementById("qv-importe");
		if (importeEstimadoSpan) importeEstimadoSpan.textContent = montoValido ? monto.toLocaleString('es-AR') : '0';

		const adicionalElemento = document.getElementById("qv-adicional");
		if (adicionalElemento) adicionalElemento.style.display = "none";

		const distanciaSpan = document.getElementById("qv-distancia");
		if (distanciaSpan) distanciaSpan.textContent = "Tarifa plana";

		/* Hidden inputs */
		const importeInputHidden = document.getElementById("qv_importe_input");
		const totalGeneralInput = document.querySelector('input[name="qv_total_general"]');
		if (importeInputHidden) importeInputHidden.value = base;
		if (totalGeneralInput) totalGeneralInput.value = totalGeneral;
		return;
	}

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

			/* Referencias visuales en el DOM */
			const distanciaSpan = document.getElementById("qv-distancia");
			const importeKmDisplay = document.getElementById("qv-importe-km-display");
			const importeEstimadoSpan = document.getElementById("qv-importe");
			const totalContainer = document.querySelector(".qv-resumen-total");
			const adicionalElemento = document.getElementById("qv-adicional");
			const adicionalValorSpan = document.getElementById("qv-adicional-valor");

			/* Actualizar visuales */
			if (distanciaSpan) distanciaSpan.textContent = distanciaTexto;
			
			// Cambiado a 2 decimales fijos por si hay centavos en el precio del KM (ej: $150.50)
			if (importeKmDisplay) {
				importeKmDisplay.textContent = importeKm.toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
			}

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

			/* Actualizar hidden inputs para guardar en la base de datos */
			const distanciaInput = document.getElementById("qv_distancia_input");
			const importeInputHidden = document.getElementById("qv_importe_input");
			const totalGeneralInput = document.querySelector('input[name="qv_total_general"]');
			if (distanciaInput) distanciaInput.value = distanciaKm.toFixed(2);
			if (importeInputHidden) importeInputHidden.value = importeBase;
			/* Fijar el total general para que se guarde de una al publicar/actualizar */
			if (totalGeneralInput) totalGeneralInput.value = totalGeneral;

		} else {
			console.error("Error en DistanceMatrix:", status);
		}
	});
}