<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/seguridad.php';
csrfGenerar();
?>
<!DOCTYPE html>
<html lang="es">
<?php include __DIR__ . '/partials/head.php'; ?>
<body>

<?php include __DIR__ . '/partials/header.php'; ?>

<div class="main-container">

    <?php include __DIR__ . '/partials/sidebar.php'; ?>
    <div class="sidebar-backdrop" id="sidebar-backdrop" aria-hidden="true"></div>

    <main class="main-content">
        <?php include __DIR__ . '/partials/sections/panel/dashboard.php'; ?>
        <?php include __DIR__ . '/partials/sections/panel/statistics.php'; ?>
        <?php include __DIR__ . '/partials/sections/avisos.php'; ?>

        <?php include __DIR__ . '/partials/sections/crm/contactos.php'; ?>
        <?php include __DIR__ . '/partials/sections/crm/leads.php'; ?>
        <?php include __DIR__ . '/partials/sections/crm/oportunidades.php'; ?>
        <?php include __DIR__ . '/partials/sections/crm/presupuestos.php'; ?>
        <?php include __DIR__ . '/partials/sections/crm/productos.php'; ?>
        <?php include __DIR__ . '/partials/sections/crm/proveedores.php'; ?>
        <?php include __DIR__ . '/partials/sections/crm/facturas.php'; ?>
        <?php include __DIR__ . '/partials/sections/crm/facturas_recurrentes.php'; ?>

        <?php include __DIR__ . '/partials/sections/archivos/file-manager.php'; ?>
        <?php include __DIR__ . '/partials/sections/archivos/ftp.php'; ?>
        <?php include __DIR__ . '/partials/sections/archivos/databases.php'; ?>
        <?php include __DIR__ . '/partials/sections/archivos/backups.php'; ?>

        <?php include __DIR__ . '/partials/sections/seguridad/ssl.php'; ?>
        <?php include __DIR__ . '/partials/sections/seguridad/security.php'; ?>
        <?php include __DIR__ . '/partials/sections/seguridad/firewall.php'; ?>

        <?php include __DIR__ . '/partials/sections/correo/email.php'; ?>
        <?php include __DIR__ . '/partials/sections/correo/domains.php'; ?>

        <?php include __DIR__ . '/partials/sections/sistema/users.php'; ?>
        <?php include __DIR__ . '/partials/sections/sistema/logs.php'; ?>
        <?php include __DIR__ . '/partials/sections/sistema/modulos.php'; ?>
        <?php include __DIR__ . '/partials/sections/sistema/configuracion.php'; ?>

        <?php include __DIR__ . '/partials/sections/perfil.php'; ?>
    </main>

</div>

<?php $jsDir = __DIR__ . '/../../assets/js/dashboard/'; ?>
<script src="/assets/js/dashboard/cpanel-configuracion.js?v=<?= filemtime($jsDir . 'cpanel-configuracion.js') ?>"></script>
<script src="/assets/js/dashboard/cpanel-notificaciones.js?v=<?= filemtime($jsDir . 'cpanel-notificaciones.js') ?>"></script>
<script src="/assets/js/dashboard/cpanel-core.js?v=<?= filemtime($jsDir . 'cpanel-core.js') ?>"></script>
<script src="/assets/js/dashboard/cpanel-actividades.js?v=<?= filemtime($jsDir . 'cpanel-actividades.js') ?>"></script>
<script src="/assets/js/dashboard/cpanel-contactos.js?v=<?= filemtime($jsDir . 'cpanel-contactos.js') ?>"></script>
<script src="/assets/js/dashboard/cpanel-leads.js?v=<?= filemtime($jsDir . 'cpanel-leads.js') ?>"></script>
<script src="/assets/js/dashboard/cpanel-oportunidades.js?v=<?= filemtime($jsDir . 'cpanel-oportunidades.js') ?>"></script>
<script src="/assets/js/dashboard/cpanel-presupuestos.js?v=<?= filemtime($jsDir . 'cpanel-presupuestos.js') ?>"></script>
<script src="/assets/js/dashboard/cpanel-productos.js?v=<?= filemtime($jsDir . 'cpanel-productos.js') ?>"></script>
<script src="/assets/js/dashboard/cpanel-proveedores.js?v=<?= filemtime($jsDir . 'cpanel-proveedores.js') ?>"></script>
<script src="/assets/js/dashboard/cpanel-facturas.js?v=<?= filemtime($jsDir . 'cpanel-facturas.js') ?>"></script>
<script src="/assets/js/dashboard/cpanel-recurrentes.js?v=<?= filemtime($jsDir . 'cpanel-recurrentes.js') ?>"></script>
<script src="/assets/js/dashboard/cpanel-estadisticas.js?v=<?= filemtime($jsDir . 'cpanel-estadisticas.js') ?>"></script>
<script src="/assets/js/dashboard/cpanel-avisos.js?v=<?= filemtime($jsDir . 'cpanel-avisos.js') ?>"></script>
<script src="/assets/js/dashboard/cpanel-email.js?v=<?= filemtime($jsDir . 'cpanel-email.js') ?>"></script>
<script src="/assets/js/dashboard/cpanel-dominios.js?v=<?= filemtime($jsDir . 'cpanel-dominios.js') ?>"></script>
<?php if ($_SESSION['rol'] === 'administrador'): ?>
<script src="/assets/js/dashboard/cpanel-usuarios.js?v=<?= filemtime($jsDir . 'cpanel-usuarios.js') ?>"></script>
<script src="/assets/js/dashboard/cpanel-smtp.js?v=<?= filemtime($jsDir . 'cpanel-smtp.js') ?>"></script>
<script src="/assets/js/dashboard/cpanel-auditoria.js?v=<?= filemtime($jsDir . 'cpanel-auditoria.js') ?>"></script>
<script src="/assets/js/dashboard/cpanel-modulos.js?v=<?= filemtime($jsDir . 'cpanel-modulos.js') ?>"></script>
<script src="/assets/js/dashboard/cpanel-databases.js?v=<?= filemtime($jsDir . 'cpanel-databases.js') ?>"></script>
<script src="/assets/js/dashboard/cpanel-backups.js?v=<?= filemtime($jsDir . 'cpanel-backups.js') ?>"></script>
<?php endif; ?>
</body>
</html>
