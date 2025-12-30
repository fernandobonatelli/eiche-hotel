# 🏨 Pousada Bona - Sistema de Hotelaria v2.0

Sistema moderno de gestão hoteleira com interface intuitiva e responsiva.

## ✨ Novidades da Versão 2.0

- **PHP 8.x** - Código totalmente modernizado com tipos estritos
- **PDO** - Conexão segura com prepared statements
- **BCrypt** - Senhas com hash seguro
- **Interface Moderna** - Design responsivo com dark mode
- **CSS Variables** - Tema customizável
- **Sem dependências pesadas** - CSS e JS puros, sem frameworks

## 📋 Requisitos

- PHP 8.1 ou superior
- MySQL 5.7 ou superior (recomendado MariaDB 10.3+)
- Extensões PHP:
  - pdo
  - pdo_mysql
  - mbstring
  - json
  - gd

## 🚀 Instalação

### 1. Configurar Banco de Dados

Edite o arquivo `config/database.php` com suas credenciais:

```php
$this->host = 'localhost';
$this->database = 'seu_banco';
$this->username = 'seu_usuario';
$this->password = 'sua_senha';
```

Ou use variáveis de ambiente:

```env
DB_HOST=localhost
DB_DATABASE=eiche_hotel
DB_USERNAME=root
DB_PASSWORD=senha
```

### 2. Executar Migração do Banco

Execute o script SQL para atualizar a estrutura:

```bash
mysql -u usuario -p banco < migration/update_database.sql
```

### 3. Migrar Senhas (se vindo da v1)

Acesse pelo navegador:
```
http://seu-site/v2/migration/migrate_passwords.php
```

⚠️ **IMPORTANTE:** Este script só pode ser executado uma vez!

### 4. Configurar Servidor Web

#### Apache (.htaccess)
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

#### Nginx
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

## 📁 Estrutura de Pastas

```
v2/
├── app/                    # Código da aplicação
│   ├── Helpers/           # Funções auxiliares
│   ├── Models/            # Modelos (futuro)
│   └── Controllers/       # Controllers (futuro)
├── config/                # Configurações
│   ├── config.php        # Configurações gerais
│   └── database.php      # Conexão com banco
├── migration/             # Scripts de migração
├── public/                # Arquivos públicos
│   ├── assets/           # CSS, JS, imagens
│   │   ├── css/         # Estilos
│   │   ├── js/          # JavaScript
│   │   └── images/      # Imagens
│   ├── index.php        # Entrada principal
│   ├── login.php        # Página de login
│   ├── dashboard.php    # Dashboard
│   └── logout.php       # Logout
├── storage/              # Arquivos gerados
│   ├── logs/            # Logs do sistema
│   └── uploads/         # Uploads
└── composer.json         # Dependências
```

## 🎨 Personalização do Tema

### Cores

Edite `public/assets/css/variables.css`:

```css
:root {
    --primary-500: #0d8fdb;  /* Cor principal */
    --accent-500: #e67e22;   /* Cor de destaque */
}
```

### Dark Mode

O sistema suporta dark mode automaticamente. O usuário pode alternar manualmente ou usar a preferência do sistema.

## 🔐 Segurança

- Senhas com BCrypt (custo 10)
- Prepared Statements (PDO)
- CSRF Protection (em desenvolvimento)
- XSS Prevention com `htmlspecialchars()`
- Session Fixation Protection

## 📝 Migrando da v1

1. **Backup completo** do banco e arquivos
2. Execute `migration/update_database.sql`
3. Execute `migration/migrate_passwords.php`
4. Atualize as configurações em `config/`
5. Teste em ambiente de desenvolvimento

### Compatibilidade

Os módulos da v1 podem ser gradualmente migrados. A estrutura de banco foi mantida compatível.

## 🛠️ Desenvolvimento

### Composer (opcional)

```bash
composer install
composer dump-autoload
```

### Padrões de Código

- PSR-4 Autoloading
- PSR-12 Coding Style
- Tipos estritos (`declare(strict_types=1)`)

## 📄 Licença

GNU General Public License v3.0
---

Desenvolvido com ❤️ para Pousada Bona

