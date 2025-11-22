class CategoryIndex {
    constructor() {
        this.service = new CategoryService();
        this.table = new CategoryTable();
        this.init();
    }

    async init() {
        await this.loadCategories();

        document.addEventListener("click", (e) => {
            if (e.target.closest("[data-action='delete-category']")) {
                const id = e.target.closest("button").dataset.id;
                this.deleteCategory(id);
            }
        });
    }

    async loadCategories() {
        const categories = await this.service.getAll();
        this.table.updateTable(categories);
    }

    async deleteCategory(id) {
        if (!confirm("¿Eliminar categoría?")) return;

        await this.service.delete(id);
        await this.loadCategories();
    }
}

document.addEventListener("DOMContentLoaded", () => new CategoryIndex());
