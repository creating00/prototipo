@props([
    'sale' => null,
    'branches' => [],
    'clients' => [],
    'statusOptions' => [],
])

@push('styles')
    <style>
        .equal-height-selects .form-group {
            height: 100%;
        }

        .equal-height-selects .select2-container {
            height: 100%;
        }

        .sticky-summary {
            position: sticky;
            top: 1rem;
        }
    </style>
@endpush

@php
    $customerType = old('customer_type') ?? ($customer_type ?? ($sale->customer_type ?? null));
    $customerId = old('customer_id', $sale->customer_id ?? null);

    $isRepair =
        (old('sale_type') ?? ($sale->sale_type?->value ?? \App\Enums\SaleType::Sale->value)) ==
        \App\Enums\SaleType::Repair->value;

    // Detectamos qué sección debe abrirse
    $originErrors = $errors->hasAny(['customer_type', 'client_id', 'branch_id']);
    $productErrors = $errors->hasAny(['products', 'products.*.quantity', 'products.*.price']);
    $paymentErrors = $errors->hasAny([
        'sale_date',
        'payment_type',
        'amount_received',
        'change_returned',
        'remaining_balance',
        'total_amount',
        'repair_amount',
    ]);
    $notesErrors = $errors->has('notes');

    // Prioridad de apertura
    $openSection = $paymentErrors
        ? 'payment'
        : ($productErrors
            ? 'products'
            : ($originErrors
                ? 'origin'
                : ($notesErrors
                    ? 'notes'
                    : 'origin')));
@endphp

<input type="hidden" name="user_id" value="{{ auth()->id() }}">
<input type="hidden" name="source" value="1">

<div class="accordion" id="saleFormAccordion">
    {{-- Tipo de formulario dinámico --}}
    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button {{ $openSection !== 'origin' ? 'collapsed' : '' }}" type="button"
                data-bs-toggle="collapse" data-bs-target="#originSection"
                aria-expanded="{{ $openSection === 'origin' ? 'true' : 'false' }}">
                Origen de la Venta
            </button>
        </h2>

        <div id="originSection" class="accordion-collapse collapse {{ $openSection === 'origin' ? 'show' : '' }}"
            data-bs-parent="#saleFormAccordion">
            <div class="accordion-body">
                @include('admin.sales.partials.sections._origin')
            </div>
        </div>
    </div>

    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button {{ $openSection !== 'products' ? 'collapsed' : '' }}" type="button"
                data-bs-toggle="collapse" data-bs-target="#productsSection"
                aria-expanded="{{ $openSection === 'products' ? 'true' : 'false' }}">
                Productos de la Venta
            </button>
        </h2>

        <div id="productsSection" class="accordion-collapse collapse {{ $openSection === 'products' ? 'show' : '' }}"
            data-bs-parent="#saleFormAccordion">
            <div class="accordion-body">
                {{-- Buscador de producto --}}
                @include('admin.sales.partials.sections._products')
            </div>
        </div>
    </div>

    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button {{ $openSection !== 'payment' ? 'collapsed' : '' }}" type="button"
                data-bs-toggle="collapse" data-bs-target="#paymentSection"
                aria-expanded="{{ $openSection === 'payment' ? 'true' : 'false' }}">
                Totales y Pago
            </button>
        </h2>

        <div id="paymentSection" class="accordion-collapse collapse {{ $openSection === 'payment' ? 'show' : '' }}"
            data-bs-parent="#saleFormAccordion">
            <div class="accordion-body">
                @include('admin.sales.partials.sections._payment')
            </div>
        </div>
    </div>

    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button {{ $openSection !== 'notes' ? 'collapsed' : '' }}" type="button"
                data-bs-toggle="collapse" data-bs-target="#notesSection"
                aria-expanded="{{ $openSection === 'notes' ? 'true' : 'false' }}">
                Notas y Observaciones
            </button>
        </h2>

        <div id="notesSection" class="accordion-collapse collapse {{ $openSection === 'notes' ? 'show' : '' }}"
            data-bs-parent="#saleFormAccordion">
            <div class="accordion-body">
                @include('admin.sales.partials.sections._notes')
            </div>
        </div>
    </div>
</div>
