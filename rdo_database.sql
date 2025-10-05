-- Banco de Dados para Sistema de Gestão de RDO
CREATE DATABASE IF NOT EXISTS sistema_rdo;
USE sistema_rdo;

-- Tabela de Usuários
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    tipo ENUM('admin', 'engenheiro', 'fiscal', 'operacional') DEFAULT 'operacional',
    ativo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de Obras
CREATE TABLE obras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) UNIQUE NOT NULL,
    nome VARCHAR(200) NOT NULL,
    cliente VARCHAR(150),
    endereco TEXT,
    data_inicio DATE,
    data_prevista_fim DATE,
    status ENUM('planejamento', 'em_andamento', 'pausada', 'concluida') DEFAULT 'planejamento',
    responsavel_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (responsavel_id) REFERENCES usuarios(id)
);

-- Tabela de RDOs
CREATE TABLE rdos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    obra_id INT NOT NULL,
    data_rdo DATE NOT NULL,
    numero_rdo VARCHAR(50),
    periodo ENUM('manha', 'tarde', 'noite', 'dia_todo') DEFAULT 'dia_todo',
    clima ENUM('sol', 'chuva', 'nublado', 'parcialmente_nublado') DEFAULT 'sol',
    temperatura DECIMAL(4,1),
    servicos_executados TEXT,
    observacoes TEXT,
    status ENUM('rascunho', 'enviado', 'aprovado', 'rejeitado') DEFAULT 'rascunho',
    criado_por INT NOT NULL,
    aprovado_por INT,
    data_aprovacao DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (obra_id) REFERENCES obras(id),
    FOREIGN KEY (criado_por) REFERENCES usuarios(id),
    FOREIGN KEY (aprovado_por) REFERENCES usuarios(id),
    UNIQUE KEY unique_rdo_obra_data (obra_id, data_rdo)
);

-- Tabela de Mão de Obra
CREATE TABLE rdo_mao_obra (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rdo_id INT NOT NULL,
    funcao VARCHAR(100) NOT NULL,
    quantidade INT NOT NULL,
    horas_trabalhadas DECIMAL(5,2),
    observacao VARCHAR(255),
    FOREIGN KEY (rdo_id) REFERENCES rdos(id) ON DELETE CASCADE
);

-- Tabela de Equipamentos
CREATE TABLE rdo_equipamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rdo_id INT NOT NULL,
    equipamento VARCHAR(150) NOT NULL,
    quantidade INT NOT NULL,
    horas_utilizadas DECIMAL(5,2),
    observacao VARCHAR(255),
    FOREIGN KEY (rdo_id) REFERENCES rdos(id) ON DELETE CASCADE
);

-- Tabela de Materiais
CREATE TABLE rdo_materiais (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rdo_id INT NOT NULL,
    material VARCHAR(150) NOT NULL,
    unidade VARCHAR(20),
    quantidade DECIMAL(10,2) NOT NULL,
    observacao VARCHAR(255),
    FOREIGN KEY (rdo_id) REFERENCES rdos(id) ON DELETE CASCADE
);

-- Tabela de Fotos/Anexos
CREATE TABLE rdo_anexos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rdo_id INT NOT NULL,
    tipo ENUM('foto', 'documento', 'outro') DEFAULT 'foto',
    nome_arquivo VARCHAR(255) NOT NULL,
    caminho_arquivo VARCHAR(500) NOT NULL,
    descricao TEXT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rdo_id) REFERENCES rdos(id) ON DELETE CASCADE
);

-- Tabela de Histórico de Alterações
CREATE TABLE rdo_historico (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rdo_id INT NOT NULL,
    usuario_id INT NOT NULL,
    acao VARCHAR(100) NOT NULL,
    detalhes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rdo_id) REFERENCES rdos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- Inserir usuário admin padrão (senha: admin123)
INSERT INTO usuarios (nome, email, senha, tipo) VALUES 
('Administrador', 'admin@sistema.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Inserir obra exemplo
INSERT INTO obras (codigo, nome, cliente, endereco, data_inicio, status, responsavel_id) VALUES
('OBR-001', 'Construção Edifício Central', 'Construtora XYZ', 'Av. Principal, 1000', CURDATE(), 'em_andamento', 1);