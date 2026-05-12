export class ProviderSelector {
    constructor(select, addButton) {
        this.select = select;
        this.addButton = addButton;
        this.lastProviderId = this.getValue();
    }

    getValue() {
        if (!this.select) return "";
        return this.select._choices
            ? String(this.select._choices.getValue(true) || "").trim()
            : String(this.select.value || "").trim();
    }

    toggleAddButton() {
        if (!this.addButton) return;
        const val = this.getValue();
        this.addButton.disabled = val === "" || val === "null";
    }

    confirmChange(hasItems) {
        const current = this.getValue();
        if (hasItems && current !== this.lastProviderId) {
            const ok = confirm(
                "Si cambia el proveedor, se eliminarán los productos actuales. ¿Continuar?"
            );
            if (!ok) {
                this.restore();
                return false;
            }
        }
        this.lastProviderId = current;
        return true;
    }

    restore() {
        if (this.select._choices) {
            this.select._choices.setChoiceByValue(this.lastProviderId);
        } else {
            this.select.value = this.lastProviderId;
        }
    }
}
