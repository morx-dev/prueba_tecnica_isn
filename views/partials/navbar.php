<?php
/**
 * Partial reutilizable. Se incluye al inicio del <body> en cada vista protegida.
 * Requiere que la sesion ya este iniciada (session_start() esta en bootstrap.php)
 * y que la clase Auth ya este disponible via autoload.
 */
$usuarioActual = Auth::usuarioActual();
?>
<nav class="navbar">
    <div class="navbar-marca">Hogares ISN &mdash; Inventario de Herramientas</div>

    <ul class="navbar-enlaces">
        <li><a href="/taller/index">Talleres</a></li>
        <li><a href="/mecanico/index">Mecánicos</a></li>
        <?php if ($usuarioActual !== null && (int)$usuarioActual['id_rol'] === 1): ?>
            <li><a href="/usuario/index">Usuarios</a></li>
        <?php endif; ?>
    </ul>

    <?php if ($usuarioActual !== null): ?>
        <div class="navbar-usuario">
            <span>Hola, <?= htmlspecialchars($usuarioActual['nombre_completo']) ?></span>
            <a href="/usuario/logout">Cerrar sesión</a>
        </div>
    <?php endif; ?>
</nav>