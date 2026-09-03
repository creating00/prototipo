// resources/js/helpers/repair-category.js
export function getSelectedSaleType() {
    return (
        document.querySelector('input[name="sale_type"]:checked')?.value ||
        document.querySelector('select[name="sale_type"]')?.value ||
        "1"
    );
}

export function isRepairSaleSelected() {
    return getSelectedSaleType() === "2";
}

export function getSelectedRepairTypeId() {
    return (
        document.querySelector('input[name="repair_type_id"]:checked')?.value ||
        document.getElementById("repair_type")?.value ||
        document.querySelector('select[name="repair_type_id"]')?.value ||
        null
    );
}

export function getSelectedRepairAmount() {
    const typeId = getSelectedRepairTypeId();
    const amount = typeId ? window.repairAmountsMap?.[typeId] : null;

    if (amount === null || amount === undefined || amount === "") {
        return null;
    }

    const parsed = parseFloat(amount);
    return Number.isNaN(parsed) ? null : parsed;
}

export function formatRepairAmount(amount) {
    return new Intl.NumberFormat("es-AR", {
        style: "currency",
        currency: "ARS",
        minimumFractionDigits: 2,
    }).format(amount);
}

export function getRepairCategoryId() {
    const categoryId = getSelectedRepairTypeId();

    // Excluir vacío y "Otro" (ID 6)
    if (!categoryId || categoryId === "" || categoryId === "6") {
        return null;
    }

    return categoryId;
}
