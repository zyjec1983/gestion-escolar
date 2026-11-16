{{--
    Avisos compartidos por las vistas del sitio web.

    Qué hace: muestra el mensaje de éxito y el primer error de validación para
    que todas las secciones del portal se comporten igual.
    Conecta con: Admin\SitioController y admin/sitio/*.blade.php.
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
