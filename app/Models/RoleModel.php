<?php
declare(strict_types=1);

class RoleModel
{
    public static function seedDefaults(): void
    {
        self::ensureRole('admin', 'Administrator', [
            'manage_campings',
            'manage_reservations',
            'manage_users',
            'view_stats',
            'import_export',
            'manage_roles',
        ], true);

        self::ensureRole('member', 'Membru', [], true);

        self::ensureRole('moderator', 'Moderator', [
            'moderate_messages',
        ], true);
    }

    public static function all(): array
    {
        $rows = db()->query(
            'SELECT slug, name, permissions, is_system, created_at
             FROM roles
             ORDER BY is_system DESC, name ASC'
        )->fetchAll();

        return array_map([self::class, 'payload'], $rows);
    }

    public static function exists(string $slug): bool
    {
        $stmt = db()->prepare('SELECT COUNT(*) FROM roles WHERE slug = ?');
        $stmt->execute([self::normalizeSlug($slug)]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public static function create(string $name, array $permissions = []): array
    {
        $name = clean($name, 80);
        $slug = self::normalizeSlug(slug($name));

        if ($name === '' || $slug === '') {
            json_response(['error' => 'Numele rolului este obligatoriu.'], 422);
        }

        if (self::exists($slug)) {
            json_response(['error' => 'Exista deja un rol cu acest nume.'], 409);
        }

        $stmt = db()->prepare(
            'INSERT INTO roles (slug, name, permissions, is_system)
             VALUES (?, ?, ?, 0)'
        );

        $stmt->execute([
            $slug,
            $name,
            json_encode(array_values($permissions), JSON_UNESCAPED_SLASHES),
        ]);

        return self::find($slug);
    }

    public static function update(string $slug, string $name, array $permissions = []): array
    {
        $slug = self::normalizeSlug($slug);
        $name = clean($name, 80);

        if (!self::exists($slug)) {
            json_response(['error' => 'Rolul nu exista.'], 404);
        }

        if ($name === '') {
            json_response(['error' => 'Numele rolului este obligatoriu.'], 422);
        }

        $stmt = db()->prepare(
            'UPDATE roles
             SET name = ?, permissions = ?
             WHERE slug = ?'
        );

        $stmt->execute([
            $name,
            json_encode(array_values($permissions), JSON_UNESCAPED_SLASHES),
            $slug,
        ]);

        return self::find($slug);
    }

    public static function delete(string $slug): void
    {
        $slug = self::normalizeSlug($slug);

        $stmt = db()->prepare('SELECT is_system FROM roles WHERE slug = ?');
        $stmt->execute([$slug]);
        $role = $stmt->fetch();

        if (!$role) {
            json_response(['error' => 'Rolul nu exista.'], 404);
        }

        if ((int) $role['is_system'] === 1) {
            json_response(['error' => 'Rolurile de sistem nu pot fi sterse.'], 422);
        }

        db()->prepare('UPDATE users SET role = ? WHERE role = ?')->execute(['member', $slug]);
        db()->prepare('DELETE FROM roles WHERE slug = ?')->execute([$slug]);
    }

    public static function find(string $slug): array
    {
        $stmt = db()->prepare(
            'SELECT slug, name, permissions, is_system, created_at
             FROM roles
             WHERE slug = ?'
        );

        $stmt->execute([self::normalizeSlug($slug)]);
        $row = $stmt->fetch();

        if (!$row) {
            json_response(['error' => 'Rolul nu exista.'], 404);
        }

        return self::payload($row);
    }

    public static function permissionsForRole(string $slug): array
        {
            $stmt = db()->prepare('SELECT permissions FROM roles WHERE slug = ?');
            $stmt->execute([self::normalizeSlug($slug)]);
            $permissions = json_decode((string) $stmt->fetchColumn(), true);

            return is_array($permissions) ? $permissions : [];
        }

        public static function hasPermission(string $slug, string $permission): bool
        {
            return in_array($permission, self::permissionsForRole($slug), true);
        }

    private static function ensureRole(string $slug, string $name, array $permissions, bool $isSystem): void
    {
        $stmt = db()->prepare(
            'INSERT OR IGNORE INTO roles (slug, name, permissions, is_system)
             VALUES (?, ?, ?, ?)'
        );

        $stmt->execute([
            self::normalizeSlug($slug),
            $name,
            json_encode(array_values($permissions), JSON_UNESCAPED_SLASHES),
            $isSystem ? 1 : 0,
        ]);
    }

    private static function payload(array $row): array
    {
        $permissions = json_decode((string) $row['permissions'], true);

        return [
            'slug' => $row['slug'],
            'name' => $row['name'],
            'permissions' => is_array($permissions) ? $permissions : [],
            'is_system' => (int) $row['is_system'] === 1,
            'created_at' => $row['created_at'],
        ];
    }

    private static function normalizeSlug(string $slug): string
    {
        $slug = strtolower(trim($slug));
        $slug = preg_replace('/[^a-z0-9_ -]+/', '', $slug) ?: '';
        $slug = preg_replace('/[\s-]+/', '_', $slug) ?: '';

        return trim($slug, '_');
    }

    
}