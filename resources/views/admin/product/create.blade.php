@extends('adminlte::page')

@section('title', 'Nuevo producto')

@section('content_header')
    <h1>Nuevo Producto</h1>
@endsection

@section('content')

    <form id="formCreate">
        @include('admin.product.partials._form', [
            'product' => null,
            'mode' => 'create',
        ])
    </form>

@endsection

@section('js')
    <script>
        async function loadSelects() {
            const branches = await axios.get('/api/branches');
            const categories = await axios.get('/api/categories');

            document.querySelector('#branch_id').innerHTML =
                branches.data.map(b => `<option value="${b.id}">${b.name}</option>`).join('');

            document.querySelector('#category_id').innerHTML =
                categories.data.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
        }

        document.querySelector('#formCreate').addEventListener('submit', async e => {
            e.preventDefault();

            await axios.post('/api/products', {
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
