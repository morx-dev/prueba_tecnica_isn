<?php
declare(strict_types=1);

/**
 * Controlador para la gestión integral de mecánicos.
 * Administra el listado, creación, edición, activación y desactivación de mecánicos,
 * aplicando restricciones de seguridad y control de acceso por roles y talleres.
 */
class MecanicoController
{
    private MecanicoModel $modeloMecanico;
    private TallerModel $modeloTaller;

    /**
     * Inicializa el controlador verificando permisos de acceso y cargando los modelos requeridos.
     */
    public function __construct()
    {
        // Solo Administrador (1) y Encargado de Taller (2) pueden entrar a este modulo
        Auth::requerirRol([1, 2]);

        $db = (new Database())->getConnection();
        $this->modeloMecanico = new MecanicoModel($db);
        $this->modeloTaller = new TallerModel($db);
    }

    /**
     * Muestra el listado de mecánicos aplicando filtros según el rol del usuario autenticado.
     */
    public function index(): void
    {
        if (Auth::esAdministrador()) {
            $mecanicos = $this->modeloMecanico->obtenerTodos();
        } else {
            // Un Encargado de Taller solo ve los mecanicos de su propio taller
            $mecanicos = $this->modeloMecanico->obtenerPorTaller((int)$_SESSION['id_taller']);
        }

        $mensaje = $this->obtenerMensajeFlash();

        require_once __DIR__ . '/../views/mecanico/mecanico_index.php';
    }

    /**
     * Procesa la creación de un nuevo mecánico, manejando la visualización del formulario y la persistencia de datos.
     */
    public function crear(): void
    {
        $errores = [];
        $mecanico = null;

        // Si es Encargado de Taller, el taller queda fijo al suyo: no puede elegir otro,
        // ni siquiera manipulando el formulario, porque el valor nunca viene del POST.
        $tallerFijo = Auth::esAdministrador() ? null : (int)$_SESSION['id_taller'];
        $talleres = $tallerFijo === null
            ? $this->modeloTaller->obtenerTodos()
            : array_filter([$this->modeloTaller->obtenerPorId($tallerFijo)]);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombreCompleto = trim($_POST['nombre_completo'] ?? '');
            $codigoEmpleado = trim($_POST['codigo_empleado'] ?? '');
            $idTaller = $tallerFijo ?? (int)($_POST['id_taller'] ?? 0);
            $telefono = trim($_POST['telefono'] ?? '') ?: null;
            $fechaIngreso = trim($_POST['fecha_ingreso'] ?? '') ?: null;

            $errores = $this->validar($nombreCompleto, $codigoEmpleado, $idTaller);

            if (empty($errores)) {
                $this->modeloMecanico->crear($nombreCompleto, $codigoEmpleado, $idTaller, $telefono, $fechaIngreso);
                $this->guardarMensajeFlash('Mecánico creado correctamente.', 'exito');
                header('Location: /mecanico/index');
                exit;
            }

            $mecanico = [
                'nombre_completo' => $nombreCompleto,
                'codigo_empleado' => $codigoEmpleado,
                'id_taller' => $idTaller,
                'telefono' => $telefono,
                'fecha_ingreso' => $fechaIngreso
            ];
        }

