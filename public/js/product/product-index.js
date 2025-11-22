class ProductIndex {
    static init() {
        this.service = new ProductService();
        this.table = new ProductTable();
        this.load();
    }

    static async load() {
        try {
            const products = await this.service.getAll();
            this.table.updateTable(products);
        } catch (err) {
            console.error(err);
            alert('Error cargando productos.');
        }
    }

    static async deleteProduct(id) {
        if (!confirm("¿Eliminar producto?")) return;

        try {
            await this.service.delete(id);
            await this.load();
        } catch (err) {
            console.error(err);
            const message = err.response?.data?.error || 'Error eliminando el producto';
            alert(message);
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    ProductIndex.init();
});
