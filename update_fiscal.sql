-- Adicionar coluna fiscal_id na tabela obras
ALTER TABLE obras ADD COLUMN fiscal_id INT AFTER responsavel_id;
ALTER TABLE obras ADD FOREIGN KEY (fiscal_id) REFERENCES usuarios(id);

-- Comentário: fiscal_id é quem aprova os RDOs dessa obra

-- OPCIONAL: Se quiser migrar os responsáveis que são fiscais
-- UPDATE obras SET fiscal_id = responsavel_id WHERE responsavel_id IN (
--     SELECT id FROM usuarios WHERE tipo = 'fiscal'
-- );

-- Ver estrutura atualizada
DESCRIBE obras;