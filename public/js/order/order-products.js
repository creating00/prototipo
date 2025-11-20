// order-products.js
class OrderProducts {
    constructor(orderForm) {
        this.orderForm = orderForm;
        this.allProducts = [];
        this.addedProducts = []; // Array para almacenar productos añadidos
    }

    // Cargar productos desde la API
    async loadProducts() {
        try {
            const response = await axios.get("/api/products");
            this.allProducts = response.data;
            // Asegurar que los precios sean números
            this.allProducts = this.allProducts.map((product) => ({
                ...product,
                sale_price: parseFloat(product.sale_price) || 0,
            }));
            this.populateProductSelect();
        } catch (error) {
            console.error("Error loading products:", error);
        }
    }

    // Llenar el select de productos
    populateProductSelect() {
        const select = document.getElementById("product_select");
        if (!select) return;

        const addProductBtn = document.getElementById("addProductBtn");

        select.innerHTML =
            '<option value="">Seleccionar Producto</option>' +
            this.allProducts
                .map(
                    (product) =>
                        `<option value="${product.id}" data-price="${
                            product.sale_price
                        }">
                ${product.name} - S/. ${product.sale_price.toFixed(2)}
            </option>`
                )
                .join("");

        if (addProductBtn) {
            select.addEventListener("change", () => {
                addProductBtn.disabled = !select.value;
            });
        }
    }

    // Agregar producto a la tabla
    addProductToTable() {
        const productSelect = document.getElementById("product_select");
        const quantityInput = document.getElementById("product_quantity");

        if (!productSelect || !quantityInput) return;

        const productId = productSelect.value;
        const quantity = parseInt(quantityInput.value);

        if (!productId) {
            alert("Por favor seleccione un producto");
            return;
        }

        if (!quantity || quantity < 1) {
            alert("Por favor ingrese una cantidad válida");
            return;
        }

        const product = this.allProducts.find((p) => p.id == productId);
        if (!product) return;

        // Asegurar que el precio sea número
        const productPrice = parseFloat(product.sale_price) || 0;

        // Verificar si el producto ya fue agregado
        const existingProductIndex = this.addedProducts.findIndex(
            (p) => p.id == productId
        );

        if (existingProductIndex !== -1) {
            // Actualizar cantidad si ya existe
            this.addedProducts[existingProductIndex].quantity += quantity;
            this.addedProducts[existingProductIndex].subtotal =
                this.addedProducts[existingProductIndex].quantity *
                productPrice;
        } else {
            // Agregar nuevo producto
            this.addedProducts.push({
                id: product.id,
                name: product.name,
                price: productPrice, // Aseguramos que sea número
                quantity: quantity,
                subtotal: productPrice * quantity,
            });
        }

        this.renderProductsTable();
        this.clearProductForm();
    }

    // Renderizar la tabla de productos
    renderProductsTable() {
        const tbody = document.getElementById("products_table_body");
        const noProductsMessage = document.getElementById(
            "no_products_message"
        );
        const totalAmount = document.getElementById("total_amount");

        if (!tbody || !noProductsMessage || !totalAmount) return;

        if (this.addedProducts.length === 0) {
            tbody.innerHTML = "";
            noProductsMessage.style.display = "block";
            totalAmount.textContent = "S/. 0.00";
            return;
        }

        noProductsMessage.style.display = "none";

        const rows = this.addedProducts
            .map((product, index) => {
                // Asegurar que price y subtotal sean números
                const price = parseFloat(product.price) || 0;
                const subtotal = parseFloat(product.subtotal) || 0;

                return `
                    <tr>
                        <td>${product.name}</td>
                        <td class="text-right">S/. ${price.toFixed(2)}</td>
                        <td class="text-center">
                            <div class="input-group input-group-sm">
                                <input type="number" 
                                       class="form-control form-control-sm quantity-update" 
                                       value="${product.quantity}" 
                                       min="1" 
                                       data-index="${index}"
                                       style="width: 80px;">
                            </div>
                        </td>
                        <td class="text-right text-success font-weight-bold">S/. ${subtotal.toFixed(
                            2
                        )}</td>
                        <td class="text-center">
                            <button type="button" class="btn btn-danger btn-sm remove-product" data-index="${index}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
            })
            .join("");

        tbody.innerHTML = rows;

        // Calcular total
        const total = this.addedProducts.reduce(
            (sum, product) => sum + (parseFloat(product.subtotal) || 0),
            0
        );
        totalAmount.textContent = `S/. ${total.toFixed(2)}`;

        // Configurar event listeners
        this.setupTableEvents();
    }

    // Configurar eventos de la tabla
    setupTableEvents() {
        // Eventos para actualizar cantidades
        document.querySelectorAll(".quantity-update").forEach((input) => {
            input.addEventListener("change", (e) => {
                const index = parseInt(e.target.getAttribute("data-index"));
                const newQuantity = parseInt(e.target.value);

                if (newQuantity >= 1) {
                    this.updateProductQuantity(index, newQuantity);
                } else {
                    e.target.value = this.addedProducts[index].quantity;
                }
            });
        });

        // Eventos para eliminar productos
        document.querySelectorAll(".remove-product").forEach((button) => {
            button.addEventListener("click", (e) => {
                const index = parseInt(
                    e.target.closest("button").getAttribute("data-index")
                );
                this.removeProduct(index);
            });
        });
    }

    // Actualizar cantidad de producto
    updateProductQuantity(index, newQuantity) {
        if (index >= 0 && index < this.addedProducts.length) {
            const price = parseFloat(this.addedProducts[index].price) || 0;
            this.addedProducts[index].quantity = newQuantity;
            this.addedProducts[index].subtotal = price * newQuantity;
            this.renderProductsTable();
        }
    }

    // Eliminar producto
    removeProduct(index) {
        if (index >= 0 && index < this.addedProducts.length) {
            this.addedProducts.splice(index, 1);
            this.renderProductsTable();
        }
    }

    // Limpiar formulario de producto
    clearProductForm() {
        const productSelect = document.getElementById("product_select");
        const quantityInput = document.getElementById("product_quantity");

        if (productSelect) productSelect.value = "";
        if (quantityInput) quantityInput.value = "1";
    }

    // Cargar productos desde una orden existente
    loadProductsFromOrder(orderItems) {
        this.addedProducts = orderItems.map((item) => {
            const price = parseFloat(item.unit_price) || 0;
            const subtotal = parseFloat(item.subtotal) || 0;

            return {
                id: item.product_id,
                name: item.product?.name || "Producto",
                price: price,
                quantity: item.quantity,
                subtotal: subtotal,
            };
        });

        this.renderProductsTable();
    }

    // Validar productos
    validateProducts() {
        if (this.addedProducts.length === 0) {
            alert("Debe agregar al menos un producto");
            return false;
        }
        return true;
    }

    // Obtener datos de productos para el formulario
    getProductsData() {
        return this.addedProducts.map((product) => ({
            id: product.id,
            cantidad: product.quantity,
        }));
    }

    // Obtener total del pedido
    getTotalAmount() {
        return this.addedProducts.reduce(
            (sum, product) => sum + (parseFloat(product.subtotal) || 0),
            0
        );
    }
}
