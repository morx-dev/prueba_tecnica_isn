<?php
declare(strict_types=1);

/**
 * Controlador para la gestión de usuarios, encargándose de la autenticación, 
 * control de sesiones, listados, creación y cambios de estado (activo/inactivo).
 */
class UsuarioController
{
    private UsuarioModel $usuarioModel;

    /**
     * Inicializa la conexión a la base de datos y carga el modelo de usuarios.
     */
    public function __construct()
    {
        $database = new Database();
        $db = $database->getConnection();
        $this->usuarioModel = new UsuarioModel($db);
    }

    /**
     * Formulario de login y su procesamiento. Esta es la unica accion publica del sistema.
     */
    public function login(): void
    {
        // Si ya esta logueado, no tiene sentido que vea el login de nuevo
        if (Auth::estaLogueado()) {
            header('Location: /taller/index');
            exit;
        }

        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $usuario = trim($_POST['usuario'] ?? '');
            $passwordPlano = $_POST['password'] ?? '';

            $datosUsuario = $this->usuarioModel->obtenerPorUsuario($usuario);

            if ($datosUsuario !== null && password_verify($passwordPlano, $datosUsuario['password_hash'])) {
                // Regenerar el id de sesion previene fijacion de sesion (session fixation)
                session_regenerate_id(true);
                $_SESSION['id_usuario'] = $datosUsuario['id_usuario'];
                $_SESSION['nombre_completo'] = $datosUsuario['nombre_completo'];
                $_SESSION['id_rol'] = $datosUsuario['id_rol'];
                $_SESSION['id_taller'] = $datosUsuario['id_taller'];

                header('Location: /taller/index');
                exit;
            }

            $error = 'Usuario o contraseña incorrectos.';
        }

        require_once __DIR__ . '/../views/usuario/usuario_login.php';
    }

    /**
     * Cierra la sesión activa del usuario y redirige a la vista de autenticación.
     */
    public function logout(): void
    {
        $_SESSION = [];
        session_destroy();
        header('Location: /usuario/login');
        exit;
    }

    /**
     * Listado general de usuarios (restringido únicamente al rol de Administrador, id_rol = 1).
     */
    public function index(): void
    {
        Auth::requerirRol([1]);

        $usuarios = $this->usuarioModel->obtenerTodos();
        $mensaje = $this->obtenerMensajeFlash();

        require_once __DIR__ . '/../views/usuario/usuario_index.php';
    }

    /**
     * Muestra el formulario de creación de usuarios y procesa su registro (restringido al Administrador).
     */
    public function crear(): void
    {
        Auth::requerirRol([1]);

        $errores = [];
        $talleres = (new TallerModel((new Database())->getConnection()))->obtenerTodos();
        $roles = $this->usuarioModel->obtenerRoles();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombreCompleto = trim($_POST['nombre_completo'] ?? '');
            $usuario = trim($_POST['usuario'] ?? '');
            $passwordPlano = $_POST['password'] ?? '';
            $idRol = (int)($_POST['id_rol'] ?? 0);
            $idTaller = !empty($_POST['id_taller']) ? (int)$_POST['id_taller'] : null;

            $errores = $this->validar($nombreCompleto, $usuario, $passwordPlano, $idRol);

            if (empty($errores)) {
                $this->usuarioModel->crear($nombreCompleto, $usuario, $passwordPlano, $idRol, $idTaller);
                $this->guardarMensajeFlash('Usuario creado correctamente.', 'exito');
                header('Location: /usuario/index');
                exit;
            }
        }

        require_once __DIR__ . '/../views/usuario/usuario_formulario.php';
    }

    /**
     * Desactiva lógicamente a un usuario existente preservando su historial (restringido al Administrador).
     * 
     * @param string $id Identificador del usuario a desactivar.
     */
    public function desactivar(string $id): void
    {
        Auth::requerirRol([1]);
        $this->usuarioModel->cambiarEstado((int)$id, 0);
        $this->guardarMensajeFlash('Usuario desactivado.', 'exito');
        header('Location: /usuario/index');
        exit;
    }

    /**
     * Reactiva a un usuario previamente desactivado (restringido al Administrador).
     * 
     * @param string $id Identificador del usuario a activar.
     */
    public function activar(string $id): void
    {
        Auth::requerirRol([1]);
        $this->usuarioModel->cambiarEstado((int)$id, 1);
        $this->guardarMensajeFlash('Usuario activado.', 'exito');
        header('Location: /usuario/index');
        exit;
    }

    /**
     * Valida los campos ingresados en el formulario de creación de usuarios.
     * 
     * @param string $nombreCompleto Nombre completo proporcionado.
     * @param string $usuario Nombre de usuario único.
     * @param string $passwordPlano Contraseña en texto plano.
     * @param int $idRol Identificador numérico del rol.
     * @return array Lista de errores encontrados durante la validación.
     */
    private function validar(string $nombreCompleto, string $usuario, string $passwordPlano, int $idRol): array
    {
        $errores = [];

        if ($nombreCompleto === '') {
            $errores[] = 'El nombre completo es obligatorio.';
        }

        if ($usuario === '') {
            $errores[] = 'El nombre de usuario es obligatorio.';
        } elseif ($this->usuarioModel->existeUsuario($usuario)) {
            $errores[] = 'Ese nombre de usuario ya está en uso.';
        }

        if (strlen($passwordPlano) < 6) {
            $errores[] = 'La contraseña debe tener al menos 6 caracteres.';
        }

        if ($idRol <= 0) {
            $errores[] = 'Debes seleccionar un rol.';
        }

        return $errores;
    }

    // --- Mensajes flash (mismo patron que TallerController, copialo igual en los proximos modulos) ---

    /**
     * Guarda un mensaje temporal en la sesión para mostrarlo después de una redirección.
     * 
     * @param string $texto Texto descriptivo del mensaje.
     * @param string $tipo Tipo de alerta (ej. 'exito', 'error').
     */
    private function guardarMensajeFlash(string $texto, string $tipo): void
    {
        $_SESSION['flash'] = ['texto' => $texto, 'tipo' => $tipo];
    }

    /**
     * Recupera y elimina el mensaje flash actual de la sesión.
     * 
     * @return array|null Arreglo asociativo con el texto y tipo, o null si no hay mensajes pendientes.
     */
    private function obtenerMensajeFlash(): ?array
    {
        if (!isset($_SESSION['flash'])) {
            return null;
        }
        $mensaje = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $mensaje;
    }
}