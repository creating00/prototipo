@extends('layouts.app')

@section('page-title', 'Ejemplo')

@section('content')
    <div class="row">
        @foreach (config('dashboardCards.cards') as $card)
            <div class="col-md-3 col-sm-6">
                <x-adminlte.small-box :title="$card['title']" :value="$card['value']" :color="$card['color']" :svgPath="$card['svgPath'] ?? ''"
                    :icon="$card['icon'] ?? ''" :viewBox="$card['viewBox']" :url="$card['url']" :description="$card['description'] ?? null" :customBgColor="$card['customBgColor'] ?? null" />
            </div>
        @endforeach

        <x-adminlte.form action="{{ route('form.test') }}" title="Crear Usuario" submit-text="Guardar Usuario"
            submitting-text="Creando usuario...">
            <x-adminlte.input name="name" label="Nombre" placeholder="Ingrese el nombre" required />

            <x-adminlte.input name="email" type="email" label="Email" placeholder="correo@ejemplo.com" required />

            <x-adminlte.select name="role" label="Rol" :options="[
                'admin' => 'Administrador',
                'user' => 'Usuario',
                'editor' => 'Editor',
            ]" placeholder="Seleccione un rol" required />
        </x-adminlte.form>

        {{-- Select con búsqueda deshabilitada --}}
        <x-adminlte.select name="status" label="Estado" :options="['active' => 'Activo', 'inactive' => 'Inactivo']" search-enabled="false" />

        {{-- Select múltiple con datos estáticos --}}
        <x-adminlte.select name="tags[]" label="Etiquetas" :options="[
            'php' => 'PHP',
            'laravel' => 'Laravel',
            'javascript' => 'JavaScript',
            'vue' => 'Vue.js',
            'react' => 'React',
            'bootstrap' => 'Bootstrap',
        ]" multiple="true"
            placeholder="Selecciona las etiquetas" />

        {{-- Alert básico con auto-close --}}
        <x-adminlte.alert type="success" message="¡Operación completada con éxito!" />

        {{-- Alert con enlace --}}
        <x-adminlte.alert type="info" message="Se ha creado un nuevo registro." link="/users"
            linkText="Ver usuarios" />

        {{-- Alert con contenido en slot --}}
        <x-adminlte.alert type="warning" dismissible="true" autoClose="5000">
            <strong>Advertencia!</strong> Esta acción no se puede deshacer.
            <a href="#" class="alert-link">Más información</a>
        </x-adminlte.alert>

        {{-- Alert permanente (sin auto-close) --}}
        <x-adminlte.alert type="danger" message="Error crítico en el sistema" autoClose="false" />

        {{-- Tu componente actual (con iconos) --}}
        <x-adminlte.input-group id="email" type="email" name="email" label="Email" icon="envelope" />

        {{-- Nuevo: Con texto --}}
        <x-adminlte.input-group-text id="email" name="email" label="Email" prepend-text="@" append-text=".com" />

        <x-adminlte.input-group-text id="aaa" name="email" prepend-text="$" />


        {{-- Nuevo: Con ayuda --}}
        <x-adminlte.input-group-with-help id="vanity-url" name="vanity_url" label="Your vanity URL"
            prepend-text="https://example.com/users/" help-text="Example help text goes outside the input group." />

        {{-- Nuevo: Doble input --}}
        <x-adminlte.input-group-double first-id="username2" first-name="username2" first-label="Username"
            second-id="server" second-name="server" second-label="Server" separator="@" />

        <x-adminlte.input-group-double first-id="code" first-name="code" second-id="ext" second-name="ext"
            separator="-" />

        {{-- Alert personalizado --}}
        <x-adminlte.alert type="primary" dismissible="true" autoClose="3000">
            Bienvenido al sistema, <strong>{{ auth()->user()->name }}</strong>
        </x-adminlte.alert>

        <x-adminlte.button-group label="Checkbox toggle group">
            <x-adminlte.button-check-toggle id="check1" color="success">Opción 1</x-adminlte.button-check-toggle>
            <x-adminlte.button-check-toggle id="check2" color="success" checked>Opción 2
                (Marcada)</x-adminlte.button-check-toggle>
        </x-adminlte.button-group>

        <x-adminlte.button-group label="Opciones de Estatus">
            <x-adminlte.button-radio-toggle id="radio_a" name="status_group" color="info" value="A">
                Opción A (Valor: A)
            </x-adminlte.button-radio-toggle>

            <x-adminlte.button-radio-toggle id="radio_b" name="status_group" color="info" value="B" checked>
                Opción B (Seleccionada)
            </x-adminlte.button-radio-toggle>

            <x-adminlte.button-radio-toggle id="radio_c" name="status_group" color="info" value="C">
                Opción C (Valor: C)
            </x-adminlte.button-radio-toggle>
        </x-adminlte.button-group>

        <div class="card-body">
            <x-adminlte.button data-bs-toggle="toast" data-bs-target="toastDefault">
                Show default toast
            </x-adminlte.button>

            <hr />

            <x-adminlte.button color="primary" data-bs-toggle="toast" data-bs-target="toastPrimary" class="mb-2">
                Show primary toast
            </x-adminlte.button>

            <!-- Más botones... -->

            <x-adminlte.toast-container>
                <x-adminlte.toast id="toastDefault" title="Bootstrap" time="11 mins ago" icon="bi bi-circle">
                    Hello, world! This is a toast message.
                </x-adminlte.toast>

                <x-adminlte.toast id="toastPrimary" color="primary" title="Bootstrap" time="11 mins ago"
                    icon="bi bi-circle">
                    Hello, world! This is a toast message.
                </x-adminlte.toast>

                <!-- Más toasts... -->
            </x-adminlte.toast-container>
        </div>
    </div>
@endsection
