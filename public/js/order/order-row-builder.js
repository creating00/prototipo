class OrderRowBuilder {
    constructor(orderIndexHandler) {
        this.orderIndexHandler = orderIndexHandler;
    }

    buildRow(order) {
        const totalQuantity = this.calculateTotalQuantity(order.items);
        const productsHtml = this.formatProductsList(order.items);

        return `
            <tr>
                <td>${order.id}</td>
                <td>${this.formatClientInfo(order.client)}</td>
                <td>${productsHtml}</td>
                <td class="text-center">${totalQuantity}</td>
                <td class="text-right">${this.formatAmount(
                    order.total_amount
                )}</td>
                <td>${order.user?.name || "Sistema"}</td>
                <td>${this.formatDate(order.created_at)}</td>
                <td>${this.createActionButtons(order)}</td>
            </tr>
        `;
    }

    calculateTotalQuantity(items) {
        return items.reduce((sum, item) => sum + item.quantity, 0);
    }

    formatClientInfo(client) {
        if (!client) return "<strong>N/A</strong>";
        return `
            <strong>${client.full_name}</strong><br>
            <small class="text-muted">Doc: ${client.document}</small>
        `;
    }

    formatProductsList(items) {
        const firstThree = items.slice(0, 3);
        const productsList = firstThree
            .map(
                (item) =>
                    `${item.product?.name || "Producto"} (${item.quantity})`
            )
            .join("<br>");

        const moreCount = items.length - 3;
        const moreProducts =
            moreCount > 0
                ? `<br><small class="text-muted">+${moreCount} más</small>`
                : "";

        return productsList + moreProducts;
    }

    formatAmount(amount) {
        return `S/. ${parseFloat(amount).toFixed(2)}`;
    }

    formatDate(dateString) {
        return new Date(dateString).toLocaleString("es-ES", {
            year: "numeric",
            month: "2-digit",
            day: "2-digit",
            hour: "2-digit",
            minute: "2-digit",
        });
    }

    createActionButtons(order) {
        const editBtn = `
        <a href="order/${order.id}/edit" class="btn btn-sm btn-warning" title="Editar">
            <i class="fas fa-edit"></i>
        </a>
    `;

        const hasPayment = order.payments && order.payments.length > 0;

        const paymentBtn = hasPayment
            ? `<button class="btn btn-sm btn-info" onclick="orderIndexHandler.viewInvoice(${order.payments[0].id})" title="Imprimir Factura">
                <i class="fas fa-print"></i>
            </button>`
            : `<button class="btn btn-sm btn-success" onclick="orderIndexHandler.createPayment(${order.id})" title="Crear Pago">
                <i class="fas fa-credit-card"></i>
            </button>`;

        const deleteBtn = `
            <button class="btn btn-sm btn-danger" onclick="orderIndexHandler.deleteOrder(${order.id})" title="Eliminar">
                <i class="fas fa-trash"></i>
            </button>
        `;

        return editBtn + paymentBtn + deleteBtn;
    }
}

window.OrderRowBuilder = OrderRowBuilder;
