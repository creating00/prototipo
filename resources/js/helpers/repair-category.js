// resources/js/helpers/repair-category.js
export function getRepairCategoryId() {
    const repairTypeSelect =
        document.getElementById("repair_type") ||
        document.querySelector('select[name="repair_type_id"]');

    const categoryId = repairTypeSelect?.value;

    // Excluir vacío y "Otro" (ID 6)
    if (!categoryId || categoryId === "" || categoryId === "6") {
        return null;
    }

    return categoryId;
}
