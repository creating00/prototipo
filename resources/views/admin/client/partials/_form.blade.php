<form id="clientForm">

    <div class="mb-3">
        <label>Documento</label>
        <input type="text" id="document" class="form-control" value="{{ $client->document ?? '' }}">
    </div>

    <div class="mb-3">
        <label>Nombre Completo</label>
        <input type="text" id="full_name" class="form-control" value="{{ $client->full_name ?? '' }}">
    </div>

    <div class="mb-3">
        <label>Teléfono</label>
        <input type="text" id="phone" class="form-control" value="{{ $client->phone ?? '' }}">
    </div>

    <div class="mb-3">
        <label>Dirección</label>
        <input type="text" id="address" class="form-control" value="{{ $client->address ?? '' }}">
    </div>

    <button class="btn btn-primary">Guardar</button>
    <a href="{{ route('client.index') }}" class="btn btn-secondary">Volver</a>

</form>

@section('js')
    <script>
        document.querySelector('#clientForm').addEventListener('submit', async e => {
            e.preventDefault();

            const payload = {
                document: document.querySelector('#document').value,
                full_name: document.querySelector('#full_name').value,
                phone: document.querySelector('#phone').value,
                address: document.querySelector('#address').value,
            };

            @if ($mode === 'create')
                await axios.post('{{ $action }}', payload);
            @else
                await axios.put('{{ $action }}', payload);
            @endif

            window.location.href = "{{ route('client.index') }}";
        });
    </script>
@endsection
