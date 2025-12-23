@if ($customerType === 'App\Models\Client')
    @include('admin.sales.partials.sections._form_origin_client')
@else
    @include('admin.sales.partials.sections._form_origin_branch')
@endif
