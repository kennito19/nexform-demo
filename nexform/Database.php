<?php
namespace NexForm;

/**
 * Database – stores and retrieves form submissions.
 * Supports SQLite (zero-config) and MySQL.
 */
class Database
{
    private \PDO $pdo;

    public function __construct()
    {
        $driver = NF_DB_DRIVER;

        if ($driver === 'sqlite') {
            $path = NF_DB_SQLITE_PATH;
            $dir  = dirname($path);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $this->pdo = new \PDO('sqlite:' . $path);
        } elseif ($driver === 'mysql') {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                NF_DB_MYSQL_HOST, NF_DB_MYSQL_PORT, NF_DB_MYSQL_NAME
            );
            $this->pdo = new \PDO($dsn, NF_DB_MYSQL_USER, NF_DB_MYSQL_PASS);
        } else {
            throw new \RuntimeException("Unsupported DB driver: {$driver}");
        }

        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        $this->createTable();
    }

    /** Ensure submissions table exists. */
    private function createTable(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS nf_submissions (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                form_id     VARCHAR(100)  NOT NULL DEFAULT 'default',
                fields      TEXT          NOT NULL,
                ip_address  VARCHAR(45)   NOT NULL DEFAULT '',
                user_agent  TEXT          NOT NULL DEFAULT '',
                created_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }

    /**
     * Save a submission.
     *
     * @param  array  $fields   Filtered field data
     * @param  string $formId   Identifier for the form (e.g. 'contact', 'quote')
     * @return int              New row ID
     */
    public function save(array $fields, string $formId = 'default'): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO nf_submissions (form_id, fields, ip_address, user_agent)
            VALUES (:form_id, :fields, :ip, :ua)
        ");

        $stmt->execute([
            ':form_id' => $formId,
            ':fields'  => json_encode($fields, JSON_UNESCAPED_UNICODE),
            ':ip'      => $_SERVER['REMOTE_ADDR']          ?? '',
            ':ua'      => $_SERVER['HTTP_USER_AGENT']      ?? '',
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Retrieve paginated submissions.
     *
     * @param  int    $page     1-based page number
     * @param  int    $perPage  Rows per page
     * @param  string $formId   Filter by form id, or '' for all
     * @return array            ['rows' => [...], 'total' => N]
     */
    public function getAll(int $page = 1, int $perPage = 20, string $formId = ''): array
    {
        $offset = ($page - 1) * $perPage;
        $where  = $formId ? 'WHERE form_id = :fid' : '';

        $countSql = "SELECT COUNT(*) FROM nf_submissions {$where}";
        $dataSql  = "SELECT * FROM nf_submissions {$where} ORDER BY id DESC LIMIT :limit OFFSET :offset";

        $params = $formId ? [':fid' => $formId] : [];

        $countStmt = $this->pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $dataStmt = $this->pdo->prepare($dataSql);
        if ($formId) $dataStmt->bindValue(':fid', $formId);
        $dataStmt->bindValue(':limit',  $perPage, \PDO::PARAM_INT);
        $dataStmt->bindValue(':offset', $offset,  \PDO::PARAM_INT);
        $dataStmt->execute();
        $rows = $dataStmt->fetchAll();

        foreach ($rows as &$row) {
            $row['fields'] = json_decode($row['fields'], true);
        }

        return ['rows' => $rows, 'total' => $total];
    }

    /** Delete a submission by ID. */
    public function delete(int $id): void
    {
        $this->pdo->prepare("DELETE FROM nf_submissions WHERE id = :id")
                  ->execute([':id' => $id]);
    }

    /** Return all submissions as a flat array suitable for CSV export. */
    public function exportAll(string $formId = ''): array
    {
        $where  = $formId ? 'WHERE form_id = :fid' : '';
        $stmt   = $this->pdo->prepare("SELECT * FROM nf_submissions {$where} ORDER BY id DESC");
        $params = $formId ? [':fid' => $formId] : [];
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $flat = [];
        foreach ($rows as $row) {
            $fields = json_decode($row['fields'], true) ?? [];
            $flat[] = array_merge([
                'id'         => $row['id'],
                'form_id'    => $row['form_id'],
                'ip_address' => $row['ip_address'],
                'created_at' => $row['created_at'],
            ], $fields);
        }
        return $flat;
    }
}
