<?php
/**
 * Vista de formulario para el registro de nuevos usuarios en el sistema.
 * 
 * Variables disponibles aquí (pasadas por UsuarioController::crear()):
 * @var array $errores  Lista de mensajes de error de validación pendientes.
 * @var array $roles    Filas de la tabla roles para el selector desplegable.
 * @var array $talleres Filas de la tabla talleres para la asignación opcional.
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nuevo usuario - Hogares ISN</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>

<?php 
// Inclusión de la barra de navegación compartida del sistema
require __DIR__ . '/../partials/navbar.php'; 
?>

<h1>Nuevo usuario</h1>

<?php 
// Renderiza el contenedor de alertas si la lista de errores de validación no está vacía
if (!empty($errores)): 
?>
    <div class="alerta alerta-error">
        <ul>
            <?php foreach ($errores as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST" action="/usuario/crear">
    <label for="nombre_completo">Nombre completo</label>
    <!-- Campo de texto para el nombre completo del usuario con persistencia vía POST -->
    <input type="text" id="nombre_completo" name="nombre_completo"
           value="<?= htmlspecialchars($_POST['nombre_completo'] ?? '') ?>" required>

    <label for="usuario">Usuario</label>
    <!-- Campo para el nombre de usuario (login) con persistencia vía POST -->
    <input type="text" id="usuario" name="usuario"
           value="<?= htmlspecialchars($_POST['usuario'] ?? '') ?>" required>

    <label for="password">Contraseña</label>
    <!-- Campo seguro para la contraseña con restricción de longitud mínima -->
    <input type="password" id="password" name="password" required minlength="6">

    <label for="id_rol">Rol</label>
    <!-- Selector desplegable para asignar el rol correspondiente al usuario -->
    <select id="id_rol" name="id_rol" required>
        <option value="">-- Selecciona un rol --</option>
        <?php foreach ($roles as $rol): ?>
            <option value="<?= (int)$rol['id_rol'] ?>">
                <?= htmlspecialchars($rol['nombre_rol']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label for="id_taller">Taller (opcional, dejar vacío si es Administrador global)</label>
    <!-- Selector opcional de taller asignado -->
    <select id="id_taller" name="id_taller">
        <option value="">-- Sin taller --</option>
        <?php foreach ($talleres as $taller): ?>
            <option value="<?= (int)$taller['id_taller'] ?>">
                <?= htmlspecialchars($taller['nombre']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <!-- Botón de envío para registrar el nuevo usuario -->
    <button type="submit" class="boton boton-primario">Crear usuario</button>
    <a href="/usuario/index" class="boton boton-secundario">Cancelar</a>
</form>

<?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>