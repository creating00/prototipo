class BranchTable {
    constructor(selector = "#tableBranches") {
        this.selector = selector;
        this.dataTableInstance = null;
    }

    renderRows(branches) {
        const template = document.querySelector("#branch-row-template");
        const fragment = document.createDocumentFragment();

        branches.forEach((b) => {
            const tr = template.content.cloneNode(true);

            tr.querySelector(".col-id").textContent = b.id;
            tr.querySelector(".col-name").textContent = b.name;
            tr.querySelector(".col-address").textContent = b.address ?? "";

            tr.querySelector(".col-actions").innerHTML = `
                <a href="/admin/branch/${b.id}/edit" class="btn btn-sm btn-warning" title="Editar">
                    <i class="fas fa-edit"></i>
                </a>
                <button class="btn btn-sm btn-danger"
                        title="Eliminar"
                        data-id="${b.id}"
                        data-action="delete-branch">
                    <i class="fas fa-trash"></i>
                </button>
            `;

            fragment.appendChild(tr);
        });

        return fragment;
    }

    updateTable(branches) {
        const tbody = document.querySelector(`${this.selector} tbody`);

        tbody.innerHTML = "";
        tbody.appendChild(this.renderRows(branches));

        if (this.dataTableInstance) {
            this.dataTableInstance.destroy();
        }

        const initializer = new DataTableInitializer(this.selector, {
            order: [[0, "asc"]],
        });

        initializer.initialize();
        this.dataTableInstance = $(this.selector).DataTable();
    }
}
