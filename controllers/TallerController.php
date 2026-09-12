<?php
declare(strict_types=1);

/**
 * Clase TallerController
 * Controlador principal para la gestión de talleres bajo el patrón MVC.
 * Coordina las peticiones del usuario, ejecuta las validaciones correspondientes
 * y comunica la interfaz web con la capa de datos (TallerModel).
 */
class TallerController
{
    /** @var TallerModel Instancia del modelo para operaciones de base de datos de talleres */
    private TallerModel $tallerModel;

    /**
     * Constructor del controlador.
     * Inicializa la conexión PDO mediante la clase Database y crea la instancia 
     * del modelo de talleres requerido.
     */
    public function __construct()
    {
        // bootstrap.php ya cargo el autoload antes de que este controlador se instancie,
        // asi que Database y TallerModel se resuelven solas. Nada de require_once aqui.
        $database = new Database();
        $db = $database->getConnection();
        $this->tallerModel = new TallerModel($db);
    }

    /**
     * Listar todos los talleres (accion por defecto).
     * Obtiene el listado completo y mensajes temporales para renderizarlos en la vista general.
     */
    public function index(): void
    {
        $talleres = $this->tallerModel->obtenerTodos();
        $mensaje = $this->obtenerMensajeFlash();

        require_once __DIR__ . '/../views/taller/taller.php';
    }

    /**
     * Mostrar formulario de creacion y procesarlo.
     * Si la petición es POST, valida los campos recibidos y registra el nuevo taller en la base de datos.
     */
    public function crear(): void
    {
        $errores = [];
        $taller = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombre = trim($_POST['nombre'] ?? '');
            $direccion = trim($_POST['direccion'] ?? '');
            $telefono = trim($_POST['telefono'] ?? '') ?: null;

            $errores = $this->validar($nombre, $direccion);

            if (empty($errores)) {
                $this->tallerModel->crear($nombre, $direccion, $telefono);
                $this->guardarMensajeFlash('Taller creado correctamente.', 'exito');
                header('Location: /taller/index');
                exit;
            }

            // Si hubo errores, conservamos lo que el usuario escribio
            $taller = ['nombre' => $nombre, 'direccion' => $direccion, 'telefono' => $telefono];
        }

        $modo = 'crear';
        require_once __DIR__ . '/../views/taller/formulario.php';
    }

    /**
     * Mostrar formulario de edicion y procesarlo.
     * Carga los datos del taller seleccionado por su ID y procesa la actualización vía POST tras validar.
     * 
     * @param string $id Identificador del taller recibido por la ruta.
     */
    public function editar(string $id): void
    {
        $idTaller = (int)$id;
        $taller = $this->tallerModel->obtenerPorId($idTaller);

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
                $this->tallerModel->actualizar($idTaller, $nombre, $direccion, $telefono);
                $this->guardarMensajeFlash('Taller actualizado correctamente.', 'exito');
                header('Location: /taller/index');
                exit;
            }

            $taller = ['id_taller' => $idTaller, 'nombre' => $nombre, 'direccion' => $direccion, 'telefono' => $telefono];
        }

        $modo = 'editar';
        require_once __DIR__ . '/../views/taller/formulario.php';
    }

    /**
     * Desactivar un taller (borrado logico, preserva el historial).
     * Cambia el estado del registro a inactivo (0).
     * 
     * @param string $id Identificador del taller a desactivar.
     */
    public function desactivar(string $id): void
    {
        $this->tallerModel->cambiarEstado((int)$id, 0);
        $this->guardarMensajeFlash('Taller desactivado.', 'exito');
        header('Location: /taller/index');
        exit;
    }

    /**
     * Reactivar un taller previamente desactivado.
     * Cambia el estado del registro a activo (1).
     * 
     * @param string $id Identificador del taller a activar.
     */
    public function activar(string $id): void
    {
        $this->tallerModel->cambiarEstado((int)$id, 1);
        $this->guardarMensajeFlash('Taller activado.', 'exito');
        header('Location: /taller/index');
        exit;
    }

    /**
     * Validaciones basicas del formulario.
     * Comprueba que los campos obligatorios cuenten con información válida.
     * 
     * @param string $nombre Nombre ingresado del taller.
     * @param string $direccion Dirección ingresada del taller.
     * @return array Arreglo con los mensajes de error encontrados.
     */
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
     * Almacena un mensaje temporal en la sesión del usuario.
     * 
     * @param string $texto Contenido del mensaje.
     * @param string $tipo Tipo de alerta (ej. exito, error).
     */
    private function guardarMensajeFlash(string $texto, string $tipo): void
    {
        $_SESSION['flash'] = ['texto' => $texto, 'tipo' => $tipo];
    }

    /**
     * Recupera y elimina el mensaje temporal almacenado en sesión.
     * 
     * @return array|null Datos del mensaje o nulo si no existe.
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