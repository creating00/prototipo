class BranchIndex {
    constructor() {
        this.service = new BranchService();
        this.table = new BranchTable();
        this.init();
    }

    async init() {
        await this.loadBranches();

        document.addEventListener("click", (e) => {
            const btn = e.target.closest("[data-action='delete-branch']");
            if (btn) {
                const id = btn.dataset.id;
                this.deleteBranch(id);
            }
        });
    }

    async loadBranches() {
        const branches = await this.service.getAll();
        this.table.updateTable(branches);
    }

    async deleteBranch(id) {
        if (!confirm("¿Eliminar sucursal?")) return;

        await this.service.delete(id);
        await this.loadBranches();
    }
}

document.addEventListener("DOMContentLoaded", () => new BranchIndex());
