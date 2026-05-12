// resources/js/modules/sales/services/ProductCalculator.js
const ProductCalculator = {
    calculateRowSubtotal: (price, quantity) => {
        const p = parseFloat(price) || 0;
        const q = parseFloat(quantity) || 0;
        return (p * q).toFixed(2);
    },

    calculateTableTotal: (rows) => {
        return Array.from(rows).reduce((sum, row) => {
            const subtotalInput = row.querySelector(".subtotal");
            return sum + (parseFloat(subtotalInput?.value) || 0);
        }, 0);
    }
};

export default ProductCalculator;