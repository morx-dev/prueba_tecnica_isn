<?php
declare(strict_types=1);

/**
 * Controlador para la gestión completa del inventario de herramientas,
 * altas, modificaciones, asignaciones a mecánicos y devoluciones en los talleres.
 */
class HerramientaController
{
    private HerramientaModel $herramientaModel;
    private TallerModel $tallerModel;
    private MecanicoModel $mecanicoModel;
    private AsignacionModel $asignacionModel;

    /**
     * Inicializa el controlador, verifica los permisos de rol (Administrador o Encargado)
     * y establece las instancias de conexión a los modelos requeridos.
     */
    public function __construct()
    {
        Auth::requerirRol([1, 2]);

        $db = (new Database())->getConnection();
        $this->herramientaModel = new HerramientaModel($db);
        $this->tallerModel = new TallerModel($db);
        $this->mecanicoModel = new MecanicoModel($db);
        $this->asignacionModel = new AsignacionModel($db);
    }

    /**
     * Muestra el listado de herramientas según el rol del usuario autenticado:
     * El Administrador visualiza el inventario global; el Encargado solo el de su taller.
     */
    public function index(): void
    {
        if (Auth::esAdministrador()) {
            $herramientas = $this->herramientaModel->obtenerTodas();
        } else {
            $herramientas = $this->herramientaModel->obtenerPorTaller((int)$_SESSION['id_taller']);
        }

        $mensaje = $this->obtenerMensajeFlash();

        require_once __DIR__ . '/../views/herramienta/herramienta_index.php';
    }

    /**
     * Gestiona la creación de un nuevo registro de herramienta en el inventario.
     * Restringe la selección de taller si el usuario es Encargado.
     */
    public function crear(): void
    {
        $errores = [];
        $herramienta = null;

        $tallerFijo = Auth::esAdministrador() ? null : (int)$_SESSION['id_taller'];
        $talleres = $tallerFijo === null
            ? $this->tallerModel->obtenerTodos()
            : array_filter([$this->tallerModel->obtenerPorId($tallerFijo)]);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombre = trim($_POST['nombre'] ?? '');
            $medida = trim($_POST['medida'] ?? '') ?: null;
            $precioCompra = (float)($_POST['precio_compra'] ?? 0);
            $idTaller = $tallerFijo ?? (int)($_POST['id_taller'] ?? 0);
            $fechaIngreso = trim($_POST['fecha_ingreso'] ?? '') ?: date('Y-m-d');

            $errores = $this->validar($nombre, $precioCompra, $idTaller);

            if (empty($errores)) {
                $this->herramientaModel->crear($nombre, $medida, $precioCompra, $idTaller, $fechaIngreso);
                $this->guardarMensajeFlash('Herramienta creada correctamente.', 'exito');
                header('Location: /herramienta/index');
                exit;
            }

            $herramienta = [
                'nombre' => $nombre,
                'medida' => $medida,
                'precio_compra' => $precioCompra,
                'id_taller' => $idTaller,
                'fecha_ingreso' => $fechaIngreso
            ];
        }

