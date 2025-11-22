// order-client.js
class OrderClient {
    constructor(orderForm) {
        this.orderForm = orderForm;
        this.selectedClient = null;
        this.searchTimeout = null;
    }

    // Configurar el modal de búsqueda de clientes
    setupClientSearchModal() {
        const modalSearch = document.getElementById("modalClientSearch");

        if (modalSearch) {
            modalSearch.addEventListener("input", (e) => {
                clearTimeout(this.searchTimeout);
                this.searchTimeout = setTimeout(() => {
                    this.searchClients(e.target.value);
                }, 500);
            });

            $("#clientSearchModal").on("show.bs.modal", () => {
                this.searchClients("");
            });
        }

        // Inicializar estados de botones
        this.updateButtonStates();
    }

    // Buscar clientes
    async searchClients(query) {
        const resultsContainer = document.getElementById("modalClientResults");
        const loading = document.getElementById("modalLoading");

        if (!resultsContainer || !loading) return;

        loading.style.display = "block";
        resultsContainer.innerHTML = "";

        try {
            const url = query
                ? `/api/clients/search?search=${encodeURIComponent(query)}`
                : "/api/clients/search";
            const res = await axios.get(url);
            const clients = res.data;

            if (clients.length > 0) {
                const html = clients
                    .map(
                        (client) => `
                    <tr>
                        <td>${client.document}</td>
                        <td>${client.full_name}</td>
                        <td>${client.phone || "N/A"}</td>
                        <td>${client.address || "N/A"}</td>
                        <td>
                            <button type="button" class="btn btn-success btn-sm select-client-btn" 
                                    data-client-id="${client.id}"
                                    data-client-document="${client.document}"
                                    data-client-name="${client.full_name.replace(
                                        /"/g,
                                        "&quot;"
                                    )}">
                                <i class="fas fa-check"></i> Seleccionar
                            </button>
                        </td>
                    </tr>
                `
                    )
                    .join("");

                resultsContainer.innerHTML = html;
                this.setupClientSelectionButtons();
            } else {
                resultsContainer.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center text-muted">
                            No se encontraron clientes
                        </td>
                    </tr>
                `;
            }
        } catch (error) {
            console.error("Error searching clients:", error);
            resultsContainer.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center text-danger">
                        Error al cargar los clientes
                    </td>
                </tr>
            `;
        } finally {
            loading.style.display = "none";
        }
    }

    // Configurar botones de selección
    setupClientSelectionButtons() {
        const buttons = document.querySelectorAll(".select-client-btn");
        buttons.forEach((button) => {
            button.addEventListener("click", (e) => {
                const clientId = e.target
                    .closest("button")
                    .getAttribute("data-client-id");
                const clientDocument = e.target
                    .closest("button")
                    .getAttribute("data-client-document");
                const clientName = e.target
                    .closest("button")
                    .getAttribute("data-client-name");

                this.selectClientFromModal(
                    clientId,
                    clientDocument,
                    clientName
                );
            });
        });
    }

    // Seleccionar cliente desde el modal
    selectClientFromModal(id, doc, fullName) {
        this.selectedClient = { id, document: doc, fullName };

        const clientIdInput = document.getElementById("client_id");
        const clientNameDisplay = document.getElementById("clientNameDisplay");
        const clientDocumentDisplay = document.getElementById(
            "clientDocumentDisplay"
        );
        const selectedClientInfo =
            document.getElementById("selectedClientInfo");
        const noClientSelected = document.getElementById("noClientSelected");
        const newClientForm = document.getElementById("new_client_form");

        if (clientIdInput) clientIdInput.value = id;
        if (clientNameDisplay) clientNameDisplay.textContent = fullName;
        if (clientDocumentDisplay) clientDocumentDisplay.textContent = doc;
        if (selectedClientInfo) selectedClientInfo.style.display = "block";
        if (noClientSelected) noClientSelected.style.display = "none";
        if (newClientForm) newClientForm.style.display = "none";

        this.updateButtonStates();
        this.orderForm.handler.updateSubmitButtonState();
        $("#clientSearchModal").modal("hide");
    }

    // Mostrar formulario de nuevo cliente
    showNewClientForm() {
        const newClientForm = document.getElementById("new_client_form");
        if (newClientForm) newClientForm.style.display = "block";

        this.selectedClient = null;
        this.updateButtonStates();

        // Limpiar selección anterior
        const clientIdInput = document.getElementById("client_id");
        const selectedClientInfo =
            document.getElementById("selectedClientInfo");
        const noClientSelected = document.getElementById("noClientSelected");

        if (clientIdInput) clientIdInput.value = "";
        if (selectedClientInfo) selectedClientInfo.style.display = "none";
        if (noClientSelected) noClientSelected.style.display = "block";
    }

    // Ocultar formulario de nuevo cliente
    hideNewClientForm() {
        const newClientForm = document.getElementById("new_client_form");
        if (newClientForm) newClientForm.style.display = "none";
        this.updateButtonStates();
    }

    // Limpiar selección de cliente
    clearClientSelection() {
        this.selectedClient = null;

        const clientIdInput = document.getElementById("client_id");
        const selectedClientInfo =
            document.getElementById("selectedClientInfo");
        const noClientSelected = document.getElementById("noClientSelected");
        const newClientForm = document.getElementById("new_client_form");

        if (clientIdInput) clientIdInput.value = "";
        if (selectedClientInfo) selectedClientInfo.style.display = "none";
        if (noClientSelected) noClientSelected.style.display = "block";
        if (newClientForm) newClientForm.style.display = "none";

        this.updateButtonStates();
        this.orderForm.handler.updateSubmitButtonState();
    }

    // Actualizar estados de los botones
    updateButtonStates() {
        const searchClientBtn = document.getElementById("searchClientBtn");
        const newClientBtn = document.getElementById("newClientBtn");
        const newClientForm = document.getElementById("new_client_form");

        const isClientSelected = this.selectedClient !== null;
        const isNewClientFormVisible =
            newClientForm && newClientForm.style.display !== "none";

        // Solo deshabilitar estos dos botones
        if (searchClientBtn) {
            searchClientBtn.disabled =
                isClientSelected || isNewClientFormVisible;
        }

        if (newClientBtn) {
            newClientBtn.disabled = isClientSelected || isNewClientFormVisible;
        }
    }

    // Validar datos del cliente
    validateClient() {
        if (this.selectedClient) return true;

        const clientDocument = document.getElementById("client_document");
        const clientFullName = document.getElementById("client_full_name");

        if (!clientDocument || !clientDocument.value) {
            alert("Debe completar el documento del cliente");
            return false;
        }

        if (!clientFullName || !clientFullName.value) {
            alert("Debe completar el nombre del cliente");
            return false;
        }

        return true;
    }

    // Obtener datos del cliente para el formulario
    getClientData() {
        if (this.selectedClient) {
            return {
                document: this.selectedClient.document,
                full_name: this.selectedClient.fullName,
            };
        }

        const clientDocument = document.getElementById("client_document");
        const clientFullName = document.getElementById("client_full_name");
        const clientPhone = document.getElementById("client_phone");
        const clientAddress = document.getElementById("client_address");

        return {
            document: clientDocument ? clientDocument.value : "",
            full_name: clientFullName ? clientFullName.value : "",
            phone: clientPhone ? clientPhone.value : "",
            address: clientAddress ? clientAddress.value : "",
        };
    }
}
