class ProductTable {
    constructor(selector = "#tableProducts") {
        this.selector = selector;
        this.dataTableInstance = null;

        this.currencyFormatter = new Intl.NumberFormat("es-AR", {
            style: "currency",
            currency: "ARS",
            minimumFractionDigits: 2,
        });
    }

    renderRows(products) {
        const template = document.querySelector("#product-row-template");
        const fragment = document.createDocumentFragment();

        products.forEach((p) => {
            const tr = template.content.cloneNode(true);

            tr.querySelector(".col-img").innerHTML = `<img src="${
                p.image || "/images/placeholder.webp"
            }" style="width:48px;height:48px;object-fit:cover;border-radius:4px">`;
            tr.querySelector(".col-code").textContent = this.escape(p.code);
            tr.querySelector(".col-name").textContent = this.escape(p.name);
            tr.querySelector(".col-category").textContent = this.escape(
                p.category?.name ?? ""
            );
            tr.querySelector(".col-stock").textContent = Number(p.stock ?? 0);
            tr.querySelector(".col-branch").textContent = this.escape(
                p.branch?.name ?? ""
            );
            tr.querySelector(".col-price").textContent =
                p.sale_price != null
                    ? this.currencyFormatter.format(Number(p.sale_price))
                    : "—";
            tr.querySelector(".col-rating").textContent =
                p.average_rating != null
                    ? Number(p.average_rating).toFixed(1)
                    : "—";

            tr.querySelector(".col-actions").innerHTML = `
                <a href="/admin/product/${p.id}/edit" class="btn btn-sm btn-warning" title="Editar">
                    <i class="fas fa-edit"></i>
                </a>

                <button class="btn btn-sm btn-danger" data-id="${p.id}" data-action="delete-product" title="Eliminar">
                    <i class="fas fa-trash"></i>
                </button>
            `;

            fragment.appendChild(tr);
        });

        return fragment;
    }

    updateTable(products) {
        const tbody = document.querySelector(`${this.selector} tbody`);
        tbody.innerHTML = "";

        if (products.length) {
            tbody.appendChild(this.renderRows(products));
        }

        // Destruir la instancia anterior si existe
        if (this.dataTableInstance) {
            this.dataTableInstance.destroy();
            this.dataTableInstance = null;
        }

        // Inicializar DataTables
        const initializer = new DataTableInitializer(this.selector, {
            order: [[2, "asc"]],
        });

        this.dataTableInstance = initializer.initialize();
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
