// resources/js/modules/sales/services/PaymentCalculator.js
const PaymentCalculator = {
    calculate: (total, received) => {
        const t = parseFloat(total) || 0;
        const r = parseFloat(received) || 0;
        return {
            change: Math.max(0, r - t),
            balance: Math.max(0, t - r),
        };
    },

    getStatus: (total, received, change, balance) => {
        const b = parseFloat(balance) || 0;
        const c = parseFloat(change) || 0;
        const r = parseFloat(received) || 0;

        if (b === 0 && c === 0 && r > 0)
            return { label: "Pagado exacto", class: "success" };
        if (b === 0 && c > 0)
            return { label: "Pagado con cambio", class: "info" };
        if (b > 0 && r > 0) return { label: "Pago parcial", class: "warning" };
        if (b > 0 && r === 0)
            return { label: "Pendiente de pago", class: "danger" };
        return { label: "Sin pago registrado", class: "secondary" };
    },
};

export default PaymentCalculator;
