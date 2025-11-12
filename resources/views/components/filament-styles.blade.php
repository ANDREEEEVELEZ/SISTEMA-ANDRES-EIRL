<style>
/* FIX CRÍTICO EMERGENCIA - CSS DIRECTO */
.fi-sidebar {
    background-color: #1E4E8C !important;
}

.fi-sidebar-item-icon {
    color: #cfe9ff !important;
}

.fi-sidebar-item-label,
.fi-sidebar-item a {
    color: white !important;
}

.fi-sidebar-item a:hover {
    background-color: #153d6b !important;
}

.fi-sidebar-item a:hover .fi-sidebar-item-label {
    color: #ffffff !important;
    font-weight: 700 !important;
}

.fi-sidebar-item-active a {
    background-color: #0f2e55 !important;
    font-weight: 700 !important;
}

.fi-sidebar-item-active .fi-sidebar-item-icon,
.fi-sidebar-item-active .fi-sidebar-item-label {
    color: #ffffff !important;
}

.fi-sidebar-item-active .fi-sidebar-item-icon {
    color: white !important;
}

/* SVG FIX CRÍTICO */
svg {
    max-width: 1.25rem !important;
    max-height: 1.25rem !important;
    width: 1.25rem !important;
    height: 1.25rem !important;
}

.fi-icon svg,
.fi-sidebar-item-icon svg,
.fi-topbar-item svg,
.fi-btn svg,
.fi-badge svg {
    width: 1.25rem !important;
    height: 1.25rem !important;
    max-width: 1.25rem !important;
    max-height: 1.25rem !important;
}

/* FIX CONTENIDO DISTORSIONADO */
.fi-main-ctn,
.fi-page {
    width: auto !important;
    max-width: none !important;
}

/* Excepciones para logos */
.fi-logo svg,
.fi-brand svg,
img {
    max-width: none !important;
    max-height: none !important;
    width: auto !important;
    height: auto !important;
}

/* BOTONES - Color del sidebar con texto blanco */
.fi-btn-primary,
.fi-btn[type="submit"],
button[type="submit"],
.filament-button,
.fi-ac-btn-action {
    background-color: #1E4E8C !important;
    color: white !important;
    border-color: #1E4E8C !important;
}

.fi-btn-primary:hover,
.fi-btn[type="submit"]:hover,
button[type="submit"]:hover,
.filament-button:hover,
.fi-ac-btn-action:hover {
    background-color: #153d6b !important;
    border-color: #153d6b !important;
}

.fi-btn span,
button span,
.filament-button span {
    color: white !important;
}

/* TABLAS - Reducir tamaño de fuente */
.fi-table td,
.fi-table th,
.fi-table tbody,
.fi-ta-text {
    font-size: 0.875rem !important;
    line-height: 1.25rem !important;
}

/* FIX BARRA BLANCA AL SELECCIONAR MÓDULO */
.fi-sidebar-item-active::before,
.fi-sidebar-item-active::after,
.fi-sidebar-item::before,
.fi-sidebar-item::after {
    display: none !important;
}

.fi-sidebar-item-active > *:first-child,
.fi-sidebar-item > *:first-child {
    background: transparent !important;
}

.fi-sidebar-item,
.fi-sidebar-item-active {
    border-top: none !important;
    border-bottom: none !important;
}
</style>

<style>
/* PAGINACIÓN - respaldo inline: números en negro */
.filament-pagination a,
.filament-pagination button,
.fi-pagination a,
.fi-pagination button,
.pagination a,
.pagination button,
.page-link,
.page-item a {
    color: #000 !important;
    background: transparent !important;
}
.filament-pagination .active a,
.pagination .active a,
.page-item.active a {
    color: #000 !important;
    font-weight: 700 !important;
}
</style>
</style>
