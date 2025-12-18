@extends('layouts.app')

@section('page-title', 'Ejemplo')

@section('content')
    <div class="row">
        @foreach (config('dashboardCards.cards') as $card)
            <div class="col-md-3 col-sm-6">
                <x-admin-lte.small-box :title="$card['title']" :value="$card['value']" :color="$card['color']" :svgPath="$card['svgPath'] ?? ''"
                    :icon="$card['icon'] ?? ''" :viewBox="$card['viewBox']" :url="$card['url']" :description="$card['description'] ?? null" :customBgColor="$card['customBgColor'] ?? null" />
            </div>
        @endforeach

        <x-admin-lte.form action="{{ route('form.test') }}" title="Crear Usuario" submit-text="Guardar Usuario"
            submitting-text="Creando usuario...">
            <x-admin-lte.input name="name" label="Nombre" placeholder="Ingrese el nombre" required />

            <x-admin-lte.input name="email" type="email" label="Email" placeholder="correo@ejemplo.com" required />

            <x-admin-lte.select name="role" label="Rol" :options="[
                'admin' => 'Administrador',
                'user' => 'Usuario',
                'editor' => 'Editor',
            ]" placeholder="Seleccione un rol" required />
        </x-admin-lte.form>

        {{-- Select con búsqueda deshabilitada --}}
        <x-admin-lte.select name="status" label="Estado" :options="['active' => 'Activo', 'inactive' => 'Inactivo']" search-enabled="false" />

        {{-- Select múltiple con datos estáticos --}}
        <x-admin-lte.select name="tags[]" label="Etiquetas" :options="[
            'php' => 'PHP',
            'laravel' => 'Laravel',
            'javascript' => 'JavaScript',
            'vue' => 'Vue.js',
            'react' => 'React',
            'bootstrap' => 'Bootstrap',
        ]" multiple="true"
            placeholder="Selecciona las etiquetas" />

        {{-- Alert básico con auto-close --}}
        <x-admin-lte.alert type="success" message="¡Operación completada con éxito!" />

        {{-- Alert con enlace --}}
        <x-admin-lte.alert type="info" message="Se ha creado un nuevo registro." link="/users"
            linkText="Ver usuarios" />

        {{-- Alert con contenido en slot --}}
        <x-admin-lte.alert type="warning" dismissible="true" autoClose="5000">
            <strong>Advertencia!</strong> Esta acción no se puede deshacer.
            <a href="#" class="alert-link">Más información</a>
        </x-admin-lte.alert>

        {{-- Alert permanente (sin auto-close) --}}
        <x-admin-lte.alert type="danger" message="Error crítico en el sistema" autoClose="false" />

        {{-- Tu componente actual (con iconos) --}}
        <x-admin-lte.input-group id="email" type="email" name="email" label="Email" icon="envelope" />

        {{-- Nuevo: Con texto --}}
        <x-admin-lte.input-group-text id="email" name="email" label="Email" prepend-text="@" append-text=".com" />

        <x-admin-lte.input-group-text id="aaa" name="email" prepend-text="$" />


        {{-- Nuevo: Con ayuda --}}
        <x-admin-lte.input-group-with-help id="vanity-url" name="vanity_url" label="Your vanity URL"
            prepend-text="https://example.com/users/" help-text="Example help text goes outside the input group." />

        {{-- Nuevo: Doble input --}}
        <x-admin-lte.input-group-double first-id="username2" first-name="username2" first-label="Username"
            second-id="server" second-name="server" second-label="Server" separator="@" />

        <x-admin-lte.input-group-double first-id="code" first-name="code" second-id="ext" second-name="ext"
            separator="-" />

        {{-- Alert personalizado --}}
        <x-admin-lte.alert type="primary" dismissible="true" autoClose="3000">
            Bienvenido al sistema, <strong>{{ auth()->user()->name }}</strong>
        </x-admin-lte.alert>

        <x-admin-lte.button-group label="Checkbox toggle group">
            <x-admin-lte.button-check-toggle id="check1" color="success">Opción 1</x-admin-lte.button-check-toggle>
            <x-admin-lte.button-check-toggle id="check2" color="success" checked>Opción 2
                (Marcada)</x-admin-lte.button-check-toggle>
        </x-admin-lte.button-group>

        <x-admin-lte.button-group label="Opciones de Estatus">
            <x-admin-lte.button-radio-toggle id="radio_a" name="status_group" color="info" value="A">
                Opción A (Valor: A)
            </x-admin-lte.button-radio-toggle>

            <x-admin-lte.button-radio-toggle id="radio_b" name="status_group" color="info" value="B" checked>
                Opción B (Seleccionada)
            </x-admin-lte.button-radio-toggle>

            <x-admin-lte.button-radio-toggle id="radio_c" name="status_group" color="info" value="C">
                Opción C (Valor: C)
            </x-admin-lte.button-radio-toggle>
        </x-admin-lte.button-group>

        <div class="card-body">
            <x-admin-lte.button data-bs-toggle="toast" data-bs-target="toastDefault">
                Show default toast
            </x-admin-lte.button>

            <hr />

            <x-admin-lte.button color="primary" data-bs-toggle="toast" data-bs-target="toastPrimary" class="mb-2">
                Show primary toast
            </x-admin-lte.button>

            <!-- Más botones... -->

            <x-admin-lte.toast-container>
                <x-admin-lte.toast id="toastDefault" title="Bootstrap" time="11 mins ago" icon="bi bi-circle">
                    Hello, world! This is a toast message.
                </x-admin-lte.toast>

                <x-admin-lte.toast id="toastPrimary" color="primary" title="Bootstrap" time="11 mins ago"
                    icon="bi bi-circle">
                    Hello, world! This is a toast message.
                </x-admin-lte.toast>

                <!-- Más toasts... -->
            </x-admin-lte.toast-container>
        </div>
    </div>
@endsection
