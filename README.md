# 📍 GeoDash - Sistema de Geolocalização

Sistema completo para cadastro, gerenciamento e visualização de pontos geográficos com mapa interativo.

![PHP](https://img.shields.io/badge/PHP-7.4+-777BB4?style=flat&logo=php)
![SQLite](https://img.shields.io/badge/SQLite-3-003B57?style=flat&logo=sqlite)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=flat&logo=bootstrap)
![Leaflet](https://img.shields.io/badge/Leaflet-1.9-199900?style=flat&logo=leaflet)

---

## ✨ Funcionalidades

### 📌 Gestão de Pontos

- ✅ Cadastro de pontos com descrição e coordenadas
- ✅ Edição e exclusão de pontos
- ✅ Listagem em tabela com ordenação
- ✅ Clique no mapa para capturar coordenadas
- ✅ Localização visual de pontos no mapa

### 🗺️ Mapa Interativo

- ✅ Visualização com OpenStreetMap
- ✅ Marcadores com popups informativos
- ✅ Centralização automática em todos os pontos
- ✅ Zoom e navegação fluidos

### 📊 Importação/Exportação

- ✅ Exportação para CSV
- ✅ Importação de dados em lote
- ✅ Dados de exemplo pré-configurados

### 🎨 Interface Moderna

- ✅ Design responsivo (mobile-friendly)
- ✅ Notificações toast elegantes
- ✅ Validação em tempo real
- ✅ Feedback visual em todas as ações
- ✅ Loading overlay para operações

---

## 🚀 Instalação Rápida

### Pré-requisitos

```bash
PHP 7.4 ou superior
Extensão PDO SQLite habilitada
Servidor web (Apache, Nginx, ou PHP built-in)
```

### Passos

1. **Clone ou baixe os arquivos:**

```bash
git clone [seu-repositorio]
cd geodash
```

1. **Estrutura de arquivos:**

```
geodash/
├── api.php          # Backend API
├── index.php        # Frontend
└── geodata.db       # (criado automaticamente)
```

1. **Configurar permissões (Linux/Mac):**

```bash
chmod 755 .
chmod 666 geodata.db  # após primeira execução
```

1. **Iniciar servidor:**

**Opção 1: PHP Built-in Server (desenvolvimento)**

```bash
php -S localhost:8000
```

**Opção 2: Apache/Nginx**

```bash
# Copie os arquivos para /var/www/html ou seu diretório web
cp * /var/www/html/geodash/
```

1. **Acessar:**

```
http://localhost:8000
```

ou

```
http://localhost/geodash
```

---

## 📖 Como Usar

### 1️⃣ Adicionar um Ponto

**Método 1: Clique no Mapa**

1. Clique em qualquer lugar do mapa
2. As coordenadas serão preenchidas automaticamente
3. Digite uma descrição
4. Clique em "Gravar no Servidor"

**Método 2: Manual**

1. Digite a descrição
2. Digite latitude e longitude
3. Clique em "Gravar no Servidor"

### 2️⃣ Editar um Ponto

1. Clique no botão "Editar" (ícone de lápis) na tabela
2. Modifique os dados no formulário
3. Clique em "Atualizar Registro"

### 3️⃣ Excluir um Ponto

1. Clique no botão "Excluir" (ícone de lixeira)
2. Confirme a exclusão

### 4️⃣ Localizar no Mapa

1. Clique no botão "Localizar" (ícone de olho)
2. O mapa será centralizado no ponto
3. Um popup será aberto automaticamente

### 5️⃣ Exportar Dados

1. Clique em "Exportar CSV"
2. O arquivo será baixado automaticamente
3. Formato: `geodash_export_YYYY-MM-DD.csv`

### 6️⃣ Importar Dados de Exemplo

1. Clique em "Importar Dados Iniciais"
2. Confirme a importação
3. 5 pontos de exemplo serão adicionados

---

## 🔧 Configuração Avançada

### Alterar Coordenadas Iniciais do Mapa

Edite em `index.php`:

```javascript
const CENTRO_INICIAL = [-22.28, -45.93];  // [latitude, longitude]
const ZOOM_INICIAL = 13;                   // 1-19
```

### Alterar Nome do Banco de Dados

Edite em `api.php`:

```php
private $dbFile = 'geodata.db';  // Seu nome aqui
```

### Personalizar Pontos de Exemplo

Edite em `index.php`:

```javascript
function importarDadosIniciais() {
    const dadosExemplo = [
        { desc: "Sua descrição", lat: -22.28, lng: -45.93 },
        // Adicione mais pontos aqui
    ];
}
```

---

## 🛡️ Segurança

### Medidas Implementadas

✅ **SQL Injection** - Prepared statements  
✅ **XSS** - Sanitização HTML  
✅ **CSRF** - Validação de origem  
✅ **Clickjacking** - X-Frame-Options  
✅ **Input Validation** - Front-end e back-end  
✅ **Error Handling** - Mensagens genéricas ao usuário  

### Recomendações para Produção

1. **HTTPS obrigatório**

```apache
# .htaccess
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

1. **Autenticação de usuários**

```php
// Adicione sistema de login antes de permitir acesso
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
```

1. **Rate Limiting**

```php
// Limite de requisições por IP
// Implemente com Redis ou arquivo de cache
```

1. **Backup automático**

```bash
# Cron job diário
0 2 * * * cp /var/www/html/geodata.db /backup/geodata_$(date +\%Y\%m\%d).db
```

---

## 🐛 Troubleshooting

### Problema: "Erro ao conectar ao banco de dados"

**Solução:**

```bash
# Verifique se a extensão SQLite está ativa
php -m | grep sqlite

# Se não estiver, instale:
# Ubuntu/Debian
sudo apt-get install php-sqlite3

# CentOS/RHEL
sudo yum install php-sqlite3

# Reinicie o servidor web
sudo systemctl restart apache2
```

### Problema: "Erro ao salvar ponto"

**Solução:**

```bash
# Verifique permissões do banco
ls -l geodata.db

# Dê permissão de escrita
chmod 666 geodata.db
chmod 777 .  # diretório também precisa de escrita
```

### Problema: Mapa não carrega

**Solução:**

1. Verifique conexão com internet (OpenStreetMap é externo)
2. Abra o console do navegador (F12)
3. Verifique erros de JavaScript
4. Limpe cache do navegador

### Problema: Coordenadas inválidas

**Solução:**

```
Latitude: -90 a 90
Longitude: -180 a 180

Exemplos válidos:
Pouso Alegre/MG: -22.2303, -45.9364
São Paulo/SP: -23.5505, -46.6333
Rio de Janeiro/RJ: -22.9068, -43.1729
```

---

## 📡 API Endpoints

### Listar Pontos

```http
GET /api.php?action=listar
```

**Resposta:**

```json
{
    "status": "success",
    "message": "Operação realizada com sucesso",
    "data": [
        {
            "id": 1,
            "descricao": "Ponto 1",
            "lat": -22.28,
            "lng": -45.93,
            "data_criacao": "2024-01-15 10:30:00"
        }
    ]
}
```

### Salvar Ponto

```http
POST /api.php?action=salvar
Content-Type: application/x-www-form-urlencoded

id=-1&desc=Novo Ponto&lat=-22.28&lng=-45.93
```

### Deletar Ponto

```http
POST /api.php?action=deletar
Content-Type: application/x-www-form-urlencoded

id=1
```

### Limpar Banco

```http
GET /api.php?action=limpar
```

### Importar Pontos

```http
POST /api.php?action=importar
Content-Type: application/x-www-form-urlencoded

pontos=[{"desc":"Ponto 1","lat":-22.28,"lng":-45.93}]
```

---

## 🧪 Testes

### Teste Manual

1. **CRUD Completo:**

```
✓ Criar ponto
✓ Editar ponto
✓ Deletar ponto
✓ Listar pontos
```

1. **Validações:**

```
✓ Latitude > 90 → erro
✓ Longitude < -180 → erro
✓ Descrição vazia → erro
✓ Coordenadas inválidas → erro
```

1. **Funcionalidades:**

```
✓ Clique no mapa captura coordenadas
✓ Localizar ponto abre popup
✓ Exportar CSV gera arquivo
✓ Importar adiciona múltiplos pontos
```

---

## 📊 Estrutura do Banco de Dados

```sql
CREATE TABLE pontos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    descricao TEXT NOT NULL,
    lat REAL NOT NULL,
    lng REAL NOT NULL,
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

**Constraints:**

- `id`: Auto-incremento, chave primária
- `descricao`: Obrigatório, até 200 caracteres
- `lat`: -90 a 90
- `lng`: -180 a 180
- `data_criacao`: Timestamp automático
- `data_atualizacao`: Atualizado em cada UPDATE

---

## 📈 Performance

### Otimizações Implementadas

✅ Cache local de dados no front-end  
✅ Prepared statements no banco  
✅ Carregamento assíncrono de dados  
✅ Debounce em validações  
✅ Lazy loading de marcadores  

### Benchmarks Aproximados

| Operação | Tempo | Requisições |
|----------|-------|-------------|
| Listar 100 pontos | ~50ms | 1 |
| Salvar ponto | ~30ms | 1 |
| Exportar CSV | ~100ms | 0 |
| Renderizar mapa | ~200ms | 1 |

---

## 🤝 Contribuindo

Contribuições são bem-vindas! Sinta-se à vontade para:

1. Fazer fork do projeto
2. Criar uma branch (`git checkout -b feature/MinhaFeature`)
3. Commit suas mudanças (`git commit -m 'Adiciona MinhaFeature'`)
4. Push para a branch (`git push origin feature/MinhaFeature`)
5. Abrir um Pull Request

---

## 📝 Changelog

### v2.0.0 (2024-01-15)

- ✨ Reescrita completa em OOP
- ✨ Interface Bootstrap 5
- ✨ Sistema de notificações
- ✨ Exportação CSV
- ✨ Importação em lote
- 🔒 Segurança aprimorada
- 🐛 Correção de bugs de validação

### v1.0.0 (2023-12-01)

- 🎉 Lançamento inicial
- Funcionalidades básicas de CRUD

---

## 📄 Licença

Este projeto é de código aberto e está disponível sob a licença MIT.

---

## 👨‍💻 Autor

Desenvolvido com ❤️ e boas práticas de programação.

---

## 📞 Suporte

- 📧 Email: [seu-email@exemplo.com]
- 🐛 Issues: [GitHub Issues]
- 📖 Docs: [MELHORIAS.md](MELHORIAS.md)

---

## 🌟 Screenshots

### Tela Principal

Interface moderna com mapa interativo, formulário e tabela de dados.

### Funcionalidades

- Clique no mapa para adicionar pontos
- Edição visual com destaque
- Notificações elegantes
- Exportação com um clique

---

## ⭐ Star History

Se este projeto foi útil para você, considere dar uma estrela! ⭐

---

**Made with 💙 by [Seu Nome]**
