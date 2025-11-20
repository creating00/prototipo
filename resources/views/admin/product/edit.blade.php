@extends('adminlte::page')

@section('title', 'Editar producto')

@section('content_header')
    <h1>Editar Producto</h1>
@endsection

@section('content')

    <form id="formEdit">
        @include('admin.product.partials._form', [
            'product' => $product,
            'mode' => 'edit',
        ])
    </form>

@endsection

@section('js')
    <script>
        async function loadSelects() {
            const branches = await axios.get('/api/branches');
            const categories = await axios.get('/api/categories');

            document.querySelector('#branch_id').innerHTML =
                branches.data.map(b =>
                    `<option value="${b.id}" ${b.id == {{ $product->branch_id }} ? 'selected':''}>${b.name}</option>`
                ).join('');

            document.querySelector('#category_id').innerHTML =
                categories.data.map(c =>
                    `<option value="${c.id}" ${c.id == {{ $product->category_id ?? 'null' }} ? 'selected':''}>${c.name}</option>`
                ).join('');
        }

        document.querySelector('#formEdit').addEventListener('submit', async e => {
            e.preventDefault();

            await axios.put(`/api/products/{{ $product->id }}`, {
                code: document.querySelector('#code').value,
                name: document.querySelector('#name').value,
                image: document.querySelector('#image').value,
                category_id: document.querySelector('#category_id').value,
                description: document.querySelector('#description').value,
                stock: document.querySelector('#stock').value,
                branch_id: document.querySelector('#branch_id').value,
                purchase_price: document.querySelector('#purchase_price').value,
                sale_price: document.querySelector('#sale_price').value,
            });

            window.location.href = "{{ route('product.index') }}";
        });

        loadSelects();
    </script>
@endsection
