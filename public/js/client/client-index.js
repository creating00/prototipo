class ClientIndex {
    constructor() {
        this.service = new ClientService();
        this.table = new ClientTable();
        this.initializeEvents();
        this.load();
    }

    async load() {
        const clients = await this.service.getAll();
        this.table.updateTable(clients);
    }

    initializeEvents() {
        document.addEventListener('click', async (e) => {
            const btn = e.target.closest('[data-action="delete-client"]');
            if (!btn) return;

            const id = btn.dataset.id;

            if (!confirm('¿Eliminar cliente?')) return;

            await this.service.delete(id);
            this.load();
        });
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new ClientIndex();
});
