<table class="items-table">
    <thead>
        <tr>
            <th style="width: 50%">Descripción</th>
            <th style="width: 15%">Cantidad</th>
            <th style="width: 15%">Precio Unit.</th>
            <th style="width: 20%">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($items as $item)
        <tr>
            <td class="item-description">{{ $item['description'] }}</td>
            <td class="item-quantity">{{ $item['quantity'] }} {{ $item['unit'] }}</td>
            <td>${{ number_format($item['price'], 2, ',', '.') }}</td>
            <td>${{ number_format($item['total'], 2, ',', '.') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>