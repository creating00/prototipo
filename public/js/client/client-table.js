class ClientTable {
    constructor(selector = "#tableClients") {
        this.selector = selector;
        this.dataTableInstance = null;
    }

    renderRows(clients) {
        const template = document.querySelector("#client-row-template");
        const fragment = document.createDocumentFragment();

        clients.forEach((c) => {
            const tr = template.content.cloneNode(true);

            tr.querySelector(".col-doc").textContent = this.escape(c.document);
            tr.querySelector(".col-name").textContent = this.escape(
                c.full_name
            );
            tr.querySelector(".col-phone").textContent = this.escape(
                c.phone ?? ""
            );
            tr.querySelector(".col-address").textContent = this.escape(
                c.address ?? ""
            );

            tr.querySelector(".col-actions").innerHTML = `
                <a href="/admin/client/${c.id}/edit" class="btn btn-sm btn-warning" title="Editar">
                    <i class="fas fa-edit"></i>
                </a>

                <button class="btn btn-sm btn-danger"
                        data-id="${c.id}"
                        data-action="delete-client"
                        title="Eliminar">
                    <i class="fas fa-trash"></i>
                </button>
            `;

            fragment.appendChild(tr);
        });

        return fragment;
    }

    updateTable(clients) {
        const tbody = document.querySelector(`${this.selector} tbody`);

        if (clients.length) {
            tbody.innerHTML = "";
            tbody.appendChild(this.renderRows(clients));
        } else {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center">No hay clientes</td>
                </tr>
            `;
        }

        if (this.dataTableInstance) {
            this.dataTableInstance.destroy();
        }

        const initializer = new DataTableInitializer(this.selector, {
            order: [[1, "asc"]], // Orden por nombre
        });
        initializer.initialize();

        this.dataTableInstance = $(this.selector).DataTable();
    }

    escape(value) {
        if (value == null) return "";
        return String(value)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
}
