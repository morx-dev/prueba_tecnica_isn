<?php
/**
 * Vista de listado principal para la gestión de mecánicos.
 * 
 * Variables disponibles (pasadas por MecanicoController::index()):
 * @var array      $mecanicos Listado de registros de mecánicos recuperados de la base de datos.
 * @var array|null $mensaje   Arreglo opcional con notificaciones de éxito o error para mostrar al usuario.
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mecánicos - Hogares ISN</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>

<?php 
// Inclusión de la barra de navegación compartida del sistema
require __DIR__ . '/../partials/navbar.php'; 
?>

<h1>Mecánicos</h1>

<?php 
// Muestra un bloque de alerta dinámico si existe un mensaje flash proveniente del controlador
if ($mensaje !== null): 
?>
    <div class="alerta alerta-<?= htmlspecialchars($mensaje['tipo']) ?>">
        <?= htmlspecialchars($mensaje['texto']) ?>
    </div>
<?php endif; ?>

<!-- Botón de acceso directo para registrar un nuevo mecánico -->
<a href="/mecanico/crear" class="boton boton-primario">+ Nuevo mecánico</a>

<input type="text" class="buscador" data-tabla="tabla-mecanicos" placeholder="Buscar mecánico...">

<table class="tabla" id="tabla-mecanicos">
    <thead>
        <tr>
            <th>ID</th>
            <th>Nombre completo</th>
            <th>Código</th>
            <th>Taller</th>
            <th>Teléfono</th>
            <th>Estado</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($mecanicos)): ?>
            <!-- Fila informativa mostrada cuando el listado de mecánicos está vacío -->
            <tr><td colspan="7">No hay mecánicos registrados todavía.</td></tr>
        <?php else: ?>
            <?php foreach ($mecanicos as $mecanico): ?>
                <tr>
                    <td><?= (int)$mecanico['id_mecanico'] ?></td>
                    <td><?= htmlspecialchars($mecanico['nombre_completo']) ?></td>
                    <td><?= htmlspecialchars($mecanico['codigo_empleado']) ?></td>
                    <td><?= htmlspecialchars($mecanico['nombre_taller']) ?></td>
                    <td><?= htmlspecialchars($mecanico['telefono'] ?? '-') ?></td>
                    <td>
                        <?php if ((int)$mecanico['estado'] === 1): ?>
                            <span class="etiqueta etiqueta-activo">Activo</span>
                        <?php else: ?>
                            <span class="etiqueta etiqueta-inactivo">Inactivo</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <!-- Enlace para redirigir al formulario de edición del mecánico -->
                        <a href="/mecanico/editar/<?= (int)$mecanico['id_mecanico'] ?>">Editar</a>
                        <?php if ((int)$mecanico['estado'] === 1): ?>
                            <!-- Acción para desactivar con confirmación previa vía data-confirm (main.js) -->
                            <a href="/mecanico/desactivar/<?= (int)$mecanico['id_mecanico'] ?>"
                               data-confirm="¿Desactivar este mecánico?">Desactivar</a>
                        <?php else: ?>
                            <!-- Acción para reactivar al mecánico -->
                            <a href="/mecanico/activar/<?= (int)$mecanico['id_mecanico'] ?>">Activar</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>