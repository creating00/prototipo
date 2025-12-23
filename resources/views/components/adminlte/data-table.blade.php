<div class="card" data-base-url="{{ url()->current() }}">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">{{ $title }}</h3>

        {{-- Slot para botones del header --}}
        @if (isset($headerButtons))
            <div class="card-tools ms-auto">
                {{ $headerButtons }}
            </div>
        @endif
    </div>

    <div class="card-body">
        <div @class(['table-responsive' => $responsive])>
            <table id="{{ $tableId }}" class="{{ $getTableClass() }}">
                <thead>
                    <tr>
                        @foreach ($headers as $header)
                            <th>{{ $header }}</th>
                        @endforeach
                        @if ($withActions)
                            <th class="text-center">Acciones</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $index => $row)
                        <tr data-row-index="{{ $index }}"
                            @foreach ($getRowData($index) as $key => $value)
                                data-{{ $key }}="{{ $value }}" @endforeach>

                            <!-- Mostrar solo las celdas visibles -->
                            @foreach ($row as $cell)
                                <td>{!! $cell !!}</td>
                            @endforeach

                            @if ($withActions)
                                <td class="text-center">
                                    {{ $slot }}
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
