import { Toast } from "@/config/notifications";

const destinations = {
    branches: [],
    stockRequests: new WeakMap(),

    get isManual() {
        return this.enabled && document.querySelector('input[name="source"]')?.value === "3";
    },

    async refreshDestinationStock(row) {
        if (!this.isManual) return;
        this.stockRequests.get(row)?.abort();
        const input = row.querySelector(".item-stock");
        const destinationId = row.querySelector(".item-destination")?.value;
        if (!input) return;
        input.value = "—";
        input.title = "Seleccione una sucursal de destino";
        if (!destinationId) return;

        const controller = new AbortController();
        this.stockRequests.set(row, controller);
        input.value = "...";
        input.title = "Consultando stock del destino";
        const url = new URL("/api/inventory/manual-destination-stock", window.location.origin);
        url.searchParams.set("product_id", row.dataset.id);
        url.searchParams.set("branch_id", document.getElementById("current_branch_id")?.value || "");
        url.searchParams.set("destination_branch_id", destinationId);
        url.searchParams.set("destination_province_id", row.querySelector(".item-destination-province")?.value || "");
        try {
            const response = await fetch(url, {
                headers: { Accept: "application/json" },
                cache: "no-store",
                signal: controller.signal,
            });
            if (!response.ok) throw new Error("No se pudo consultar el stock del destino.");
            const data = await response.json();
            if (controller.signal.aborted || this.stockRequests.get(row) !== controller) return;
            input.value = data.stock;
            input.title = "Stock actual de la sucursal de destino";
        } catch (error) {
            if (controller.signal.aborted || this.stockRequests.get(row) !== controller) return;
            input.value = "—";
            input.title = "Stock no disponible";
            Toast.fire({ icon: "warning", title: "Stock no disponible", text: "No se pudo consultar el destino. Vuelva a seleccionar la sucursal para reintentar." });
        }
    },

    get enabled() {
        return document.getElementById("is_multi_destination")?.value === "1";
    },

    get provinceId() {
        return document.getElementById("destination_province_id")?.value || "";
    },

    init() {
        if (!this.enabled) return;
        this.branches = JSON.parse(document.getElementById("transfer-destinations-data")?.value || "[]");
        document.getElementById("orderForm")?.addEventListener("submit", (event) => {
            const rows = [...document.querySelectorAll("#order-items-table tbody tr")];
            let invalid = rows.length === 0;
            for (const row of rows) {
                const quantity = Number(row.querySelector(".quantity")?.value);
                if (!row.querySelector(".item-destination")?.value || !Number.isInteger(quantity) || quantity < 1) invalid = true;
            }
            if (invalid) {
                event.preventDefault();
                event.stopImmediatePropagation();
                Toast.fire({ icon: "warning", title: "Revise el pedido", text: "Agregue productos y seleccione un destino para cada renglón." });
            }
        }, true);
    },

    prepareRow(row, saved = null) {
        if (!this.enabled || !row) return;
        const select = row.querySelector(".item-destination");
        const provinceInput = row.querySelector(".item-destination-province");
        if (!select || !provinceInput) return;
        const destinationId = saved?.destination_branch_id ?? row.dataset.destinationId ?? "";
        const branch = this.branches.find((item) => String(item.id) === String(destinationId));
        const provinceId = branch?.province_id ?? saved?.destination_province_id ?? provinceInput.value ?? this.provinceId;
        const effectiveProvince = provinceId || this.provinceId;
        select.replaceChildren(new Option("Seleccione una sucursal", ""));
        for (const option of this.branches.filter((item) => String(item.province_id) === String(effectiveProvince))) {
            select.add(new Option(option.name, String(option.id)));
        }
        select.value = String(destinationId);
        provinceInput.value = String(effectiveProvince);
        const provinceLabel = row.querySelector(".item-destination-province-label");
        if (provinceLabel) {
            provinceLabel.textContent = this.branches.find((item) => String(item.province_id) === String(effectiveProvince))?.province_name || "";
        }
        row.dataset.destinationId = select.value;
        this.refreshDestinationStock(row);
        if (row.dataset.destinationBound) return;
        row.dataset.destinationBound = "true";
        select.addEventListener("change", () => {
            const duplicate = [...row.parentElement.children].find((other) => other !== row
                && other.dataset.id === row.dataset.id && other.querySelector(".item-destination")?.value === select.value);
            if (select.value && duplicate) {
                select.value = row.dataset.destinationId || "";
                Toast.fire({ icon: "warning", title: "Destino repetido", text: "Modifique la cantidad del renglón existente para ese producto y destino." });
                return;
            }
            row.dataset.destinationId = select.value;
            this.refreshDestinationStock(row);
        });
    },
};

export default destinations;
