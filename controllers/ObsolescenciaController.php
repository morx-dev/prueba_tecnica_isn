<?php
declare(strict_types=1);

class ObsolescenciaController
{
    private HerramientaObsoletaModel $obsoletoModel;
    private HerramientaRecicladaModel $recicladoModel;
    private HerramientaModel $herramientaModel;
    private AsignacionModel $asignacionModel;

    public function __construct()
    {
        Auth::requerirRol([1, 2]);

        $db = (new Database())->getConnection();
        $this->obsoletoModel = new HerramientaObsoletaModel($db);
        $this->recicladoModel = new HerramientaRecicladaModel($db);
        $this->herramientaModel = new HerramientaModel($db);
        $this->asignacionModel = new AsignacionModel($db);
    }

    // Bodega de obsoletos: lo que esta pendiente de reciclar
    public function index(): void
    {
        if (Auth::esAdministrador()) {
            $enBodega = $this->obsoletoModel->obtenerEnBodega();
        } else {
            $enBodega = $this->obsoletoModel->obtenerEnBodegaPorTaller((int)$_SESSION['id_taller']);
        }

        $mensaje = $this->obtenerMensajeFlash();

        require_once __DIR__ . '/../views/obsolescencia/obsolescencia_index.php';
    }

    // Historial de lo que ya se reciclo, con su valor -- el segundo reporte que pide el enunciado
    public function historial(): void
    {
        if (Auth::esAdministrador()) {
            $reciclados = $this->recicladoModel->obtenerTodas();
        } else {
            $reciclados = $this->recicladoModel->obtenerPorTaller((int)$_SESSION['id_taller']);
        }

        require_once __DIR__ . '/../views/obsolescencia/obsolescencia_historial.php';
    }

    // Formulario para marcar una herramienta como obsoleta
    public function marcar(string $id): void
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
            die('No tienes permiso para modificar herramientas de otro taller.');
        }

        if (!in_array($herramienta['estado'], ['disponible', 'asignada'], true)) {
            $this->guardarMensajeFlash('Esta herramienta ya está obsoleta o reciclada.', 'error');
            header('Location: /herramienta/index');
            exit;
        }

        $errores = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $motivo = trim($_POST['motivo'] ?? '');

            if ($motivo === '') {
                $errores[] = 'Debes indicar el motivo del reemplazo u obsolescencia.';
            }

            if (empty($errores)) {
                // Si esta asignada, cerramos esa asignacion y usamos a ese mecanico como "solicitante"
                $asignacionActiva = $this->asignacionModel->obtenerActivaPorHerramienta($idHerramienta);
                $idAsignacionActiva = $asignacionActiva['id_asignacion'] ?? null;
                $idMecanicoSolicito = $asignacionActiva['id_mecanico'] ?? null;

                $exito = $this->obsoletoModel->marcarObsoleta(
                    $idHerramienta,
                    $idAsignacionActiva,
                    $idMecanicoSolicito,
                    (int)$_SESSION['id_usuario'],
                    $motivo
                );

                if ($exito) {
                    $this->guardarMensajeFlash('Herramienta marcada como obsoleta.', 'exito');
                } else {
                    $this->guardarMensajeFlash('Ocurrió un error al marcar la herramienta como obsoleta.', 'error');
                }
                header('Location: /herramienta/index');
                exit;
            }
        }

        require_once __DIR__ . '/../views/obsolescencia/obsolescencia_marcar.php';
    }

    // Formulario para registrar el reciclaje de un obsoleto que esta en bodega
    public function reciclar(string $id): void
    {
        $idObsoleto = (int)$id;
        $obsoleto = $this->obsoletoModel->obtenerPorId($idObsoleto);

        if ($obsoleto === null) {
            $this->guardarMensajeFlash('El registro de obsolescencia no existe.', 'error');
            header('Location: /obsolescencia/index');
            exit;
        }

        if (!Auth::perteneceATaller((int)$obsoleto['id_taller'])) {
            http_response_code(403);
            die('No tienes permiso para reciclar herramientas de otro taller.');
        }

        if ($obsoleto['estado'] !== 'en_bodega') {
            $this->guardarMensajeFlash('Este obsoleto ya fue reciclado.', 'error');
            header('Location: /obsolescencia/index');
            exit;
        }

        $errores = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $pesoKg = trim($_POST['peso_kg'] ?? '') !== '' ? (float)$_POST['peso_kg'] : null;
            $valorEstimado = trim($_POST['valor_estimado'] ?? '') !== '' ? (float)$_POST['valor_estimado'] : null;
            $observaciones = trim($_POST['observaciones'] ?? '') ?: null;

            $exito = $this->recicladoModel->reciclar(
                $idObsoleto,
                (int)$obsoleto['id_herramienta'],
                (int)$_SESSION['id_usuario'],
                $pesoKg,
                $valorEstimado,
                $observaciones
            );

            if ($exito) {
                $this->guardarMensajeFlash('Reciclaje registrado correctamente.', 'exito');
            } else {
                $this->guardarMensajeFlash('Ocurrió un error al registrar el reciclaje.', 'error');
            }
            header('Location: /obsolescencia/index');
            exit;
        }

        require_once __DIR__ . '/../views/obsolescencia/obsolescencia_reciclar.php';
    }

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