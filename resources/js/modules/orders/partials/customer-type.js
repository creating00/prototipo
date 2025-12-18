// resources/js/partials/customer-type.js

const customerType = (() => {
    const init = () => {
        const customerTypeSelect = document.querySelector(
            'select[name="customer_type"]'
        );
        const clientWrapper = document.getElementById("client-select-wrapper");
        const branchWrapper = document.getElementById("branch-select-wrapper");

        if (!customerTypeSelect) return;

        const toggleCustomerSelects = () => {
            if (customerTypeSelect.value === "App\\Models\\Client") {
                clientWrapper.style.display = "block";
                branchWrapper.style.display = "none";
            } else {
                clientWrapper.style.display = "none";
                branchWrapper.style.display = "block";
            }
        };

        customerTypeSelect.addEventListener("change", toggleCustomerSelects);
        toggleCustomerSelects(); // inicializa según el valor actual
    };

    return { init };
})();

export default customerType;
