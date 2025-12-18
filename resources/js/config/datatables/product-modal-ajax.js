/**
 * Función AJAX para cargar productos filtrados por sucursal
 * @param {Object} data - Datos de DataTables
 * @param {Function} callback - Callback para devolver datos
 * @param {Object} settings - Configuración de DataTables
 * @returns {Object} Objeto con método abort para cancelar la solicitud
 */
export function productModalAjax(data, callback, settings) {
    // Obtener el branch_id actual considerando ambos formularios
    let branchId = null;

    // Detectar qué tipo de formulario estamos usando
    const branchRecipientSelect = document.querySelector(
        'select[name="branch_recipient_id"]'
    );
    const hiddenBranchInput = document.querySelector('input[name="branch_id"]');
    const branchSelect = document.querySelector('select[name="branch_id"]');

    // Formulario de sucursal (branch-to-branch)
    if (branchRecipientSelect) {
        // Los productos deben venir de la sucursal DESTINATARIA
        branchId = branchRecipientSelect.value;
        //console.log("Modal AJAX - Formulario sucursal, usando branch_recipient_id:", branchId);
    }
    // Formulario de cliente
    else if (branchSelect) {
        // Los productos deben venir de la sucursal ORIGEN (seleccionada)
        branchId = branchSelect.value;
        // console.log(
        //     "Modal AJAX - Formulario cliente, usando branch_id:",
        //     branchId
        // );
    }

    // Si no hay branchId, devolver array vacío inmediatamente
    if (!branchId || branchId === "") {
        // console.warn(
        //     "Modal AJAX - Branch ID not found or empty, returning empty product list"
        // );
        callback({ data: [] });
        return null;
    }

    let url = "/api/inventory/list";
    url += `?branch_id=${branchId}`;

    //console.log("Modal AJAX - Fetching products from URL:", url);

    // Crear un controlador para poder abortar la solicitud
    const controller = new AbortController();
    const signal = controller.signal;

    // Realizar la solicitud
    fetch(url, { signal })
        .then((response) => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then((json) => {
            // Verificar si la respuesta es un array
            if (Array.isArray(json)) {
                //console.log("Modal AJAX - Products loaded:", json.length);
                callback({ data: json });
            } else {
                console.error(
                    "Modal AJAX - Invalid response format, expected array:",
                    json
                );
                callback({ data: [] });
            }
        })
        .catch((error) => {
            if (error.name === "AbortError") {
                //console.log("Modal AJAX - Request aborted");
            } else {
                //console.error("Modal AJAX - Error loading products:", error);
                callback({ data: [] });
            }
        });

    // Devolver un objeto con método abort para DataTables
    return {
        abort: function () {
            controller.abort();
            console.log("Modal AJAX - DataTables abort called");
        },
    };
}

/**
 * Obtener el branch_id actual para otras funciones
 * @returns {string|null} El branch_id actual
 */
export function getCurrentBranchId() {
    const branchRecipientSelect = document.querySelector(
        'select[name="branch_recipient_id"]'
    );
    const branchSelect = document.querySelector('select[name="branch_id"]');

    if (branchRecipientSelect) {
        return branchRecipientSelect.value;
    } else if (branchSelect) {
        return branchSelect.value;
    }

    return null;
}
