<?php
/**
 * Variables disponibles aqui (pasadas por MecanicoController::index()):
 * array $mecanicos
 * array|null $mensaje
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

<?php require __DIR__ . '/../partials/navbar.php'; ?>

<h1>Mecánicos</h1>

<?php if ($mensaje !== null): ?>
    <div class="alerta alerta-<?= htmlspecialchars($mensaje['tipo']) ?>">
        <?= htmlspecialchars($mensaje['texto']) ?>
    </div>
<?php endif; ?>

<a href="/mecanico/crear" class="boton boton-primario">+ Nuevo mecánico</a>

<table class="tabla">
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
                        <a href="/mecanico/editar/<?= (int)$mecanico['id_mecanico'] ?>">Editar</a>
                        <?php if ((int)$mecanico['estado'] === 1): ?>
                            <a href="/mecanico/desactivar/<?= (int)$mecanico['id_mecanico'] ?>"
                               onclick="return confirm('¿Desactivar este mecánico?');">Desactivar</a>
                        <?php else: ?>
                            <a href="/mecanico/activar/<?= (int)$mecanico['id_mecanico'] ?>">Activar</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

</body>
</html>