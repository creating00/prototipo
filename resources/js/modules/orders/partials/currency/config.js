/**
 * Configuración centralizada del módulo de monedas
 */
export const CONFIG = {
    FALLBACK_RATE: 1000,
    
    CURRENCY_CODES: {
        ARS: "1",
        USD: "2",
    },
    
    RATE_TYPES: {
        CLIENT: "venta",
        BRANCH: "compra",
    },
};

export const SELECTORS = {
    dollarInput: "current_dollar_price",
    totalUsd: "total_amount_usd",
    totalArs: "total_amount",
    labelArs: "subtotal_ars_pure",
    labelUsd: "subtotal_usd_pure",
    customerType: "customer_type",
    exchangeRate: "exchange_rate",
    isEdit: "is_edit",
    rows: "#order-items-table tr[data-id]",
};