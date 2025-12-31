import { OrderUIHelper } from "../../utils/order-ui-helper";

export class ItemRowManager {
    constructor(container, template, totalLabel) {
        this.container = container;
        this.template = template;
        this.totalLabel = totalLabel;
        this.rowCount = container.querySelectorAll(".item-row").length;
        this.products = [];
    }

    setProducts(products) {
        this.products = products;
        this.refreshAllChoices();
    }

    addRow() {
        const content = this.template.innerHTML.replace(
            /__INDEX__/g,
            this.rowCount++
        );

        this.container.insertAdjacentHTML("beforeend", content);
        const row = this.container.lastElementChild;
        this.initializeChoices(row);
        this.calculateTotals();
    }

    removeRow(row) {
        const select = row.querySelector(".product-select");
        if (select?._choices) select._choices.destroy();
        row.remove();
        this.calculateTotals();
    }

    clearAll() {
        this.container
            .querySelectorAll(".item-row")
            .forEach((row) => this.removeRow(row));
    }

    initializeChoices(row) {
        const select = row.querySelector(".product-select");
        if (!select || typeof Choices === "undefined") return;

        const instance = new Choices(select, {
            searchEnabled: true,
            shouldSort: false,
            itemSelectText: "",
            placeholderValue: "Seleccione un producto",
        });

        select._choices = instance;

        if (this.products.length) {
            instance.setChoices(this.products, "value", "label", true);
        }

        select.addEventListener("choice", (e) =>
            this.handleProductSelection(e, row, instance)
        );
    }

    handleProductSelection(event, row, choicesInstance) {
        const choice = event.detail.choice;
        if (!choice?.value) return;

        if (
            OrderUIHelper.isDuplicate(
                this.container,
                event.target,
                choice.value
            )
        ) {
            alert("Este producto ya ha sido agregado a la lista.");
            setTimeout(() => {
                choicesInstance.removeActiveItems();
                choicesInstance.setChoiceByValue("");
            }, 100);
            return;
        }

        const { price, currency } = choice.customProperties || {};
        const costInput = row.querySelector(
            ".cost-input-container input:not([type='hidden'])"
        );
        const currencySelect = row.querySelector(
            ".cost-input-container select"
        );

        if (costInput) {
            costInput.value = price ?? 0;
            costInput.dispatchEvent(new Event("input", { bubbles: true }));
        }

        if (currencySelect && currency) {
            currencySelect.value = currency;
        }

        this.calculateTotals();
        row.querySelector(".qty-input")?.focus();
    }

    refreshAllChoices() {
        this.container.querySelectorAll(".product-select").forEach((select) => {
            if (!select._choices) return;
            select._choices.clearChoices();
            select._choices.setChoices(this.products, "value", "label", true);
        });
    }

    calculateTotals() {
        OrderUIHelper.calculateTotals("#items-container", "#order-total");
    }
}
