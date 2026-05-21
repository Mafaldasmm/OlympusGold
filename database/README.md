# Base de dados SQLite — Olympus Gold Gym

## Ficheiros

| Ficheiro | Descrição |
|----------|-----------|
| `schema.sql` | Criação das tabelas, índices e restrições |
| `seed.sql` | Dados de demonstração (utilizadores, aulas, inscrições, reviews) |
| `init.php` | Script PHP que cria `olympus.sqlite` automaticamente |
| `olympus.sqlite` | Base de dados gerada localmente (não versionada no Git) |

## Inicialização rápida

```bash
php database/init.php
```

Para recriar a base de dados do zero:

```bash
php database/init.php --force
```

## Alternativa com sqlite3 (CLI)

```bash
cd database
sqlite3 olympus.sqlite < schema.sql
sqlite3 olympus.sqlite < seed.sql
```

## Utilizadores de demonstração

Password para todas as contas de seed: **demo123**

| Username | Role |
|----------|------|
| `joao.membro` | member |
| `ana.silva` | trainer |
| `sofia.costa` | trainer |
| `marco.rebelo` | trainer |
| `ricardo.pereira` | trainer |
| `admin` | admin |

## Ligação PDO na aplicação

```php
require_once dirname(__DIR__) . '/includes/database.php';

$pdo = getDatabase();
$stmt = dbQuery('SELECT title FROM classes WHERE id = :id', ['id' => 1]);
$class = $stmt->fetch();
```

Ver documentação completa em [`docs/implementacao-base-de-dados.md`](../docs/implementacao-base-de-dados.md).
