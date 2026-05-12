<x-adminlte.dynamic-modal modalId="attachProductModal" title="Asociar producto a {{ $provider->business_name }}"
    formId="attachProductForm" btnSaveId="btnAttachProduct" :route="route('providers.products.store', $provider)" :form-view="'admin.provider.partials.provider-product._form'" :form-data="[
        'provider' => $provider,
        'products' => $products,
        'providerProduct' => null,
    ]" />
