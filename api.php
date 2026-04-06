<?php

/**
 * API para Geolocalização de Pontos
 * Sistema de gestão de coordenadas geográficas com SQLite
 */

// Configurações de segurança
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Configuração do banco de dados
class Database
{
    private static $instance = null;
    private $db;
    private $dbFile = 'geodata.db';

    private function __construct()
    {
        try {
            $this->db = new PDO("sqlite:{$this->dbFile}");
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->initDatabase();
        } catch (PDOException $e) {
            $this->sendError('Erro ao conectar ao banco de dados', 500);
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        return $this->db;
    }

    private function initDatabase()
    {
        $sql = "CREATE TABLE IF NOT EXISTS pontos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            descricao TEXT NOT NULL,
            lat REAL NOT NULL,
            lng REAL NOT NULL,
            data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
            data_atualizacao DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        $this->db->exec($sql);
    }

    private function sendError($message, $code = 400)
    {
        http_response_code($code);
        echo json_encode(['status' => 'error', 'message' => $message]);
        exit;
    }
}

// Classe de controle da API
class GeoAPI
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Valida coordenadas geográficas
     */
    private function validarCoordenadas($lat, $lng)
    {
        if (!is_numeric($lat) || !is_numeric($lng)) {
            return false;
        }
        $lat = floatval($lat);
        $lng = floatval($lng);
        return ($lat >= -90 && $lat <= 90) && ($lng >= -180 && $lng <= 180);
    }

    /**
     * Sanitiza string removendo tags HTML
     */
    private function sanitizar($texto)
    {
        return htmlspecialchars(strip_tags(trim($texto)), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Envia resposta JSON de sucesso
     */
    private function sendSuccess($data = null, $message = 'Operação realizada com sucesso')
    {
        echo json_encode([
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ]);
        exit;
    }

    /**
     * Envia resposta JSON de erro
     */
    private function sendError($message, $code = 400)
    {
        http_response_code($code);
        echo json_encode([
            'status' => 'error',
            'message' => $message
        ]);
        exit;
    }

    /**
     * Lista todos os pontos cadastrados
     */
    public function listar()
    {
        try {
            $stmt = $this->db->query("SELECT * FROM pontos ORDER BY id DESC");
            $pontos = $stmt->fetchAll();
            $this->sendSuccess($pontos);
        } catch (PDOException $e) {
            $this->sendError('Erro ao listar pontos');
        }
    }

    /**
     * Salva ou atualiza um ponto
     */
    public function salvar()
    {
        // Validar entrada
        $id = isset($_POST['id']) ? intval($_POST['id']) : -1;
        $descricao = isset($_POST['desc']) ? $this->sanitizar($_POST['desc']) : '';
        $lat = isset($_POST['lat']) ? $_POST['lat'] : null;
        $lng = isset($_POST['lng']) ? $_POST['lng'] : null;

        // Validações
        if (empty($descricao)) {
            $this->sendError('Descrição é obrigatória');
        }

        if ($lat === null || $lng === null) {
            $this->sendError('Latitude e longitude são obrigatórias');
        }

        if (!$this->validarCoordenadas($lat, $lng)) {
            $this->sendError('Coordenadas inválidas. Lat: -90 a 90, Lng: -180 a 180');
        }

        try {
            if ($id == -1) {
                // Inserir novo registro
                $stmt = $this->db->prepare(
                    "INSERT INTO pontos (descricao, lat, lng) VALUES (?, ?, ?)"
                );
                $stmt->execute([$descricao, floatval($lat), floatval($lng)]);
                $this->sendSuccess(['id' => $this->db->lastInsertId()], 'Ponto cadastrado com sucesso');
            } else {
                // Atualizar registro existente
                $stmt = $this->db->prepare(
                    "UPDATE pontos SET descricao=?, lat=?, lng=?, data_atualizacao=CURRENT_TIMESTAMP WHERE id=?"
                );
                $result = $stmt->execute([$descricao, floatval($lat), floatval($lng), $id]);

                if ($stmt->rowCount() === 0) {
                    $this->sendError('Ponto não encontrado', 404);
                }

                $this->sendSuccess(['id' => $id], 'Ponto atualizado com sucesso');
            }
        } catch (PDOException $e) {
            $this->sendError('Erro ao salvar ponto');
        }
    }

    /**
     * Deleta um ponto específico
     */
    public function deletar()
    {
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

        if ($id <= 0) {
            $this->sendError('ID inválido');
        }

        try {
            $stmt = $this->db->prepare("DELETE FROM pontos WHERE id=?");
            $stmt->execute([$id]);

            if ($stmt->rowCount() === 0) {
                $this->sendError('Ponto não encontrado', 404);
            }

            $this->sendSuccess(null, 'Ponto excluído com sucesso');
        } catch (PDOException $e) {
            $this->sendError('Erro ao deletar ponto');
        }
    }

    /**
     * Limpa todos os pontos do banco
     */
    public function limpar()
    {
        try {
            $this->db->exec("DELETE FROM pontos");
            $this->db->exec("DELETE FROM sqlite_sequence WHERE name='pontos'");
            $this->sendSuccess(null, 'Banco de dados limpo com sucesso');
        } catch (PDOException $e) {
            $this->sendError('Erro ao limpar banco de dados');
        }
    }

    /**
     * Importa múltiplos pontos de uma vez
     */
    public function importar()
    {
        $pontos = isset($_POST['pontos']) ? json_decode($_POST['pontos'], true) : null;

        if (!is_array($pontos) || empty($pontos)) {
            $this->sendError('Dados de importação inválidos');
        }

        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare("INSERT INTO pontos (descricao, lat, lng) VALUES (?, ?, ?)");

            $importados = 0;
            foreach ($pontos as $ponto) {
                if (!isset($ponto['desc'], $ponto['lat'], $ponto['lng'])) {
                    continue;
                }

                if (!$this->validarCoordenadas($ponto['lat'], $ponto['lng'])) {
                    continue;
                }

                $descricao = $this->sanitizar($ponto['desc']);
                $stmt->execute([$descricao, floatval($ponto['lat']), floatval($ponto['lng'])]);
                $importados++;
            }

            $this->db->commit();
            $this->sendSuccess(['total' => $importados], "$importados pontos importados com sucesso");
        } catch (PDOException $e) {
            $this->db->rollBack();
            $this->sendError('Erro ao importar pontos');
        }
    }

    /**
     * Processa a requisição baseado na action
     */
    public function processar()
    {
        $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

        switch ($action) {
            case 'listar':
            case 'list':
                $this->listar();
                break;

            case 'salvar':
            case 'save':
                $this->salvar();
                break;

            case 'deletar':
            case 'delete':
                $this->deletar();
                break;

            case 'limpar':
            case 'clear':
                $this->limpar();
                break;

            case 'importar':
            case 'import':
                $this->importar();
                break;

            default:
                $this->sendError('Ação inválida', 400);
        }
    }
}

// Executar API
try {
    $api = new GeoAPI();
    $api->processar();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Erro interno do servidor'
    ]);
}
