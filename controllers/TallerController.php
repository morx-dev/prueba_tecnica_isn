<?php
declare(strict_types=1);

class TallerController
{
    private TallerModel $modelo;

    public function __construct()
    {
        // Ninguna accion de este controlador es publica: exige sesion activa.
        Auth::requerirLogin();

        $database = new Database();
        $db = $database->getConnection();
        $this->modelo = new TallerModel($db);
    }

    // Listar todos los talleres (accion por defecto)
    public function index(): void
    {
        $talleres = $this->modelo->obtenerTodos();
        $mensaje = $this->obtenerMensajeFlash();

        require_once __DIR__ . '/../views/taller/taller_index.php';
    }

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

    // Desactivar un taller (borrado logico, preserva el historial)
    public function desactivar(string $id): void
    {
        Auth::requerirRol([1]);
        $this->modelo->cambiarEstado((int)$id, 0);
        $this->guardarMensajeFlash('Taller desactivado.', 'exito');
        header('Location: /taller/index');
        exit;
    }

    // Reactivar un taller previamente desactivado
    public function activar(string $id): void
    {
        Auth::requerirRol([1]);
        $this->modelo->cambiarEstado((int)$id, 1);
        $this->guardarMensajeFlash('Taller activado.', 'exito');
        header('Location: /taller/index');
        exit;
    }

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

    private function guardarMensajeFlash(string $texto, string $tipo): void
    {
        $_SESSION['flash'] = ['texto' => $texto, 'tipo' => $tipo];
    }

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