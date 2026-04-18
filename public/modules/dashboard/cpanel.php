<!DOCTYPE html>
<html lang="es">
<?php include __DIR__ . '/partials/head.php'; ?>
<body>

<?php include __DIR__ . '/partials/header.php'; ?>

<div class="main-container">

    <?php include __DIR__ . '/partials/sidebar.php'; ?>

    <main class="main-content">
        <?php include __DIR__ . '/partials/sections/dashboard.php'; ?>
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

<script src="/assets/js/dashboard/cpanel-script.js"></script>
</body>
</html>
