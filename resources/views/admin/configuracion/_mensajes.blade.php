{{--
    Avisos compartidos por las vistas de configuración.

    Qué hace: muestra el mensaje de éxito y el primer error de validación para
    que todas las secciones de configuración se comporten igual.
    Conecta con: Admin\ConfiguracionController y admin/configuracion/*.blade.php.
--}}
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show py-2">
    <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show py-2">
    <i class="bi bi-exclamation-circle me-1"></i>{{ $errors->first() }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
