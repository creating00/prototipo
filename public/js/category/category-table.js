class CategoryTable {
    constructor(selector = "#tableCategories") {
        this.selector = selector;
        this.dataTableInstance = null;
    }

    renderRows(categories) {
        const template = document.querySelector("#category-row-template");
        const fragment = document.createDocumentFragment();

        categories.forEach((c) => {
            const tr = template.content.cloneNode(true);

            tr.querySelector(".col-id").textContent = c.id;
            tr.querySelector(".col-name").textContent = c.name;

            // Acciones con iconos
            tr.querySelector(".col-actions").innerHTML = `
                <a href="/admin/category/${c.id}/edit" class="btn btn-sm btn-warning" title="Editar">
                    <i class="fas fa-edit"></i>
                </a>
                <button class="btn btn-sm btn-danger" data-id="${c.id}" data-action="delete-category" title="Eliminar">
                    <i class="fas fa-trash"></i>
                </button>
            `;

            fragment.appendChild(tr);
        });

        return fragment;
    }

    updateTable(categories) {
        const tbody = document.querySelector(`${this.selector} tbody`);

        tbody.innerHTML = "";
        tbody.appendChild(this.renderRows(categories));

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
