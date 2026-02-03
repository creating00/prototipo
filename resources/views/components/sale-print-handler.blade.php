{{-- resources/views/components/sale-print-handler.blade.php --}}
@if (session('print_receipt'))
    <script>
        (() => {
            const data = @json(session('print_receipt'));
            if (!data.sale_id) return;

            const routes = {
                'a4': "{{ route('sales.a4', ':id') }}",
                'ticket': "{{ route('sales.ticket', ':id') }}"
            };

            const baseUrl = routes[data.type] || routes['ticket'];
            const url = baseUrl.replace(':id', data.sale_id);

            window.open(url, '_blank');
        })();
    </script>
@endif
