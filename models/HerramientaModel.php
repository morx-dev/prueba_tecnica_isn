<?php
declare(strict_types=1);

/**
 * Modelo para la gestión del catálogo de herramientas de los talleres.
 */
class HerramientaModel
{
    private PDO $db;

    // Trae taller y, si esta asignada, el nombre del mecanico que la tiene ahora.
    // mecanico_actual sale NULL si la herramienta no tiene asignacion activa.
    private const CONSULTA_BASE = "
        SELECT h.*, t.nombre AS nombre_taller, m.nombre_completo AS mecanico_actual
        FROM herramientas h
        INNER JOIN talleres t ON t.id_taller = h.id_taller
        LEFT JOIN asignaciones a ON a.id_herramienta = h.id_herramienta AND a.estado = 'activa'
        LEFT JOIN mecanicos m ON m.id_mecanico = a.id_mecanico
    ";

    /**
     * Inicializa el modelo recibiendo la conexión activa de PDO.
     * 
     * @param PDO $conexion Instancia de conexión a la base de datos.
     */
    public function __construct(PDO $conexion)
    {
        $this->db = $conexion;
    }

    /**
     * Obtiene todas las herramientas del inventario global con información de su taller y mecánico actual.
     * 
     * @return array Arreglo asociativo con todas las herramientas.
     */
    public function obtenerTodas(): array
    {
        $stmt = $this->db->query(self::CONSULTA_BASE . " ORDER BY h.id_herramienta DESC");
        return $stmt->fetchAll();
    }

    /**
     * Obtiene las herramientas pertenecientes a un taller específico.
     * 
     * @param int $idTaller Identificador del taller.
     * @return array Arreglo asociativo con las herramientas del taller.
     */
    public function obtenerPorTaller(int $idTaller): array
    {
        $stmt = $this->db->prepare(self::CONSULTA_BASE . " WHERE h.id_taller = :id_taller ORDER BY h.id_herramienta DESC");
        $stmt->execute([':id_taller' => $idTaller]);
        return $stmt->fetchAll();
    }

    /**
     * Busca una herramienta específica por su identificador único.
     * 
     * @param int $id Identificador de la herramienta.
     * @return array|null Arreglo con los datos de la herramienta o null si no existe.
     */
    public function obtenerPorId(int $id): ?array
    {
        $stmt = $this->db->prepare(self::CONSULTA_BASE . " WHERE h.id_herramienta = :id");
        $stmt->execute([':id' => $id]);
        $resultado = $stmt->fetch();
        return $resultado !== false ? $resultado : null;
    }

    // El estado nace siempre en 'disponible'. No hay forma de crear una herramienta
    // ya "asignada" u "obsoleta" -- esos estados solo se alcanzan a traves de sus propios flujos.
    /**
     * Registra una nueva herramienta en el inventario con estado inicial 'disponible'.
     * 
     * @param string      $nombre       Nombre de la herramienta.
     * @param string|null $medida       Medida o especificación de la herramienta.
     * @param float       $precioCompra Costo de adquisición.
     * @param int         $idTaller     Identificador del taller propietario.
     * @param string      $fechaIngreso Fecha de ingreso al inventario.
     * @return bool True si la inserción fue exitosa, false en caso contrario.
     */
    public function crear(string $nombre, ?string $medida, float $precioCompra, int $idTaller, string $fechaIngreso): bool
    {
        $stmt = $this->db->prepare(
            "INSERT INTO herramientas (nombre, medida, precio_compra, id_taller, estado, fecha_ingreso, fecha_creacion)
             VALUES (:nombre, :medida, :precio_compra, :id_taller, 'disponible', :fecha_ingreso, NOW())"
        );
        return $stmt->execute([
            ':nombre' => $nombre,
            ':medida' => $medida,
            ':precio_compra' => $precioCompra,
            ':id_taller' => $idTaller,
            ':fecha_ingreso' => $fechaIngreso
        ]);
    }

    // Solo edita datos de catalogo. El estado NUNCA se toca aqui a proposito --
    // cambia unicamente via AsignacionModel (asignar/devolver) o el futuro flujo de obsolescencia.
    /**
     * Actualiza los datos del catálogo de una herramienta existente, sin alterar su estado.
     * 
     * @param int         $id           Identificador de la herramienta.
     * @param string      $nombre       Nombre actualizado.
     * @param string|null $medida       Medida actualizada.
     * @param float       $precioCompra Precio de compra actualizado.
     * @param int         $idTaller     Identificador del taller asignado.
     * @return bool True si la actualización fue exitosa, false en caso contrario.
     */
    public function actualizar(int $id, string $nombre, ?string $medida, float $precioCompra, int $idTaller): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE herramientas
             SET nombre = :nombre, medida = :medida, precio_compra = :precio_compra, id_taller = :id_taller
             WHERE id_herramienta = :id"
        );
        return $stmt->execute([
            ':id' => $id,
            ':nombre' => $nombre,
            ':medida' => $medida,
            ':precio_compra' => $precioCompra,
            ':id_taller' => $idTaller
        ]);
    }
}