# Módulo DECE — Plan de sprints

Departamento de Consejería Estudiantil (DECE) del Sistema de Gestión Escolar.
Arquitectura MVC de Laravel, principios SOLID y pruebas automatizadas.

## Alcance funcional

| Requerimiento | Sprint |
|---|---|
| Historial psicoeducativo y disciplinario (modo lectura) | 2 |
| Agenda de citas con representantes y notificaciones | 4 |
| Actas y acuerdos de compromiso con firma digital | 5 |
| Atenciones individuales (motivo, relatoría, acuerdos, evidencias) | 3 |
| Repositorio de evidencias y respaldos | 3 |
| Derivaciones internas y externas con seguimiento | 7 |
| Gestión NEE, adaptaciones curriculares y diagnósticos MSP | 6 |
| Talleres colectivos y control de asistencia | 7 |
| Dashboard, alertas tempranas y reportes para dirección | 2 y 8 |
| Catálogo oficial de diagnósticos (CIE-10 / MSP) | 6 |
| Seguridad, confidencialidad y bitácora inalterable | 1 |

## Roles

- `DECE`: gestión completa del módulo.
- `ADMIN`: gestión completa (supervisión institucional).
- `RECTORA` y `VICERRECTORA`: dashboard y reportes agregados (sin relatorías confidenciales).
- `DOCENTE` e `INSPECCION`: pueden derivar formalmente un caso al DECE.
- Resto de roles: sin acceso.

## Sprint 1 — Fundaciones, seguridad y catálogos

**Objetivo:** dejar la base de datos, modelos, rutas y navegación listos, con
acceso restringido y trazabilidad desde el primer registro.

- Migraciones de las 11 tablas del módulo.
- Modelos Eloquent con constantes de dominio, relaciones y scopes.
- Bitácora `dece_auditorias` inmutable (bloqueo de update/delete en el ORM).
- Rutas `dece.*` protegidas por rol.
- Entrada en el menú lateral solo para DECE y autoridades.
- Catálogo inicial de diagnósticos CIE-10 / MSP.
- Pruebas de acceso por rol y de inmutabilidad de la bitácora.

**Criterio de aceptación:** un usuario no autorizado recibe 403 y cada acción
sensible deja un asiento con usuario, fecha, IP y user-agent.

## Sprint 2 — Dashboard y expediente integral

**Objetivo:** que el profesional entienda el contexto del estudiante antes de
cualquier intervención.

- Indicadores: atenciones del mes, citas próximas, acuerdos activos, NEE.
- Próximas citas y últimas atenciones en el panel.
- Alertas de focos rojos (citas, atenciones y observaciones acumuladas).
- Buscador de estudiantes por nombre, cédula o código.
- Expediente con notas, asistencia, observaciones de inspección, atenciones,
  acuerdos, citas, NEE y derivaciones.
- Pruebas del dashboard, del buscador y del expediente.

**Criterio de aceptación:** Rectorado ve métricas agregadas y el expediente sin
las relatorías confidenciales; DECE ve el expediente completo.

## Sprint 3 — Atenciones individuales y evidencias

**Objetivo:** registrar la ficha de entrevista con su respaldo documental.

- Alta de atención: estudiante, motivo, fecha, historia narrada y acuerdos.
- Repositorio de evidencias PDF/JPG/PNG (máximo 10 MB) en almacenamiento privado.
- Cierre de la atención y bitácora de consulta.
- Ficha de atención en PDF.
- Pruebas de validación, carga de archivos, auditoría y PDF.

**Criterio de aceptación:** una atención no puede guardarse sin motivo, fecha,
relatoría y estudiante; las evidencias quedan asociadas y auditadas.

## Sprint 4 — Agenda de citas y notificaciones

**Objetivo:** programar entrevistas con representantes y controlar asistencia.

- Agenda con filtros por fecha, estado y profesional.
- Alta de cita con estudiante, representante, fecha, duración y motivo.
- Notificación automática al representante en su bandeja de mensajes.
- Registro de asistencia, inasistencia o cancelación con observación.
- Pruebas de alta, notificación y registro de asistencia.

**Criterio de aceptación:** cada cita notifica al representante y su resultado
queda auditado; no se puede registrar dos veces la misma cita.

## Sprint 5 — Acuerdos, actas y firma digital

**Objetivo:** formalizar compromisos de mejora y controlar su cumplimiento.

- Alta de acuerdos conductuales, académicos y de convivencia.
- Compromisos del estudiante y del representante.
- Firma digital por rol (estudiante, representante, profesional) con sello de
  tiempo, IP y user-agent.
- Seguimientos de cumplimiento y estados automáticos de vencimiento.
- Acta de acuerdo en PDF.
- Pruebas de firma, seguimiento y PDF.

**Criterio de aceptación:** el acta solo se emite cuando las tres firmas están
registradas y cada cambio queda en la bitácora.

## Sprint 6 — NEE, adaptaciones y catálogo MSP

**Objetivo:** gestionar necesidades educativas específicas con respaldo oficial.

- Catálogo de diagnósticos CIE-10 / MSP (crear, editar, activar/desactivar).
- Registro NEE interno o externo, con grado de adaptación 1, 2 o 3.
- Diagnóstico, profesional evaluador y fecha de evaluación.
- Informe psicopedagógico adjunto.
- Pruebas de catálogo, registro interno/externo y adjuntos.

**Criterio de aceptación:** un registro interno exige estudiante del padrón; uno
externo exige nombre y curso de referencia; el diagnóstico sale del catálogo.

## Sprint 7 — Derivaciones y talleres

**Objetivo:** cubrir las rutas internas/externas y la prevención colectiva.

- Derivación interna desde docentes e inspección.
- Derivación externa a MSP, DINAPEN o Juntas de Protección con oficio y plazo.
- Seguimiento de estado, respuesta y vencimiento.
- Talleres de prevención, orientación vocacional y escuela de padres.
- Control de asistencia a talleres.
- Pruebas de derivación, seguimiento y asistencia a talleres.

**Criterio de aceptación:** una derivación vencida se resalta; un taller permite
marcar asistencia de estudiantes o representantes.

## Sprint 8 — Reportes, cierre y calidad

**Objetivo:** entregar indicadores a la dirección y cerrar con calidad.

- Reporte por rango de fechas: motivos frecuentes, cursos con más reportes,
  atenciones mensuales y focos rojos.
- Exportación del reporte en PDF.
- Revisión completa de pruebas, Pint y verificación en navegador.
- Documentación de sprints y criterios de aceptación.

**Criterio de aceptación:** suite completa en verde, Pint sin cambios pendientes
y módulo navegable en escritorio y móvil.

## Estado de implementación

| Sprint | Estado |
|---|---|
| 1 — Fundaciones, seguridad y catálogos | Completado |
| 2 — Dashboard y expediente integral | Completado |
| 3 — Atenciones individuales y evidencias | Completado |
| 4 — Agenda de citas y notificaciones | Completado |
| 5 — Acuerdos, actas y firma digital | Completado |
| 6 — NEE, adaptaciones y catálogo MSP | Completado |
| 7 — Derivaciones y talleres | Completado |
| 8 — Reportes, cierre y calidad | Completado |

**Verificación:** `php artisan test` en verde (376 pruebas, 1733 aserciones),
incluidas 20 pruebas del módulo DECE en `tests/Feature/Dece/`; Pint sin cambios
pendientes; rutas `dece.*` y PDFs verificados en el navegador.
