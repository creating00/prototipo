@props(['order' => null])

<div class="card">
    <div class="card-body">
        <form id="orderForm">
            <!-- Sección Cliente -->
            <div class="row">
                <div class="col-md-8">
                    <div class="form-group">
                        <label for="selectedClientInfo">Cliente Seleccionado *</label>
                        <div id="selectedClientInfo" class="alert alert-info" style="display: none;">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong id="clientNameDisplay"></strong><br>
                                    <small>Documento: <span id="clientDocumentDisplay"></span></small>
                                    <input type="hidden" id="client_id" name="client_id">
                                </div>
                                <button type="button" class="btn btn-outline-secondary btn-sm"
                                    onclick="clearClientSelection()">
                                    <i class="fas fa-times"></i> Cambiar
                                </button>
                            </div>
                        </div>
                        <div id="noClientSelected" class="alert alert-warning">
                            No se ha seleccionado ningún cliente
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="button" id="searchClientBtn" class="btn btn-primary btn-block"
                            data-toggle="modal" data-target="#clientSearchModal">
                            <i class="fas fa-search"></i> Buscar Cliente
                        </button>
                        <button type="button" id="newClientBtn" class="btn btn-outline-secondary btn-block mt-2"
                            onclick="showNewClientForm()">
                            <i class="fas fa-plus"></i> Nuevo Cliente
                        </button>
                    </div>
                </div>
            </div>

            <!-- Checkbox Aplicar Venta -->
            <div class="row mb-3">
                <div class="col-md-12">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="apply_sale" name="apply_sale">
                        <label class="form-check-label" for="apply_sale">
                            <strong>Aplicar venta</strong>
                        </label>
                        <small class="form-text text-muted d-block">
                            Al marcar esta opción, la orden se procesará como una venta inmediata
                        </small>
                    </div>
                </div>
            </div>

            <!-- Formulario Nuevo Cliente (oculto inicialmente) -->
            <div id="new_client_form" style="display: none;">
                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="card-title">Datos del Nuevo Cliente</h5>
                        <button type="button" class="close" onclick="hideNewClientForm()">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="client_document">Documento *</label>
                                    <input type="text" id="client_document" class="form-control"
                                        name="cliente[document]">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="client_full_name">Nombre Completo *</label>
                                    <input type="text" id="client_full_name" class="form-control"
                                        name="cliente[full_name]">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="client_phone">Teléfono</label>
                                    <input type="text" id="client_phone" class="form-control" name="cliente[phone]">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="client_address">Dirección</label>
                                    <input type="text" id="client_address" class="form-control"
                                        name="cliente[address]">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <button type="button" class="btn btn-secondary" onclick="hideNewClientForm()">
                                    <i class="fas fa-arrow-left"></i> Volver
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sección Productos -->
            <div class="mt-4">
                <h5>Productos</h5>

                <!-- Formulario fijo para agregar productos -->
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="product_select">Seleccionar Producto</label>
                                    <select id="product_select" class="form-control">
                                        <option value="">Seleccionar Producto</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="product_quantity">Cantidad</label>
                                    <input type="number" id="product_quantity" class="form-control" min="1"
                                        value="1" placeholder="Cantidad">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <button type="button" id="addProductBtn" class="btn btn-success btn-block"
                                        onclick="addProductToTable()" disabled>
                                        <i class="fas fa-plus"></i> Agregar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabla de productos añadidos -->
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="thead-dark">
                            <tr>
                                <th>Producto</th>
                                <th width="120">Precio Unit.</th>
                                <th width="120">Cantidad</th>
                                <th width="120">Subtotal</th>
                                <th width="80">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="products_table_body">
                            <!-- Los productos se agregarán dinámicamente aquí -->
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-right">Total:</th>
                                <th id="total_amount" class="text-success">S/. 0.00</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div id="no_products_message" class="alert alert-info text-center">
                    No se han agregado productos al pedido
                </div>
            </div>

            <!-- Botones -->
            <div class="mt-4">
                <button type="submit" id="submitOrderBtn" class="btn btn-primary">
                    {{ $order ? 'Actualizar' : 'Crear' }} Pedido
                </button>
                <a href="{{ route('order.index') }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<!-- Incluir el modal de búsqueda -->
@include('admin.order.partials._client_search_modal')
