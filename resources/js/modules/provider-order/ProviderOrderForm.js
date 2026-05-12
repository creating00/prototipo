import { ProviderProductService } from "./ProviderProductService";
import { ItemRowManager } from "./ItemRowManager";
import { ProviderSelector } from "./ProviderSelector";

export class ProviderOrderForm {
    constructor() {
        this.container = document.getElementById("items-container");
        this.template = document.getElementById("item-row-template");
        this.btnAddItem = document.getElementById("btn-add-item");
        this.providerSelect = document.querySelector(
            'select[name="provider_id"]'
        );
        this.totalLabel = document.getElementById("order-totals-container");

        this.rowManager = new ItemRowManager(
            this.container,
            this.template,
            this.totalLabel
        );

        this.provider = new ProviderSelector(
            this.providerSelect,
            this.btnAddItem
        );
    }

    init() {
        this.bindEvents();
        this.initializeRows();
        this.provider.toggleAddButton();
        this.rowManager.calculateTotals();
    }

    bindEvents() {
        this.providerSelect?.addEventListener("change", (e) =>
            this.onProviderChange(e)
        );

        this.btnAddItem?.addEventListener("click", () => {
            if (!this.btnAddItem.disabled) {
                this.rowManager.addRow();
            }
        });

        this.container.addEventListener("click", (e) => {
            const btn = e.target.closest(".remove-item");
            if (btn) {
                this.rowManager.removeRow(btn.closest(".item-row"));
            }
        });

        // Escucha cambios en inputs (cantidad y precio)
        this.container.addEventListener("input", (e) => {
            if (e.target.matches(".qty-input, input[id$='_amount']")) {
                this.rowManager.calculateTotals();
            }
        });

        // Escucha cambios en el select de moneda
        this.container.addEventListener("change", (e) => {
            if (e.target.matches("select[id$='_currency']")) {
                this.rowManager.calculateTotals();
            }
        });
    }

    async onProviderChange(e) {
        if (e.detail?.fromAlpine) {
            this.provider.toggleAddButton();
            return;
        }

        const hasItems =
            this.container.querySelectorAll(".item-row").length > 0;

        if (!this.provider.confirmChange(hasItems)) return;

        this.provider.toggleAddButton();

        const providerId = this.provider.getValue();
        if (!providerId || providerId === "null") return;

        try {
            const products = await ProviderProductService.fetchProducts(
                providerId
            );

            this.rowManager.setProducts(
                ProviderProductService.formatForChoices(products)
            );
        } catch (err) {
            console.error("Error loading products", err);
        }
    }

    initializeRows() {
        this.container
            .querySelectorAll(".item-row")
            .forEach((row) => this.rowManager.initializeChoices(row));
    }
}
