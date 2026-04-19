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
        <?php include __DIR__ . '/partials/sections/dashboard.php'; ?>
        <?php include __DIR__ . '/partials/sections/contactos.php'; ?>
        <?php include __DIR__ . '/partials/sections/leads.php'; ?>
        <?php include __DIR__ . '/partials/sections/oportunidades.php'; ?>
        <?php include __DIR__ . '/partials/sections/statistics.php'; ?>
        <?php include __DIR__ . '/partials/sections/file-manager.php'; ?>
        <?php include __DIR__ . '/partials/sections/ftp.php'; ?>
        <?php include __DIR__ . '/partials/sections/databases.php'; ?>
        <?php include __DIR__ . '/partials/sections/backups.php'; ?>
        <?php include __DIR__ . '/partials/sections/ssl.php'; ?>
        <?php include __DIR__ . '/partials/sections/security.php'; ?>
        <?php include __DIR__ . '/partials/sections/firewall.php'; ?>
        <?php include __DIR__ . '/partials/sections/email.php'; ?>
        <?php include __DIR__ . '/partials/sections/domains.php'; ?>
        <?php include __DIR__ . '/partials/sections/users.php'; ?>
        <?php include __DIR__ . '/partials/sections/logs.php'; ?>
        <?php include __DIR__ . '/partials/sections/perfil.php'; ?>
    </main>

</div>

<script src="/assets/js/dashboard/cpanel-core.js"></script>
<script src="/assets/js/dashboard/cpanel-actividades.js"></script>
<script src="/assets/js/dashboard/cpanel-contactos.js"></script>
<script src="/assets/js/dashboard/cpanel-leads.js"></script>
<script src="/assets/js/dashboard/cpanel-oportunidades.js"></script>
<?php if ($_SESSION['rol'] === 'administrador'): ?>
<script src="/assets/js/dashboard/cpanel-usuarios.js"></script>
<script src="/assets/js/dashboard/cpanel-auditoria.js"></script>
<script src="/assets/js/dashboard/cpanel-databases.js"></script>
<?php endif; ?>
</body>
</html>
