<?php
/**
 * Vista del listado general de usuarios registrados en el sistema.
 * 
 * Variables disponibles aquí (pasadas por UsuarioController::index()):
 * @var array      $usuarios Arreglo con los registros de usuarios, roles y talleres.
 * @var array|null $mensaje  Arreglo opcional con alertas flash de éxito o error.
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Usuarios - Hogares ISN</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>

<?php 
// Inclusión de la barra de navegación compartida del sistema
require __DIR__ . '/../partials/navbar.php'; 
?>

<h1>Usuarios</h1>

<?php 
// Renderiza el mensaje flash si existe una alerta almacenada en la sesión
if ($mensaje !== null): 
?>
    <div class="alerta alerta-<?= htmlspecialchars($mensaje['tipo']) ?>">
        <?= htmlspecialchars($mensaje['texto']) ?>
    </div>
<?php endif; ?>

<!-- Botón de acceso directo para registrar un nuevo usuario en el sistema -->
<a href="/usuario/crear" class="boton boton-primario">+ Nuevo usuario</a>

<table class="tabla">
    <thead>
        <tr>
            <th>ID</th>
            <th>Nombre completo</th>
            <th>Usuario</th>
            <th>Rol</th>
            <th>Taller</th>
            <th>Estado</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($usuarios)): ?>
            <!-- Fila informativa mostrada cuando no existen registros de usuarios -->
            <tr><td colspan="7">No hay usuarios registrados todavía.</td></tr>
        <?php else: ?>
            <?php foreach ($usuarios as $usuario): ?>
                <tr>
                    <td><?= (int)$usuario['id_usuario'] ?></td>
                    <td><?= htmlspecialchars($usuario['nombre_completo']) ?></td>
                    <td><?= htmlspecialchars($usuario['usuario']) ?></td>
                    <td><?= htmlspecialchars($usuario['nombre_rol']) ?></td>
                    <td><?= htmlspecialchars($usuario['nombre_taller'] ?? '—') ?></td>
                    <td>
                        <?php if ((int)$usuario['estado'] === 1): ?>
                            <span class="etiqueta etiqueta-activo">Activo</span>
                        <?php else: ?>
                            <span class="etiqueta etiqueta-inactivo">Inactivo</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ((int)$usuario['estado'] === 1): ?>
                            <!-- Enlace de desactivación con confirmación previa mediante JavaScript -->
                            <a href="/usuario/desactivar/<?= (int)$usuario['id_usuario'] ?>"
                               onclick="return confirm('¿Desactivar este usuario?');">Desactivar</a>
                        <?php else: ?>
                            <!-- Enlace para reactivar el acceso del usuario -->
                            <a href="/usuario/activar/<?= (int)$usuario['id_usuario'] ?>">Activar</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

</body>
</html>