<footer class="app-footer">
    <!--begin::To the end-->
    <div class="float-end d-none d-sm-inline">
        {{ config('app.name') }} System
        ({{ app()->environment('production') ? 'Producción' : 'Testing' }})
    </div>
    <!--end::To the end-->
    <!--begin::Copyright-->
    <strong>
        Copyright &copy; 2014-2026&nbsp;
        <a href="https://creatingfactory.com/" class="text-decoration-none">CreatingSoft</a>.
    </strong>
    Todos los derechos Rerservados
    <!--end::Copyright-->
</footer>
