<?php
declare(strict_types=1);

/**
 * Clase Database
 * Gestiona la conexión centralizada con el servidor de base de datos MariaDB 
 * utilizando la extensión PDO (PHP Data Objects).
 */
class Database
{
    /** @var string Host o nombre del contenedor del servidor de base de datos */
    private string $host = "db";

    /** @var string Nombre de la base de datos a utilizar */
    private string $db_name = "hogares_isn_inventario";

    /** @var string Usuario con privilegios de acceso */
    private string $username = "root";

    /** @var string Contraseña de autenticación del usuario */
    private string $password = "rootpassword";

    /** @var PDO|null Instancia única de la conexión PDO activa */
    private ?PDO $conn = null;

    /**
     * Establece y retorna la conexión activa a la base de datos mediante PDO.
     * Configura el manejo de excepciones, el modo de recuperación asociativo
     * y deshabilita la emulación de consultas preparadas para mayor seguridad.
     * 
     * @return PDO Objeto de conexión activo.
     */
    public function getConnection(): PDO
    {
        try {
            // Inicializa la conexión PDO con parámetros de DSN, credenciales y opciones de seguridad
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $exception) {
            // Registra el error internamente en el log del servidor y detiene la ejecución de forma segura
            error_log("Error de conexion: " . $exception->getMessage());
            http_response_code(500);
            die("Error crítico de conexión a la base de datos. Contacte al administrador.");
        }

        return $this->conn;
    }
}