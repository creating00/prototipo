import { Toast } from "@/config/notifications";

const destinations = {
    branches: [],

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
        });
    },
};

export default destinations;
