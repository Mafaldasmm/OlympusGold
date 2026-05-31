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

## Regenerar seed

O ficheiro `seed.sql` pode ser regenerado com:

```bash
php database/generate_seed.php > database/seed.sql
```

## Utilizadores de demonstração

**53 utilizadores:** 10 treinadores, 40 membros, 3 administradores.

| Conta demo | Password | Role |
|------------|----------|------|
| `admin` | `demo123` | admin |
| `ana.silva` | `demo123` | trainer |
| `joao.membro` | `demo123` | member |

Todas as outras contas de seed usam a password **`OgGym2026!`** (nomes e emails realistas).

## Ligação PDO na aplicação

```php
require_once dirname(__DIR__) . '/includes/database.php';

$pdo = getDatabase();
$stmt = dbQuery('SELECT title FROM classes WHERE id = :id', ['id' => 1]);
$class = $stmt->fetch();
```

Ver documentação completa em [`docs/implementacao-base-de-dados.md`](../docs/implementacao-base-de-dados.md).
