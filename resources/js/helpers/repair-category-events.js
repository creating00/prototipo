// resources/js/helpers/repair-category-events.js

export function dispatchRepairCategoryChanged(categoryId) {
    document.dispatchEvent(
        new CustomEvent("repair:categoryChanged", {
            detail: { categoryId },
        })
    );
}
