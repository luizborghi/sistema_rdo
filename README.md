# 📋 Sistema de Gestão de RDO - Relatório Diário de Obras

Sistema completo e moderno para gestão de Relatórios Diários de Obras (RDO) com workflow de aprovação.

## 🚀 Funcionalidades

### ✅ Gerenciamento de RDOs
- Criação e edição de RDOs
- Registro de mão de obra, equipamentos e materiais
- Upload de fotos
- Informações climáticas
- Status: Rascunho, Enviado, Aprovado, Rejeitado

### ✅ Sistema de Aprovação
- Painel dedicado para aprovadores
- Aprovação rápida ou detalhada
- Comentários na aprovação/rejeição
- Notificações por email
- Histórico completo de ações
- Alertas de urgência por atraso

### ✅ Dashboard Interativo
- Estatísticas em tempo real
- Gráficos de RDOs dos últimos 7 dias
- Lista de obras ativas
- Últimos RDOs criados

### ✅ Controles e Filtros
- Filtrar por obra, status e data
- Busca avançada
- Ordenação personalizada

### ✅ Segurança
- Sistema de login com criptografia
- Níveis de permissão (Admin, Engenheiro, Fiscal, Operacional)
- Sessões seguras

## 📁 Estrutura de Arquivos

```
sistema_rdo/
├── config.php                  # Configurações e conexão BD
├── login.php                   # Tela de login
├── logout.php                  # Logout
├── dashboard.php               # Dashboard principal
├── rdos.php                    # Listagem de RDOs
├── rdo_criar.php              # Criar/Editar RDO
├── rdo_salvar.php             # Processar salvamento
├── rdo_visualizar.php         # Visualizar RDO completo
├── rdo_excluir.php            # Excluir RDO
├── rdo_aprovar.php            # Processar aprovação
├── aprovacoes.php             # Painel de aprovações
├── api_rdos_chart.php         # API para gráficos
└── uploads/                   # Pasta de uploads
    └── rdos/                  # Fotos dos RDOs
```

## 🔧 Instalação

### 1. Requisitos
- PHP 7.4 ou superior
- MySQL 5.7 ou superior
- Apache/Nginx
- Extensões PHP: PDO, PDO_MySQL, GD (para imagens)

### 2. Configuração do Banco de Dados

Execute o script SQL fornecido (`rdo_database.sql`) no seu MySQL:

```sql
mysql -u root -p < rdo_database.sql
```

Ou importe via phpMyAdmin.

### 3. Configurar Conexão

Edite o arquivo `config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'sistema_rdo');
define('DB_USER', 'root');
define('DB_PASS', 'sua_senha');

define('SITE_URL', 'http://localhost/sistema_rdo');
```

### 4. Criar Pasta de Uploads

```bash
mkdir -p uploads/rdos
chmod 777 uploads
```

### 5. Acessar o Sistema

Abra no navegador: `http://localhost/sistema_rdo/login.php`

**Login padrão:**
- Email: `admin@sistema.com`
- Senha: `admin123`

## 👥 Tipos de Usuário

### Admin
- Acesso total ao sistema
- Criar/editar/excluir RDOs
- Aprovar/rejeitar RDOs
- Gerenciar usuários e obras

### Engenheiro / Fiscal
- Visualizar todos os RDOs
- Aprovar/rejeitar RDOs
- Criar RDOs
- Acesso ao painel de aprovações

### Operacional
- Criar e editar seus próprios RDOs
- Visualizar RDOs
- Sem permissão de aprovação

## 📊 Workflow de Aprovação

1. **Criação**: Usuário cria RDO e pode salvá-lo como rascunho
2. **Envio**: Quando completo, envia para aprovação (status: "Enviado")
3. **Aprovação**: Engenheiro/Fiscal/Admin revisa e:
   - **Aprova**: RDO fica com status "Aprovado"
   - **Rejeita**: RDO volta com status "Rejeitado" + comentários
4. **Notificação**: Criador recebe email com resultado
5. **Histórico**: Todas as ações ficam registradas

## 🎨 Recursos Visuais

- Interface moderna com Bootstrap 5
- Design responsivo (mobile-friendly)
- Gradientes e animações suaves
- Ícones Bootstrap Icons
- Gráficos com Chart.js
- Cards interativos

## 🔐 Segurança

- Senhas criptografadas com bcrypt
- Proteção contra SQL Injection (prepared statements)
- Sessões seguras com httpOnly
- Validação de permissões em todas as páginas
- Sanitização de inputs

## 📧 Notificações por Email

O sistema está preparado para enviar emails automáticos quando:
- RDO é aprovado
- RDO é rejeitado

Para ativar, descomente a linha no arquivo `rdo_aprovar.php`:

```php
// Linha 101
mail($criador['email'], $assunto, $mensagem, $headers);
```

E configure o servidor SMTP do seu servidor.

## 🎯 Próximas Melhorias Sugeridas

- [ ] Geração de PDF dos RDOs
- [ ] Relatórios gerenciais avançados
- [ ] Dashboard com mais gráficos
- [ ] Assinatura digital
- [ ] App mobile
- [ ] Integração com WhatsApp
- [ ] Controle de estoque de materiais
- [ ] Cronograma de obras
- [ ] Gestão de funcionários completa

## 🐛 Troubleshooting

### Erro de Conexão com BD
Verifique as credenciais em `config.php` e se o MySQL está rodando.

### Erro ao fazer upload de fotos
Verifique permissões da pasta `uploads/`:
```bash
chmod 777 uploads -R
```

### Página em branco
Ative exibição de erros no PHP:
```php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

## 📝 Licença

Este sistema foi desenvolvido para fins educacionais e comerciais.

## 👨‍💻 Suporte

Para dúvidas ou problemas, entre em contato.

---

**Sistema desenvolvido com ❤️ em PHP**
