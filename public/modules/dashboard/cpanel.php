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

    <main class="main-content">
        <?php include __DIR__ . '/partials/sections/panel/dashboard.php'; ?>
        <?php include __DIR__ . '/partials/sections/panel/statistics.php'; ?>

        <?php include __DIR__ . '/partials/sections/crm/contactos.php'; ?>
        <?php include __DIR__ . '/partials/sections/crm/leads.php'; ?>
        <?php include __DIR__ . '/partials/sections/crm/oportunidades.php'; ?>

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
        <?php include __DIR__ . '/partials/sections/sistema/configuracion.php'; ?>

        <?php include __DIR__ . '/partials/sections/perfil.php'; ?>
    </main>

</div>

<script src="/assets/js/dashboard/cpanel-configuracion.js"></script>
<script src="/assets/js/dashboard/cpanel-core.js"></script>
<script src="/assets/js/dashboard/cpanel-actividades.js"></script>
<script src="/assets/js/dashboard/cpanel-contactos.js"></script>
<script src="/assets/js/dashboard/cpanel-leads.js"></script>
<script src="/assets/js/dashboard/cpanel-oportunidades.js"></script>
<script src="/assets/js/dashboard/cpanel-estadisticas.js"></script>
<script src="/assets/js/dashboard/cpanel-email.js"></script>
<script src="/assets/js/dashboard/cpanel-dominios.js"></script>
<?php if ($_SESSION['rol'] === 'administrador'): ?>
<script src="/assets/js/dashboard/cpanel-usuarios.js"></script>
<script src="/assets/js/dashboard/cpanel-auditoria.js"></script>
<script src="/assets/js/dashboard/cpanel-databases.js"></script>
<script src="/assets/js/dashboard/cpanel-backups.js"></script>
<?php endif; ?>
</body>
</html>
