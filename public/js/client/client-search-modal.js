class ClientSearchModal {
    constructor(apiUrl = "/api/clients") {
        this.apiUrl = apiUrl;
        this.tableSelector = "#modalClientTable";
        this.resultsSelector = "#modalClientResults";
        this.dataTableInstance = null;
    }

    async loadClients() {
        const tbody = document.querySelector(this.resultsSelector);
        tbody.innerHTML = "";
        document.querySelector("#modalLoading").style.display = "block";

        try {
            const response = await fetch(this.apiUrl);
            const data = await response.json();

            data.forEach((client) => {
                const row = `
                    <tr>
                        <td>${client.document}</td>
                        <td>${client.full_name}</td>
                        <td>${client.phone ?? ""}</td>
                        <td>${client.address ?? ""}</td>
                        <td>
                            <button class="btn btn-primary btn-select-client" data-client='${JSON.stringify(
                                client
                            )}'>
                                Seleccionar
                            </button>
                        </td>
                    </tr>
                `;
                tbody.insertAdjacentHTML("beforeend", row);
            });

            if (this.dataTableInstance) {
                this.dataTableInstance.destroy();
            }

            const initializer = new DataTableInitializer(this.tableSelector, {
                searching: true,
                paging: true,
            });
            initializer.initialize();

            this.dataTableInstance = $(this.tableSelector).DataTable();
        } catch (error) {
            console.error("Error cargando clientes:", error);
        } finally {
            document.querySelector("#modalLoading").style.display = "none";
        }
    }

    onSelect(callback) {
        document.addEventListener("click", (e) => {
            if (e.target.classList.contains("btn-select-client")) {
                const client = JSON.parse(e.target.getAttribute("data-client"));
                callback(client);
            }
        });
    }
}
