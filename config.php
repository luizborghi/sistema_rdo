<?php
// config.php - Configuração do Sistema RDO

// Configurações do Banco de Dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'sistema_rdo');
define('DB_USER', 'root');
define('DB_PASS', '');

// Configurações do Sistema
define('SITE_URL', 'http://localhost/sistema_rdo');
define('SITE_NAME', 'Sistema de Gestão de RDO');

// Configurações de Upload
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// Configurações de Sessão
ini_set('session.cookie_httponly', 1);
session_start();

// Classe de Conexão com Banco de Dados
class Database {
    private static $instance = null;
    private $conn;
    
    private function __construct() {
        try {
            $this->conn = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException $e) {
            die("Erro na conexão: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->conn;
    }
}

// Função para verificar se usuário está logado
function verificarLogin() {
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: login.php');
        exit;
    }
}

// Função para retornar JSON
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Função para sanitizar entrada
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Função para formatar data brasileira
function formatarDataBR($data) {
    if (empty($data)) return '';
    return date('d/m/Y', strtotime($data));
}

// Função para formatar data para MySQL
function formatarDataMySQL($data) {
    if (empty($data)) return null;
    $partes = explode('/', $data);
    if (count($partes) === 3) {
        return $partes[2] . '-' . $partes[1] . '-' . $partes[0];
    }
    return $data;
}
?>