        $modo = 'crear';
        require_once __DIR__ . '/../views/mecanico/mecanico_formulario.php';
    }

    /**
     * Gestiona la actualización de los datos de un mecánico existente.
     * 
     * @param string $id Identificador único del mecánico recibido por la ruta.
     */
    public function editar(string $id): void
    {
        $idMecanico = (int)$id;
        $mecanico = $this->modeloMecanico->obtenerPorId($idMecanico);

        if ($mecanico === null) {
            $this->guardarMensajeFlash('El mecánico que intentas editar no existe.', 'error');
            header('Location: /mecanico/index');
            exit;
        }

        // Un Encargado de Taller no puede tocar mecanicos de otro taller, ni por URL directa
        if (!Auth::perteneceATaller((int)$mecanico['id_taller'])) {
            http_response_code(403);
            die('No tienes permiso para editar mecánicos de otro taller.');
        }

        $tallerFijo = Auth::esAdministrador() ? null : (int)$_SESSION['id_taller'];
        $talleres = $tallerFijo === null
            ? $this->modeloTaller->obtenerTodos()
            : array_filter([$this->modeloTaller->obtenerPorId($tallerFijo)]);

        $errores = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombreCompleto = trim($_POST['nombre_completo'] ?? '');
            $codigoEmpleado = trim($_POST['codigo_empleado'] ?? '');
            $idTaller = $tallerFijo ?? (int)($_POST['id_taller'] ?? 0);
            $telefono = trim($_POST['telefono'] ?? '') ?: null;
            $fechaIngreso = trim($_POST['fecha_ingreso'] ?? '') ?: null;

            $errores = $this->validar($nombreCompleto, $codigoEmpleado, $idTaller, $idMecanico);

            if (empty($errores)) {
                $this->modeloMecanico->actualizar($idMecanico, $nombreCompleto, $codigoEmpleado, $idTaller, $telefono, $fechaIngreso);
                $this->guardarMensajeFlash('Mecánico actualizado correctamente.', 'exito');
                header('Location: /mecanico/index');
                exit;
            }

            $mecanico = [
                'id_mecanico' => $idMecanico,
                'nombre_completo' => $nombreCompleto,
                'codigo_empleado' => $codigoEmpleado,
                'id_taller' => $idTaller,
                'telefono' => $telefono,
                'fecha_ingreso' => $fechaIngreso
            ];
        }

        $modo = 'editar';
        require_once __DIR__ . '/../views/mecanico/mecanico_formulario.php';
    }

    /**
     * Desactiva lógicamente a un mecánico si este no posee herramientas asignadas activas.
     * 
     * @param string $id Identificador del mecánico a desactivar.
     */
    public function desactivar(string $id): void
    {
        $idMecanico = (int)$id;
        $mecanico = $this->modeloMecanico->obtenerPorId($idMecanico);

        if ($mecanico === null) {
            header('Location: /mecanico/index');
            exit;
        }

        if (!Auth::perteneceATaller((int)$mecanico['id_taller'])) {
            http_response_code(403);
            die('No tienes permiso para desactivar mecánicos de otro taller.');
        }

        if ($this->modeloMecanico->tieneAsignacionesActivas($idMecanico)) {
            $this->guardarMensajeFlash(
                'No se puede desactivar: este mecánico tiene herramientas asignadas activas. Reasigna o devuelve esas herramientas primero.',
                'error'
            );
            header('Location: /mecanico/index');
            exit;
        }

        $this->modeloMecanico->cambiarEstado($idMecanico, 0);
        $this->guardarMensajeFlash('Mecánico desactivado.', 'exito');
        header('Location: /mecanico/index');
        exit;
    }

    /**
     * Reactiva a un mecánico previamente inactivo en el sistema.
     * 
     * @param string $id Identificador del mecánico a activar.
     */
    public function activar(string $id): void
    {
        $idMecanico = (int)$id;
        $mecanico = $this->modeloMecanico->obtenerPorId($idMecanico);

        if ($mecanico === null) {
            header('Location: /mecanico/index');
            exit;
        }

        if (!Auth::perteneceATaller((int)$mecanico['id_taller'])) {
            http_response_code(403);
            die('No tienes permiso para activar mecánicos de otro taller.');
        }

        $this->modeloMecanico->cambiarEstado($idMecanico, 1);
        $this->guardarMensajeFlash('Mecánico activado.', 'exito');
        header('Location: /mecanico/index');
        exit;
    }

    /**
     * Valida los datos del formulario de mecánico (campos obligatorios y unicidad de código).
     * 
     * @param string    $nombreCompleto Nombre ingresado.
     * @param string    $codigoEmpleado Código de empleado ingresado.
     * @param int       $idTaller       Identificador del taller seleccionado.
     * @param int|null  $idExcluir      ID opcional a excluir en la validación de duplicados (útil en edición).
     * @return array Arreglo con la lista de errores encontrados.
     */
    private function validar(string $nombreCompleto, string $codigoEmpleado, int $idTaller, ?int $idExcluir = null): array
    {
        $errores = [];

        if ($nombreCompleto === '') {
            $errores[] = 'El nombre completo es obligatorio.';
        }

        if ($codigoEmpleado === '') {
            $errores[] = 'El código de empleado es obligatorio.';
        } elseif ($this->modeloMecanico->existeCodigoEmpleado($codigoEmpleado, $idExcluir)) {
            $errores[] = 'Ese código de empleado ya está en uso por otro mecánico.';
        }

        if ($idTaller <= 0) {
            $errores[] = 'Debes seleccionar un taller.';
        }

        return $errores;
    }

    /**
     * Guarda un mensaje temporal de notificación en la sesión.
     * 
     * @param string $texto Contenido del mensaje.
     * @param string $tipo  Tipo de alerta (ej. 'exito', 'error').
     */
    private function guardarMensajeFlash(string $texto, string $tipo): void
    {
        $_SESSION['flash'] = ['texto' => $texto, 'tipo' => $tipo];
    }

    /**
     * Obtiene y limpia el mensaje flash almacenado en la sesión.
     * 
     * @return array|null Arreglo con el mensaje y tipo, o null si no existe.
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