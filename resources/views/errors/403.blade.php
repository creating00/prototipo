@extends('layouts.app')

@section('page-title', 'Acceso denegado')

@section('content')
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card card-danger mt-5">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-ban me-2"></i>
                            Acceso no autorizado
                        </h3>
                    </div>

                    <div class="card-body text-center">
                        <h1 class="display-4 text-danger">403</h1>

                        <p class="lead mt-3">
                            No tienes permisos para acceder a esta sección.
                        </p>

                        <p class="text-muted">
                            Si crees que esto es un error, contacta al administrador del sistema.
                        </p>

                        <a href="{{ url()->previous() }}" class="btn btn-secondary mt-3">
                            Volver
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
