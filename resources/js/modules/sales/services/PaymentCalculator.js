// resources/js/modules/sales/services/PaymentCalculator.js
const PaymentCalculator = {
    calculate: (total, received) => {
        const change = Math.max(0, received - total);
        const balance = Math.max(0, total - received);
        return {
            change: change.toFixed(2),
            balance: balance.toFixed(2)
        };
    },

    getStatus: (total, received, change, balance) => {
        if (balance == 0 && change == 0 && received > 0) return { label: "Pagado exacto", class: "success" };
        if (balance == 0 && change > 0) return { label: "Pagado con cambio", class: "info" };
        if (balance > 0 && received > 0) return { label: "Pago parcial", class: "warning" };
        if (balance > 0 && received == 0) return { label: "Pendiente de pago", class: "danger" };
        return { label: "Sin pago registrado", class: "secondary" };
    }
};

export default PaymentCalculator;