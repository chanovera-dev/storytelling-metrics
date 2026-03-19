# Storytelling Manager - WordPress Plugin

**Storytelling Manager** es una solución integral para WordPress diseñada para la gestión de datos críticos, contactos clave y preparación ante storytelling empresariales. Permite registrar información detallada de empresas y sus responsables, visualizar estadísticas avanzadas y generar reportes integrales en PDF.

## Características Principales

- **Formulario de Registro Front-end**: Permite la recopilación de datos de empresas, contactos, niveles de decisión y roles en storytelling mediante un shortcode sencillo.
- **Dashboard de Estadísticas**: Panel administrativo visual con gráficas interactivas (ApexCharts) para analizar la preparación de las empresas (industrias, planes de storytelling, frecuencia de simulacros, etc.).
- **Gestión de Registros**: Interfaz completa en el administrador para ver, editar, eliminar y activar/desactivar registros.
- **Exportación a PDF**: Generación de reportes individuales y globales con formato limpio y profesional.
- **Filtros Avanzados**: Filtrado de registros por industria para una gestión segmentada.

## Instalación

1. Sube la carpeta del plugin `storytelling-manager` al directorio `/wp-content/plugins/`.
2. Activa el plugin desde el menú 'Plugins' en WordPress.
3. El plugin creará automáticamente una página llamada **"Registro Storytelling Manager"** con el formulario listo para usar.
4. Si deseas colocar el formulario en otra página, utiliza el shortcode: `[storytelling_registration_form]`.

## Uso del Dashboard

Accede al menú **Storytelling Manager** en la barra lateral de WordPress para visualizar:
- Progresión de nuevos registros (Mensual vs Acumulado).
- Distribución por Industria.
- Niveles de decisión de los contactos.
- Disponibilidad y existencia de Planes de Storytelling.
- Frecuencia de simulacros y comités formales.

## Estructura del Proyecto

- `storytelling-manager.php`: Archivo principal del plugin y cargador de assets.
- `includes/`:
    - `db-handler.php`: Manejo de la base de datos (Clase `Storytelling_DB`).
    - `form-handler.php`: Lógica del shortcode y procesamiento del formulario.
    - `admin-pages.php`: Interfaz del Dashboard y listado de registros.
    - `pdf-generator.php`: Generación de vistas para impresión PDF.
- `assets/`:
    - `css/style.css`: Estilos para el front-end y el dashboard.
    - `js/admin-dashboard.js`: Lógica de gráficas y acciones AJAX.

## Notas Técnicas

- **Base de Datos**: Crea la tabla personalizada `wp_storytelling_management_data` al activarse.
- **Shortcode**: `[storytelling_registration_form]`
- **Requisitos**: WordPress 5.0+ y PHP 7.4+.

## Licencia

Este proyecto está bajo la licencia **GNU General Public License v2.0 (GPL v2)**. Consulta el archivo [LICENSE](./LICENSE) para más detalles.

---
Desarrollado para la gestión estratégica de clientes.
