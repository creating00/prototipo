// resources/js/modules/sales/constants/payment-constants.js

export const SALE_TYPE = Object.freeze({
    SALE: "1",
    REPAIR: "2",
});

export const PAYMENT_TYPE = Object.freeze({
    CASH: "1",
    CARD: "2",
    TRANSFER: "3",
});

export const PAYMENT_MODELS = Object.freeze({
    [PAYMENT_TYPE.CARD]: "App\\Models\\Bank",
    [PAYMENT_TYPE.TRANSFER]: "App\\Models\\BankAccount",
});

export const FIELDS_TO_SYNC = {
    sale_date: "hidden_sale_date",
    amount_received_1_modal: "hidden_amount_received",
    amount_received_2_modal: "hidden_amount_received_2",
    payment_type_1_modal: "hidden_payment_type",
    payment_type_2_modal: "hidden_payment_type_2",
    change_returned: "hidden_change_returned",
    remaining_balance: "hidden_remaining_balance",
    repair_amount: "hidden_repair_amount",
    discount_id: "hidden_discount_id",
    payment_type_visible: "hidden_payment_type",
    discount_amount_input: "hidden_discount_amount",
    bank_id_visible: "hidden_payment_method_id",
    bank_account_id_visible: "hidden_payment_method_id",
    bank_id_1_modal: "hidden_payment_method_id",
    bank_account_id_1_modal: "hidden_payment_method_id",
    bank_id_2_modal: "hidden_payment_method_id_2",
    bank_account_id_2_modal: "hidden_payment_method_id_2",
    exchange_rate_blue: "hidden_exchange_rate_blue",
};
