<?php
/**
 * Vista de Listado General de Talleres
 * Renderiza la interfaz principal del módulo de talleres, mostrando un listado
 * tabular con los registros existentes, alertas de mensajes flash provenientes
 * de la sesión, y enlaces de control para crear, editar, activar o desactivar registros.
 */

$talleres = $talleres ?? [];
$mensaje = $mensaje ?? null;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Talleres - Hogares ISN</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>

<?php require __DIR__ . '/../partials/navbar.php'; ?>

<h1>Talleres</h1>

<?php if ($mensaje !== null): ?>
    <div class="alerta alerta-<?= htmlspecialchars($mensaje['tipo']) ?>">
        <?= htmlspecialchars($mensaje['texto']) ?>
    </div>
<?php endif; ?>

<a href="/taller/crear" class="boton boton-primario">+ Nuevo taller</a>

<table class="tabla">
    <thead>
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Dirección</th>
            <th>Teléfono</th>
            <th>Estado</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($talleres)): ?>
            <tr>
                <td colspan="6">No hay talleres registrados todavía.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($talleres as $taller): ?>
                <tr>
                    <td><?= (int)$taller['id_taller'] ?></td>
                    <td><?= htmlspecialchars($taller['nombre']) ?></td>
                    <td><?= htmlspecialchars($taller['direccion']) ?></td>
                    <td><?= htmlspecialchars($taller['telefono'] ?? '-') ?></td>
                    <td>
                        <?php if ((int)$taller['estado'] === 1): ?>
                            <span class="etiqueta etiqueta-activo">Activo</span>
                        <?php else: ?>
                            <span class="etiqueta etiqueta-inactivo">Inactivo</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="/taller/editar/<?= (int)$taller['id_taller'] ?>">Editar</a>
                        <?php if ((int)$taller['estado'] === 1): ?>
                            <a href="/taller/desactivar/<?= (int)$taller['id_taller'] ?>"
                               onclick="return confirm('¿Desactivar este taller?');">Desactivar</a>
                        <?php else: ?>
                            <a href="/taller/activar/<?= (int)$taller['id_taller'] ?>">Activar</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

</body>
</html>