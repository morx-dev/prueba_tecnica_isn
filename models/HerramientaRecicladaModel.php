<?php
declare(strict_types=1);

class HerramientaRecicladaModel
{
    private PDO $db;

    private const CONSULTA_BASE = "
        SELECT hr.*, ho.id_herramienta, ho.motivo, h.nombre AS nombre_herramienta, h.id_taller,
               t.nombre AS nombre_taller
        FROM herramientas_recicladas hr
        INNER JOIN herramientas_obsoletas ho ON ho.id_obsoleto = hr.id_obsoleto
        INNER JOIN herramientas h ON h.id_herramienta = ho.id_herramienta
        INNER JOIN talleres t ON t.id_taller = h.id_taller
    ";

    public function __construct(PDO $conexion)
    {
        $this->db = $conexion;
    }

    public function obtenerTodas(): array
    {
        $stmt = $this->db->query(self::CONSULTA_BASE . " ORDER BY hr.fecha_reciclaje DESC");
        return $stmt->fetchAll();
    }

    public function obtenerPorTaller(int $idTaller): array
    {
        $stmt = $this->db->prepare(self::CONSULTA_BASE . " WHERE h.id_taller = :id_taller ORDER BY hr.fecha_reciclaje DESC");
        $stmt->execute([':id_taller' => $idTaller]);
        return $stmt->fetchAll();
    }

    /**
     * Registra el reciclaje, cierra el registro de obsolescencia (estado = 'reciclado')
     * y marca la herramienta como 'reciclada' -- los 3 pasos en una sola transaccion.
     */
    public function reciclar(
        int $idObsoleto,
        int $idHerramienta,
        int $idUsuarioRegistro,
        ?float $pesoKg,
        ?float $valorEstimado,
        ?string $observaciones
    ): bool {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare(
                "INSERT INTO herramientas_recicladas
                    (id_obsoleto, id_usuario_registro, fecha_reciclaje, peso_kg, valor_estimado, observaciones)
                 VALUES (:id_obsoleto, :id_usuario_registro, NOW(), :peso_kg, :valor_estimado, :observaciones)"
            );
            $stmt->execute([
                ':id_obsoleto' => $idObsoleto,
                ':id_usuario_registro' => $idUsuarioRegistro,
                ':peso_kg' => $pesoKg,
                ':valor_estimado' => $valorEstimado,
                ':observaciones' => $observaciones
            ]);

            $stmtObsoleto = $this->db->prepare(
                "UPDATE herramientas_obsoletas SET estado = 'reciclado' WHERE id_obsoleto = :id"
            );
            $stmtObsoleto->execute([':id' => $idObsoleto]);

            $stmtHerramienta = $this->db->prepare(
                "UPDATE herramientas SET estado = 'reciclada' WHERE id_herramienta = :id"
            );
            $stmtHerramienta->execute([':id' => $idHerramienta]);

            $this->db->commit();
            return true;
        } catch (PDOException $excepcion) {
            $this->db->rollBack();
            error_log('Error al reciclar herramienta: ' . $excepcion->getMessage());
            return false;
        }
    }
}