        $modo = 'crear';
        require_once __DIR__ . '/../views/herramienta/herramienta_formulario.php';
    }

    /**
     * Actualiza la información de una herramienta existente, validando
     * su existencia y la autorización sobre el taller correspondiente.
     * 
     * @param string $id Identificador único de la herramienta a editar.
     */
    public function editar(string $id): void
    {
        $idHerramienta = (int)$id;
        $herramienta = $this->herramientaModel->obtenerPorId($idHerramienta);

        if ($herramienta === null) {
            $this->guardarMensajeFlash('La herramienta que intentas editar no existe.', 'error');
            header('Location: /herramienta/index');
            exit;
        }

        if (!Auth::perteneceATaller((int)$herramienta['id_taller'])) {
            http_response_code(403);
            die('No tienes permiso para editar herramientas de otro taller.');
        }

        $tallerFijo = Auth::esAdministrador() ? null : (int)$_SESSION['id_taller'];
        $talleres = $tallerFijo === null
            ? $this->tallerModel->obtenerTodos()
            : array_filter([$this->tallerModel->obtenerPorId($tallerFijo)]);

        $errores = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombre = trim($_POST['nombre'] ?? '');
            $medida = trim($_POST['medida'] ?? '') ?: null;
            $precioCompra = (float)($_POST['precio_compra'] ?? 0);
            $idTaller = $tallerFijo ?? (int)($_POST['id_taller'] ?? 0);

            $errores = $this->validar($nombre, $precioCompra, $idTaller);

            if (empty($errores)) {
                $this->herramientaModel->actualizar($idHerramienta, $nombre, $medida, $precioCompra, $idTaller);
                $this->guardarMensajeFlash('Herramienta actualizada correctamente.', 'exito');
                header('Location: /herramienta/index');
                exit;
            }

            $herramienta = [
                'id_herramienta' => $idHerramienta,
                'nombre' => $nombre,
                'medida' => $medida,
                'precio_compra' => $precioCompra,
                'id_taller' => $idTaller,
                'fecha_ingreso' => $herramienta['fecha_ingreso']
            ];
        }

        $modo = 'editar';
        require_once __DIR__ . '/../views/herramienta/herramienta_formulario.php';
    }

    /**
     * Muestra el formulario para elegir a qué mecánico activo del taller se le asigna una herramienta, 
     * y procesa la transacción de asignación correspondiente.
     * 
     * @param string $id Identificador único de la herramienta.
     */
    public function asignar(string $id): void
    {
        $idHerramienta = (int)$id;
        $herramienta = $this->herramientaModel->obtenerPorId($idHerramienta);

        if ($herramienta === null) {
            $this->guardarMensajeFlash('La herramienta no existe.', 'error');
            header('Location: /herramienta/index');
            exit;
        }

        if (!Auth::perteneceATaller((int)$herramienta['id_taller'])) {
            http_response_code(403);
            die('No tienes permiso para asignar herramientas de otro taller.');
        }

        if ($herramienta['estado'] !== 'disponible') {
            $this->guardarMensajeFlash('Esta herramienta no está disponible para asignar.', 'error');
            header('Location: /herramienta/index');
            exit;
        }

        $mecanicos = $this->mecanicoModel->obtenerActivosPorTaller((int)$herramienta['id_taller']);
        $errores = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $idMecanico = (int)($_POST['id_mecanico'] ?? 0);

            if ($idMecanico <= 0) {
                $errores[] = 'Debes seleccionar un mecánico.';
            }

            if (empty($errores)) {
                $idUsuarioActual = (int)$_SESSION['id_usuario'];
                $exito = $this->asignacionModel->asignar($idHerramienta, $idMecanico, $idUsuarioActual);

                if ($exito) {
                    $this->guardarMensajeFlash('Herramienta asignada correctamente.', 'exito');
                } else {
                    $this->guardarMensajeFlash('Ocurrió un error al asignar la herramienta. Intenta de nuevo.', 'error');
                }
                header('Location: /herramienta/index');
                exit;
            }
        }

        require_once __DIR__ . '/../views/herramienta/herramienta_asignar.php';
    }

    /**
     * Cierra la asignación activa actual y regresa el estado de la herramienta a 'disponible'.
     * 
     * @param string $id Identificador único de la herramienta a devolver.
     */
    public function devolver(string $id): void
    {
        $idHerramienta = (int)$id;
        $herramienta = $this->herramientaModel->obtenerPorId($idHerramienta);

        if ($herramienta === null) {
            header('Location: /herramienta/index');
            exit;
        }

        if (!Auth::perteneceATaller((int)$herramienta['id_taller'])) {
            http_response_code(403);
            die('No tienes permiso para devolver herramientas de otro taller.');
        }

        $asignacionActiva = $this->asignacionModel->obtenerActivaPorHerramienta($idHerramienta);

        if ($asignacionActiva === null) {
            $this->guardarMensajeFlash('Esta herramienta no tiene una asignación activa.', 'error');
            header('Location: /herramienta/index');
            exit;
        }

        $exito = $this->asignacionModel->devolver((int)$asignacionActiva['id_asignacion'], $idHerramienta);

        if ($exito) {
            $this->guardarMensajeFlash('Herramienta devuelta correctamente.', 'exito');
        } else {
            $this->guardarMensajeFlash('Ocurrió un error al devolver la herramienta. Intenta de nuevo.', 'error');
        }

        header('Location: /herramienta/index');
        exit;
    }

    /**
     * Valida los campos obligatorios y reglas de negocio para los datos de una herramienta.
     * 
     * @param string $nombre       Nombre descriptivo de la herramienta.
     * @param float  $precioCompra Costo de adquisición.
     * @param int    $idTaller     ID del taller propietario.
     * @return array Arreglo con la lista de errores encontrados.
     */
    private function validar(string $nombre, float $precioCompra, int $idTaller): array
    {
        $errores = [];

        if ($nombre === '') {
            $errores[] = 'El nombre de la herramienta es obligatorio.';
        }

        if ($precioCompra <= 0) {
            $errores[] = 'El precio de compra debe ser mayor a cero.';
        }

        if ($idTaller <= 0) {
            $errores[] = 'Debes seleccionar un taller.';
        }

        return $errores;
    }

    /**
     * Almacena un mensaje flash en la sesión actual para feedback de usuario.
     * 
     * @param string $texto Mensaje descriptivo a mostrar.
     * @param string $tipo  Tipo de alerta ('exito' o 'error').
     */
    private function guardarMensajeFlash(string $texto, string $tipo): void
    {
        $_SESSION['flash'] = ['texto' => $texto, 'tipo' => $tipo];
    }

    /**
     * Obtiene y limpia el mensaje flash almacenado en la sesión.
     * 
     * @return array|null Datos del mensaje o null si no existe.
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