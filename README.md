# Gestión Escolar

Sistema de gestión académica para instituciones educativas, construido con **Laravel 11**, **Blade** y **SQLite**. Centraliza el flujo del ciclo escolar en portales por rol: el aula (estudiantes y docentes) y, próximamente, la capa administrativa completa (inspección, secretaría, autoridades y administración).

## Estado del proyecto

### Hoy: portal operativo centrado en el aula

- **Portal de estudiante** (rol `ESTUDIANTE`)
  - Dashboard por materia: tarjetas con color, docente y contadores de pendientes/entregadas.
  - **Agendas**: lista de tareas con fecha límite, estado (vencida/entregada) y material adjunto.
  - Botón **"Marcar como entregada"** (POST) que registra la entrega en el pivot `agenda_alumnos` (`entregada_en`) y **reduce el contador de pendientes** del dashboard al instante.
  - Materiales recientes y por materia, descargables.
  - Foros activos de sus clases.
- **Portal de docente** (rol `DOCENTE`): publica agendas, materiales y foros por clase.
- **Portal de representante** (rol `REPRESENTANTE`): observaciones y gestión de citas.
- **Administración parcial** (rol `ADMIN`): pre-registros con aprobación/rechazo, matrículas, planes y personalización (`hero-slides`).

### Futuro: plataforma de ciclo escolar integral

Al completar los módulos restantes, el sistema cubrirá el ciclo administrativo completo:

- **INSPECCIÓN** — supervisión transversal: revisión de agendas/foros de todas las materias, detección de vencidas y actividad docente, reportes de cumplimiento. Se alimentará de los datos de entrega que ya registra el módulo de estudiante.
- **SECRETARÍA** — capa oficial: pre-registros/matrículas, certificados y documentos, validación de expedientes del alumno.
- **AUTORIDADES** — tablero global: KPIs (pendientes, vencidas, entregas por materia), visibilidad completa del centro y apoyo a la toma de decisiones.
- **ADMINISTRADOR** — gobierno del sistema: gestión de usuarios/roles, migraciones y configuración.

## Arquitectura

- **Autenticación y roles**: middleware `auth` + `role:<ROLE>` agrupado por portal en `routes/web.php`.
- **Dominio**:
  - `Alumno` → `Matriculas` → `ClaseDocente` (curso / paralelo / materia / docente)
  - `Agenda` (belongsToMany con `Alumno` vía `agenda_alumnos`, con `entregada_en`)
  - `Foro`, `Material` (con `ArchivoAdjunto` morph), mensajes entre usuarios.
  - Helpers de dominio en el modelo `Agenda`: `estaEntregadaPor(Alumno)` y `marcarEntregada(Alumno)`.
- **Vistas**: Blade por portal (`resources/views/{docente,estudiante,representante,admin,...}`).
- **Base de datos**: SQLite (`database/database.sqlite`) con migraciones versionadas.

## Requisitos

- PHP 8.2+
- Composer
- Laravel 11
- SQLite habilitado

## Instalación

```bash
composer install
cp .env.example .env        # configurar APP_NAME y credenciales
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Visita `http://127.0.0.1:8000` e inicia sesión con un usuario de cada rol para ver los portales.

## Licencia

Proyecto privado de gestión escolar.
