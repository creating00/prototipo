{{-- resources/views/components/admin-lte/menu-builder.blade.php --}}
@foreach ($items as $item)
    @if ($item['type'] === 'header')
        <x-admin-lte.menu-header :title="$item['title']" />
    @elseif($item['type'] === 'single')
        <x-admin-lte.single-menu-item :href="$item['href']" :icon="$item['icon']" :label="$item['label']" :active="$item['active'] ?? false" />
    @elseif($item['type'] === 'submenu')
        <x-admin-lte.menu-with-subitems :icon="$item['icon']" :label="$item['label']" :active="$item['active'] ?? false" :menuOpen="$item['menuOpen'] ?? false"
            :badge="$item['badge'] ?? null">

            @foreach ($item['subitems'] as $subitem)
                <x-admin-lte.sub-menu-item :href="$subitem['href']" :label="$subitem['label']" :active="$subitem['active'] ?? false" />
            @endforeach
        </x-admin-lte.menu-with-subitems>
    @elseif($item['type'] === 'nested')
        <x-admin-lte.nested-menu :icon="$item['icon']" :label="$item['label']" :active="$item['active'] ?? false">

            @foreach ($item['children'] as $child)
                @if (isset($child['children']))
                    {{-- Sub-nested menu --}}
                    <x-admin-lte.nested-menu :icon="$child['icon']" :label="$child['label']">
                        @foreach ($child['children'] as $grandchild)
                            <x-admin-lte.sub-menu-item :href="$grandchild['href']" :label="$grandchild['label']" />
                        @endforeach
                    </x-admin-lte.nested-menu>
                @else
                    {{-- Simple subitem --}}
                    <x-admin-lte.sub-menu-item :href="$child['href']" :label="$child['label']" :active="$child['active'] ?? false" />
                @endif
            @endforeach
        </x-admin-lte.nested-menu>
    @endif
@endforeach
