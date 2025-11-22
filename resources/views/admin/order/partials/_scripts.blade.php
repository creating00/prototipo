{{-- <script src="/js/client/client-search-modal.js"></script> --}}

<!-- Componentes base -->
<script src="{{ asset('js/order/order-client.js') }}"></script>
<script src="{{ asset('js/order/order-products.js') }}"></script>

<!-- OrderForm principal -->
<script src="{{ asset('js/order/order-form.js') }}"></script>

<!-- Nuevos componentes divididos -->
<script src="{{ asset('js/order/order-form-validator.js') }}"></script>
<script src="{{ asset('js/order/order-form-data-preparer.js') }}"></script>
<script src="{{ asset('js/order/order-payment-processor.js') }}"></script>
<script src="{{ asset('js/order/order-form-event-handler.js') }}"></script>
<script src="{{ asset('js/order/payment-modal.js') }}"></script>
<script src="{{ asset('js/order/order-form-submit-handler.js') }}"></script>

<!-- Handler principal -->
<script src="{{ asset('js/order/order-form-handler.js') }}"></script>

<!-- Inicialización -->
<script src="{{ asset('js/order-form-init.js') }}"></script>

<script>
    window.authUserId = {{ auth()->id() }};
    window.orderFormUrl = '{{ $order ? '/api/orders/' . $order->id : '/api/orders' }}';
    window.orderFormMethod = '{{ $order ? 'PUT' : 'POST' }}';
    window.orderIndexUrl = "{{ route('order.index') }}";
    window.currentOrderId = {{ $order ? $order->id : 'null' }};
</script>
