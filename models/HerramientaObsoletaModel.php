<?php
declare(strict_types=1);

/**
 * Modelo para la gestión de herramientas obsoletas y su almacenamiento en bodega.
 * Encapsula las operaciones de base de datos para consultar elementos pendientes de reciclar
 * y registrar la obsolescencia de herramientas mediante transacciones seguras.
 */
class HerramientaObsoletaModel
{
    private PDO $db;

    /**
     * Consulta SQL base utilizada para ungrar información detallada de herramientas obsoletas,
     * incluyendo datos de la herramienta, el taller y el mecánico que solicitó el proceso.
     */
    private const CONSULTA_BASE = "
        SELECT ho.*, h.nombre AS nombre_herramienta, h.medida, h.precio_compra, h.id_taller,
               t.nombre AS nombre_taller, mec.nombre_completo AS nombre_mecanico_solicito
        FROM herramientas_obsoletas ho
        INNER JOIN herramientas h ON h.id_herramienta = ho.id_herramienta
        INNER JOIN talleres t ON t.id_taller = h.id_taller
        LEFT JOIN mecanicos mec ON mec.id_mecanico = ho.id_mecanico_solicito
    ";

    /**
     * Constructor del modelo.
     * 
     * @param PDO $conexion Instancia de la conexión a la base de datos.
     */
    public function __construct(PDO $conexion)
    {
        $this->db = $conexion;
    }

    /**
     * Obtiene todas las herramientas que se encuentran en bodega pendientes de reciclar
     * (disponible para administradores globales).
     */
    // Bodega de obsoletos: lo que todavia NO se ha enviado a reciclar
    public function obtenerEnBodega(): array
    {
        $stmt = $this->db->query(self::CONSULTA_BASE . " WHERE ho.estado = 'en_bodega' ORDER BY ho.fecha_obsolescencia DESC");
        return $stmt->fetchAll();
    }

    /**
     * Obtiene las herramientas en bodega filtradas por el identificador de un taller específico.
     * 
     * @param int $idTaller Identificador del taller.
     * @return array Arreglo con los registros de obsolescencia en bodega del taller.
     */
    public function obtenerEnBodegaPorTaller(int $idTaller): array
    {
        $stmt = $this->db->prepare(
            self::CONSULTA_BASE . " WHERE ho.estado = 'en_bodega' AND h.id_taller = :id_taller ORDER BY ho.fecha_obsolescencia DESC"
        );
        $stmt->execute([':id_taller' => $idTaller]);
        return $stmt->fetchAll();
    }

    /**
     * Busca y retorna un registro específico de obsolescencia por su identificador único.
     * 
     * @param int $id Identificador del registro obsoleto.
     * @return array|null Arreglo con los datos del registro o null si no existe.
     */
    public function obtenerPorId(int $id): ?array
    {
        $stmt = $this->db->prepare(self::CONSULTA_BASE . " WHERE ho.id_obsoleto = :id");
        $stmt->execute([':id' => $id]);
        $resultado = $stmt->fetch();
        return $resultado !== false ? $resultado : null;
    }

    /**
     * Marca una herramienta como obsoleta.
     * Si $idAsignacionActiva viene con valor, esa asignacion se cierra como parte
     * de la misma transaccion (la herramienta deja de estar "en manos de" el mecanico).
     * Los 3 pasos (cerrar asignacion, crear el registro de obsoleto, cambiar estado
     * de la herramienta) se aplican juntos o no se aplica ninguno.
     * 
     * @param int       $idHerramienta        Identificador de la herramienta.
     * @param int|null  $idAsignacionActiva   Identificador de la asignación activa a cerrar (si aplica).
     * @param int|null  $idMecanicoSolicito   Identificador del mecánico que solicitó el proceso.
     * @param int       $idUsuarioRegistro    Identificador del usuario que registra la acción.
     * @param string    $motivo               Motivo por el cual se declara obsoleta la herramienta.
     * @return bool True si la transacción se completa con éxito, false en caso de error.
     */
    public function marcarObsoleta(
        int $idHerramienta,
        ?int $idAsignacionActiva,
        ?int $idMecanicoSolicito,
        int $idUsuarioRegistro,
        string $motivo
    ): bool {
        $this->db->beginTransaction();

        try {
            if ($idAsignacionActiva !== null) {
                $stmt = $this->db->prepare(
                    "UPDATE asignaciones SET estado = 'finalizada', fecha_devolucion = NOW() WHERE id_asignacion = :id"
                );
                $stmt->execute([':id' => $idAsignacionActiva]);
            }

            $stmtObsoleto = $this->db->prepare(
                "INSERT INTO herramientas_obsoletas
                    (id_herramienta, id_mecanico_solicito, id_usuario_registro, fecha_obsolescencia, motivo, estado)
                 VALUES (:id_herramienta, :id_mecanico_solicito, :id_usuario_registro, NOW(), :motivo, 'en_bodega')"
            );
            $stmtObsoleto->execute([
                ':id_herramienta' => $idHerramienta,
                ':id_mecanico_solicito' => $idMecanicoSolicito,
                ':id_usuario_registro' => $idUsuarioRegistro,
                ':motivo' => $motivo
            ]);

            $stmtHerramienta = $this->db->prepare(
                "UPDATE herramientas SET estado = 'obsoleta' WHERE id_herramienta = :id"
            );
            $stmtHerramienta->execute([':id' => $idHerramienta]);

            $this->db->commit();
            return true;
        } catch (PDOException $excepcion) {
            $this->db->rollBack();
            error_log('Error al marcar herramienta obsoleta: ' . $excepcion->getMessage());
            return false;
        }
    }
}