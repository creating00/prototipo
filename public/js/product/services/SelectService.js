class SelectService {
    static async loadBranches() {
        return await ApiClient.get("/api/branches");
    }

    static async loadCategories() {
        return await ApiClient.get("/api/categories");
    }

    static async loadAllSelects() {
        try {
            const [branches, categories] = await Promise.all([
                this.loadBranches(),
                this.loadCategories(),
            ]);
            return { branches, categories };
        } catch (error) {
            console.error("Error loading selects:", error);
            throw error;
        }
    }

    static populateSelect(
        selectElement,
        data,
        emptyOptionText = "Seleccione una opción"
    ) {
        if (!selectElement) return;

        selectElement.innerHTML =
            `<option value="">${emptyOptionText}</option>` +
            data
                .map(
                    (item) => `<option value="${item.id}">${item.name}</option>`
                )
                .join("");
    }
}
