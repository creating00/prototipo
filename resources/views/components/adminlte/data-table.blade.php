<div class="card" data-base-url="{{ url()->current() }}">

    {{-- HEADER --}}
    @if (($title !== '' && $title !== 'DataTable') || isset($headerButtons))
        <div class="card-header d-flex justify-content-between align-items-center">
            @if ($title !== '' && $title !== 'DataTable')
                <h3 class="card-title mb-0">{{ $title }}</h3>
            @endif

            @isset($headerButtons)
                <div class="card-tools ms-auto">
                    {{ $headerButtons }}
                </div>
            @endisset
        </div>
    @endif

    {{-- BODY EXTRA --}}
    @isset($body)
        <div class="card-body border-bottom">
            {{ $body }}
        </div>
    @endisset

    {{-- TABLA --}}
    <div class="card-body">
        <div @class(['table-responsive' => $responsive])>
            <table id="{{ $tableId }}" class="{{ $getTableClass() }}">

                <thead>
                    <tr>

                        {{-- SELECT ALL --}}
                        @if ($selectable ?? false)
                            <th style="width:40px;" class="text-center">
                                <input type="checkbox" class="select-all-checkbox">
                            </th>
                        @endif

                        {{-- HEADERS --}}
                        @foreach ($headers as $header)
                            <th>{{ $header }}</th>
                        @endforeach

                        {{-- ACTIONS --}}
                        @if ($withActions)
                            <th class="text-center">Acciones</th>
                        @endif

                    </tr>
                </thead>

                <tbody>
                    @foreach ($rows as $index => $row)
                        {{-- Aquí aplicamos el método que genera todos los data-* automáticamente --}}
                        <tr data-row-index="{{ $index }}" {!! $getAttributesHtml($index) !!}>

                            {{-- ROW CHECKBOX --}}
                            @if ($selectable ?? false)
                                <td class="text-center">
                                    <input type="checkbox" class="row-checkbox"
                                        value="{{ $getRowData($index)['id'] ?? '' }}">
                                </td>
                            @endif

                            {{-- CELDAS --}}
                            @foreach ($row as $cell)
                                <td>{!! $cell !!}</td>
                            @endforeach

                            {{-- ACTIONS --}}
                            @if ($withActions)
                                <td class="text-center">
                                    {{ $actions ?? $slot }}
                                </td>
                            @endif

                        </tr>
                    @endforeach
                </tbody>

            </table>
        </div>
    </div>

    {{-- FOOTER --}}
    @isset($footer)
        <div class="card-footer">
            {{ $footer }}
        </div>
    @endisset
</div>
