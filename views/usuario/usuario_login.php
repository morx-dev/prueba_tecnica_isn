<?php
/**
 * Vista de autenticación y formulario de inicio de sesión del sistema.
 * 
 * Variable disponible aqui (pasada por UsuarioController::login()):
 * @var string|null $error Mensaje de error de autenticación en caso de fallo, o null si no hay error.
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar sesión - Hogares ISN</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>

<h1>Iniciar sesión</h1>

<?php 
// Muestra el contenedor de alerta si existe un mensaje de error en la autenticación
if ($error !== null): 
?>
    <div class="alerta alerta-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="POST" action="/usuario/login">
    <label for="usuario">Usuario</label>
    <!-- Campo de texto para el identificador de usuario con enfoque automático -->
    <input type="text" id="usuario" name="usuario" required autofocus>

    <label for="password">Contraseña</label>
    <!-- Campo seguro para la contraseña de acceso -->
    <input type="password" id="password" name="password" required>

    <!-- Botón de envío para procesar la credencial de inicio de sesión -->
    <button type="submit" class="boton boton-primario">Entrar</button>
</form>

<?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>