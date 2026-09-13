<?php
/**
 * Partial reutilizable de la barra de navegación del sistema. 
 * Se incluye al inicio del <body> en cada vista protegida.
 * Requiere que la sesión ya esté iniciada (session_start() está en bootstrap.php)
 * y que la clase Auth ya esté disponible vía autoload.
 */
$usuarioActual = Auth::usuarioActual();
?>
<nav class="navbar">
    <!-- Marca o logotipo principal del sistema con descripción del módulo -->
    <div class="navbar-marca">Hogares ISN &mdash; Inventario de Herramientas</div>

    <!-- Lista de enlaces de navegación principales -->
    <ul class="navbar-enlaces">
        <li><a href="/taller/index">Talleres</a></li>
        <li><a href="/mecanico/index">Mecánicos</a></li>
        <li><a href="/herramienta/index">Herramientas</a></li>
        <li><a href="/obsolescencia/index">Obsolescencia</a></li>
        <?php 
        // Restringe el enlace de gestión de usuarios exclusivamente para administradores (id_rol === 1)
        if ($usuarioActual !== null && (int)$usuarioActual['id_rol'] === 1): 
        ?>
            <li><a href="/usuario/index">Usuarios</a></li>
        <?php endif; ?>
    </ul>

    <?php 
    // Renderiza el bloque de usuario actual y el enlace de cierre de sesión si existe una sesión activa
    if ($usuarioActual !== null): 
    ?>
        <div class="navbar-usuario">
            <span>Hola, <?= htmlspecialchars($usuarioActual['nombre_completo']) ?></span>
            <a href="/usuario/logout">Cerrar sesión</a>
        </div>
    <?php endif; ?>
</nav>