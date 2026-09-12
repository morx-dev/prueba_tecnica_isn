<?php
declare(strict_types=1);

/**
 * Controlador para la gestión y administración de talleres.
 * Controla el listado, creación, actualización, activación y desactivación de talleres,
 * restringiendo el acceso administrativo mediante autenticación de roles.
 */
class TallerController
{
    private TallerModel $modelo;

    /**
     * Inicializa el controlador exigiendo una sesión activa y configurando la conexión al modelo de talleres.
     */
    public function __construct()
    {
        // Ninguna accion de este controlador es publica: exige sesion activa.
        Auth::requerirLogin();

        $database = new Database();
        $db = $database->getConnection();
        $this->modelo = new TallerModel($db);
    }

    /**
     * Lista todos los talleres registrados en el sistema (acción por defecto).
     */
    // Listar todos los talleres (accion por defecto)
    public function index(): void
    {
        $talleres = $this->modelo->obtenerTodos();
        $mensaje = $this->obtenerMensajeFlash();

        require_once __DIR__ . '/../views/taller/taller_index.php';
    }

    /**
     * Muestra y procesa el formulario para la creación de un nuevo taller (exclusivo Administrador).
     */
    // Mostrar formulario de creacion y procesarlo
    public function crear(): void
    {
        Auth::requerirRol([1]);
        $errores = [];
        $taller = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombre = trim($_POST['nombre'] ?? '');
            $direccion = trim($_POST['direccion'] ?? '');
            $telefono = trim($_POST['telefono'] ?? '') ?: null;

            $errores = $this->validar($nombre, $direccion);

            if (empty($errores)) {
                $this->modelo->crear($nombre, $direccion, $telefono);
                $this->guardarMensajeFlash('Taller creado correctamente.', 'exito');
                header('Location: /taller/index');
                exit;
            }

            $taller = ['nombre' => $nombre, 'direccion' => $direccion, 'telefono' => $telefono];
        }

        $modo = 'crear';
        require_once __DIR__ . '/../views/taller/taller_formulario.php';
    }

    /**
     * Muestra y procesa el formulario para la edición de un taller existente (exclusivo Administrador).
     * 
     * @param string $id Identificador único del taller recibido por ruta.
     */
    // Mostrar formulario de edicion y procesarlo
    public function editar(string $id): void
    {
        Auth::requerirRol([1]);
        $idTaller = (int)$id;
        $taller = $this->modelo->obtenerPorId($idTaller);

        if ($taller === null) {
            $this->guardarMensajeFlash('El taller que intentas editar no existe.', 'error');
            header('Location: /taller/index');
            exit;
        }

        $errores = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombre = trim($_POST['nombre'] ?? '');
            $direccion = trim($_POST['direccion'] ?? '');
            $telefono = trim($_POST['telefono'] ?? '') ?: null;

            $errores = $this->validar($nombre, $direccion);

            if (empty($errores)) {
                $this->modelo->actualizar($idTaller, $nombre, $direccion, $telefono);
                $this->guardarMensajeFlash('Taller actualizado correctamente.', 'exito');
                header('Location: /taller/index');
                exit;
            }

            $taller = ['id_taller' => $idTaller, 'nombre' => $nombre, 'direccion' => $direccion, 'telefono' => $telefono];
        }

        $modo = 'editar';
        require_once __DIR__ . '/../views/taller/taller_formulario.php';
    }

    /**
     * Realiza un borrado lógico (desactivación) de un taller, preservando el historial (exclusivo Administrador).
     * 
     * @param string $id Identificador del taller a desactivar.
     */
    public function desactivar(string $id): void
    {
        Auth::requerirRol([1]);
        $idTaller = (int)$id;

        // Validar si el taller tiene mecánicos o herramientas asociadas antes de permitir la desactivación
        if ($this->modelo->tieneRegistrosAsociados($idTaller)) {
            $this->guardarMensajeFlash('No se puede desactivar el taller porque tiene mecánicos o herramientas asignados.', 'error');
            header('Location: /taller/index');
            exit;
        }

        $this->modelo->cambiarEstado($idTaller, 0);
        $this->guardarMensajeFlash('Taller desactivado.', 'exito');
        header('Location: /taller/index');
        exit;
    }

    /**
     * Reactiva un taller que se encontraba previamente desactivado (exclusivo Administrador).
     * 
     * @param string $id Identificador del taller a activar.
     */
    // Reactivar un taller previamente desactivado
    public function activar(string $id): void
    {
        Auth::requerirRol([1]);
        $this->modelo->cambiarEstado((int)$id, 1);
        $this->guardarMensajeFlash('Taller activado.', 'exito');
        header('Location: /taller/index');
        exit;
    }

    /**
     * Valida los campos básicos obligatorios del formulario de taller.
     * 
     * @param string $nombre    Nombre ingresado.
     * @param string $direccion Dirección ingresada.
     * @return array Arreglo con los errores de validación encontrados.
     */
    // Validaciones basicas del formulario
    private function validar(string $nombre, string $direccion): array
    {
        $errores = [];
        if ($nombre === '') {
            $errores[] = 'El nombre del taller es obligatorio.';
        }
        if ($direccion === '') {
            $errores[] = 'La dirección del taller es obligatoria.';
        }
        return $errores;
    }

    // --- Mensajes flash (se muestran una sola vez, tras un redirect) ---

    /**
     * Almacena un mensaje flash en la sesión para notificaciones temporales.
     * 
     * @param string $texto Contenido del mensaje.
     * @param string $tipo  Tipo de alerta (ej. 'exito', 'error').
     */
    private function guardarMensajeFlash(string $texto, string $tipo): void
    {
        $_SESSION['flash'] = ['texto' => $texto, 'tipo' => $tipo];
    }

    /**
     * Recupera y limpia el mensaje flash de la sesión actual.
     * 
     * @return array|null Datos del mensaje flash o null si no existe.
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