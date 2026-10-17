import Swal from "sweetalert2";

export function initCartTransfer() {
    const button = document.getElementById("send-to-branch");
    const form = document.getElementById("saleForm");
    if (!button || !form) return;

    let busy = false;
    form.addEventListener("submit", (event) => {
        if (busy) {
            event.preventDefault();
            event.stopImmediatePropagation();
        }
    }, true);

    button.addEventListener("click", async () => {
        if (busy) return;
        const fields = new FormData(form);
        const origin = form.querySelector('[name="branch_id"]');
        const items = Array.from(form.querySelectorAll('#order-items-table tbody tr[data-id]')).map((row) => ({
            product_id: Number(row.dataset.id),
            quantity: Number(row.querySelector(".quantity")?.value),
        }));
        if (!items.length || items.some((item) => !Number.isInteger(item.quantity) || item.quantity < 1)) {
            await Swal.fire({ icon: "warning", text: "Agregue productos con cantidades enteras mayores a cero." });
            return;
        }
        if (String(fields.get("branch_id")) !== button.dataset.originId) {
            await Swal.fire({ icon: "warning", text: "Seleccione la sucursal de origen desde el selector superior y vuelva a armar el carrito." });
            return;
        }

        busy = true;
        button.disabled = true;
        form.inert = true;
        let completed = false;
        try {
            const branches = JSON.parse(button.dataset.branches);
            const selection = await Swal.fire({
                title: "Enviar a sucursal",
                text: "Seleccione el destino del carrito completo.",
                input: "select",
                inputOptions: Object.fromEntries(branches.map((branch) => [branch.id, branch.name])),
                inputPlaceholder: "Seleccione una sucursal",
                inputValidator: (value) => !value ? "Seleccione una sucursal de destino." : undefined,
                showCancelButton: true,
                confirmButtonText: "Continuar",
                cancelButtonText: "Cancelar",
            });
            if (!selection.isConfirmed) return;

            const destination = branches.find((branch) => String(branch.id) === selection.value);
            const quantity = items.reduce((total, item) => total + item.quantity, 0);
            const confirmation = await Swal.fire({
                icon: "question",
                title: "Confirmar envío",
                text: `Vas a enviar ${quantity} unidades desde ${origin.selectedOptions[0].textContent.trim()} a ${destination.name}. Se descontarán del origen y se sumarán al destino.`,
                showCancelButton: true,
                confirmButtonText: "Confirmar envío",
                cancelButtonText: "Cancelar",
            });
            if (!confirmation.isConfirmed) return;

            Swal.fire({ title: "Enviando pedido...", allowOutsideClick: false, allowEscapeKey: false, didOpen: () => Swal.showLoading() });
            const response = await fetch(button.dataset.url, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": fields.get("_token"),
                },
                body: JSON.stringify({
                    branch_id: fields.get("branch_id"),
                    destination_branch_id: selection.value,
                    transfer_token: button.dataset.token,
                    exchange_rate: fields.get("exchange_rate_blue") || 1,
                    items,
                }),
            });
            const data = await response.json().catch(() => {
                throw new Error("No se pudo verificar la respuesta del servidor. Puede reintentar el mismo envío sin duplicarlo.");
            });
            if (!response.ok) {
                throw new Error(data.errors ? Object.values(data.errors).flat().join("\n") : (data.message || "No se pudo realizar el envío."));
            }
            completed = true;
            const result = await Swal.fire({
                icon: "success",
                title: "Envío registrado",
                text: `Pedido ${data.order_number}. El stock se actualizó en ambas sucursales.`,
                confirmButtonText: "Nueva venta",
                showDenyButton: true,
                denyButtonText: "Ver pedidos",
                allowOutsideClick: false,
                allowEscapeKey: false,
            });
            window.location.assign(result.isDenied ? data.orders_url : data.redirect_url);
        } catch (error) {
            await Swal.fire({ icon: "error", title: "No se pudo confirmar el envío", text: error.message });
        } finally {
            if (!completed) {
                busy = false;
                button.disabled = false;
                form.inert = false;
            }
        }
    });
}
