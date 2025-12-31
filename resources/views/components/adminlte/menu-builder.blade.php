{{-- resources/views/components/admin-lte/menu-builder.blade.php --}}
@foreach ($items as $item)
    @if ($item['type'] === 'header')
        <x-adminlte.menu-header :title="$item['title']" />
    @elseif($item['type'] === 'single')
        <x-adminlte.single-menu-item :href="$item['href']" :icon="$item['icon']" :label="$item['label']" :active="$item['active'] ?? false" />
    @elseif($item['type'] === 'submenu')
        <x-adminlte.menu-with-subitems :icon="$item['icon']" :label="$item['label']" :active="$item['active'] ?? false" :menuOpen="$item['menuOpen'] ?? false"
            :badge="$item['badge'] ?? null">

            @foreach ($item['subitems'] as $subitem)
                <x-adminlte.sub-menu-item :href="$subitem['href']" :label="$subitem['label']" :active="$subitem['active'] ?? false" />
            @endforeach
        </x-adminlte.menu-with-subitems>
    @elseif($item['type'] === 'nested')
        <x-adminlte.nested-menu :icon="$item['icon']" :label="$item['label']" :active="$item['active'] ?? false">

            @foreach ($item['children'] as $child)
                @if (isset($child['children']))
                    {{-- Sub-nested menu --}}
                    <x-adminlte.nested-menu :icon="$child['icon']" :label="$child['label']">
                        @foreach ($child['children'] as $grandchild)
                            <x-adminlte.sub-menu-item :href="$grandchild['href']" :label="$grandchild['label']" />
                        @endforeach
                    </x-adminlte.nested-menu>
                @else
                    {{-- Simple subitem --}}
                    <x-adminlte.sub-menu-item :href="$child['href']" :label="$child['label']" :active="$child['active'] ?? false" />
                @endif
            @endforeach
        </x-adminlte.nested-menu>
    @endif
@endforeach
