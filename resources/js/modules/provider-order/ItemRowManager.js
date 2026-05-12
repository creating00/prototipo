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
            ".cost-input-container input[id$='_amount']"
        );
        const currencySelect = row.querySelector(
            ".cost-input-container select[id$='_currency']"
        );

        if (costInput) {
            costInput.value = price ?? 0;
            // No disparamos calculateTotals aquí manualmente si el input ya burbujea
        }

        if (currencySelect && currency) {
            currencySelect.value = currency;
        }

        this.calculateTotals();
        row.querySelector(".qty-input")?.focus();
    }

    renderTotals(totals) {
        if (!this.totalLabel) return;

        this.totalLabel.innerHTML = "";
        const keys = Object.keys(totals);

        if (keys.length === 0) {
            this.totalLabel.textContent = "$ 0.00";
            return;
        }

        keys.forEach((id) => {
            const item = totals[id];
            const div = document.createElement("div");
            div.className = "d-block text-nowrap"; // Bootstrap classes
            div.style.fontWeight = "bold";
            div.textContent = `${item.label} ${item.sum.toLocaleString(
                undefined,
                {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                }
            )}`;
            this.totalLabel.appendChild(div);
        });
    }

    refreshAllChoices() {
        this.container.querySelectorAll(".product-select").forEach((select) => {
            if (!select._choices) return;
            select._choices.clearChoices();
            select._choices.setChoices(this.products, "value", "label", true);
        });
    }

    calculateTotals() {
        const totals = {}; // Objeto: { 1: { sum: 0, label: 'ARS' }, 2: { sum: 0, label: 'USD' } }
        const rows = this.container.querySelectorAll(".item-row");

        rows.forEach((row) => {
            const qty = parseFloat(row.querySelector(".qty-input").value) || 0;
            const amountInput = row.querySelector("input[id$='_amount']");
            const currencySelect = row.querySelector("select[id$='_currency']");

            if (!amountInput || !currencySelect) return;

            const amount = parseFloat(amountInput.value) || 0;
            const currencyId = currencySelect.value;
            const currencyText =
                currencySelect.options[currencySelect.selectedIndex]?.text ||
                "";
            const subtotal = qty * amount;

            // Actualizar el subtotal visual de la fila
            const rowSubtotalDisplay = row.querySelector(".row-subtotal");
            if (rowSubtotalDisplay) {
                rowSubtotalDisplay.textContent = `${currencyText} ${subtotal.toFixed(
                    2
                )}`;
            }

            // Acumular por moneda
            if (currencyId) {
                if (!totals[currencyId]) {
                    totals[currencyId] = { sum: 0, label: currencyText };
                }
                totals[currencyId].sum += subtotal;
            }
        });

        this.renderTotals(totals);
    }
}
