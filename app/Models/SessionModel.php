<?php
declare(strict_types=1);

class SessionModel implements SessionHandlerInterface
{
    private PDO $db;

    public static function register(): void
    {
        session_set_save_handler(new self(), true);
    }

    public function __construct()
    {
        if (!is_dir(dirname(DB_FILE))) {
            mkdir(dirname(DB_FILE), 0775, true);
        }

        $this->db = new PDO('sqlite:' . DB_FILE);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db->exec('PRAGMA busy_timeout = 5000');
        $this->db->exec("CREATE TABLE IF NOT EXISTS sessions (
            id TEXT PRIMARY KEY,
            data TEXT NOT NULL,
            expires_at INTEGER NOT NULL,
            updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )");
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string
    {
        $stmt = $this->db->prepare('SELECT data FROM sessions WHERE id = ? AND expires_at >= ?');
        $stmt->execute([$id, time()]);

        return (string) ($stmt->fetchColumn() ?: '');
    }

    public function write(string $id, string $data): bool
    {
        $expiresAt = time() + (int) ini_get('session.gc_maxlifetime');

        $stmt = $this->db->prepare(
            'INSERT INTO sessions (id, data, expires_at, updated_at)
             VALUES (?, ?, ?, CURRENT_TIMESTAMP)
             ON CONFLICT(id) DO UPDATE SET
                data = excluded.data,
                expires_at = excluded.expires_at,
                updated_at = CURRENT_TIMESTAMP'
        );

        return $stmt->execute([$id, $data, $expiresAt]);
    }

    public function destroy(string $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM sessions WHERE id = ?');

        return $stmt->execute([$id]);
    }

    public function gc(int $max_lifetime)
    {
        $stmt = $this->db->prepare('DELETE FROM sessions WHERE expires_at < ?');
        $stmt->execute([time()]);

        return $stmt->rowCount();
    }
}
