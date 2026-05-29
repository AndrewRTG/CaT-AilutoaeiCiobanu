<?php
declare(strict_types=1);

class DatabaseModel
{
    private static ?PDO $db = null;

    public static function connect(): PDO
    {
        if (self::$db) {
            return self::$db;
        }

        if (!is_dir(dirname(DB_FILE))) {
            mkdir(dirname(DB_FILE), 0775, true);
        }
        if (!is_dir(UPLOAD_DIR)) {
            mkdir(UPLOAD_DIR, 0775, true);
        }

        self::$db = new PDO('sqlite:' . DB_FILE);
        self::$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        self::$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        self::$db->exec('PRAGMA foreign_keys = ON');
        self::$db->exec('PRAGMA busy_timeout = 5000');

        self::createTables();
        self::seedData();

        return self::$db;
    }

    private static function createTables(): void
    {
        $db = self::$db;

        $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        provider TEXT NOT NULL,
        provider_id TEXT NOT NULL,
        name TEXT NOT NULL,
        email TEXT,
        password_hash TEXT,
        role TEXT NOT NULL DEFAULT 'member',
        status TEXT NOT NULL DEFAULT 'active',
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(provider, provider_id)
    )");



        $db->exec("CREATE TABLE IF NOT EXISTS auth_tokens (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    token_hash TEXT NOT NULL UNIQUE,
    expires_at TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
)");

        $db->exec("CREATE TABLE IF NOT EXISTS campings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            slug TEXT NOT NULL UNIQUE,
            zone TEXT NOT NULL,
            description TEXT NOT NULL,
            price_per_night REAL NOT NULL,
            rating REAL NOT NULL DEFAULT 0,
            latitude REAL NOT NULL,
            longitude REAL NOT NULL,
            image_url TEXT NOT NULL,
            capacity INTEGER NOT NULL DEFAULT 30,
            facilities TEXT NOT NULL DEFAULT '',
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )");

        self::addMissingColumn('campings', 'facilities', "TEXT NOT NULL DEFAULT ''");
        self::addMissingColumn('users', 'status', "TEXT NOT NULL DEFAULT 'active'");
        self::addMissingColumn('users', 'password_hash', "TEXT");

        $db->exec("CREATE TABLE IF NOT EXISTS reservations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            camping_id INTEGER NOT NULL,
            start_date TEXT NOT NULL,
            end_date TEXT NOT NULL,
            guests INTEGER NOT NULL,
            status TEXT NOT NULL DEFAULT 'pending',
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )");

        $db->exec("CREATE TABLE IF NOT EXISTS reviews (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            camping_id INTEGER NOT NULL,
            rating INTEGER NOT NULL,
            comment TEXT NOT NULL,
            media_type TEXT,
            media_path TEXT,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )");

        $db->exec("CREATE TABLE IF NOT EXISTS messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            camping_id INTEGER,
            content TEXT NOT NULL,
            media_type TEXT,
            media_path TEXT,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )");
    }

    private static function addMissingColumn(string $table, string $column, string $definition): void
    {
        $columns = self::$db->query("PRAGMA table_info($table)")->fetchAll();
        foreach ($columns as $info) {
            if ($info['name'] === $column) {
                return;
            }
        }

        self::$db->exec("ALTER TABLE $table ADD COLUMN $column $definition");
    }

    private static function seedData(): void
    {
        UserModel::demoUserId('admin');
        UserModel::demoUserId('member');

        if ((int) self::$db->query('SELECT COUNT(*) FROM campings')->fetchColumn() > 0) {
            return;
        }

        $campings = [
            ['Green Valley', 'Apuseni', 'Camping linistit la marginea padurii, cu trasee usoare si dusuri.', 120, 4.9, 46.5076, 22.8141, 'https://images.unsplash.com/photo-1504851149312-7a075b496cc7?auto=format&fit=crop&w=1000&q=80', 45, 'Wi-Fi,Dusuri,Pet friendly,Foc de tabara'],
            ['Lake Horizon', 'Bucovina', 'Camping langa lac, potrivit pentru caiac, pescuit si seri calme pe ponton.', 180, 4.7, 47.7009, 25.8876, 'https://images.unsplash.com/photo-1537905569824-f89f14cceb68?auto=format&fit=crop&w=1000&q=80', 30, 'Ponton,Caiac,Parcare,Electricitate'],
            ['Forest Nest', 'Brasov', 'Camping aproape de trasee montane si puncte de belvedere.', 95, 4.6, 45.6427, 25.5887, 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?auto=format&fit=crop&w=1000&q=80', 25, 'Trasee,Gratar,Apa potabila,Zona hamace'],
            ['Delta Wild Camp', 'Delta Dunarii', 'Camping pentru excursii pe canale si observarea naturii.', 150, 4.5, 45.1716, 29.3912, 'https://images.unsplash.com/photo-1478131143081-80f7f84ca84d?auto=format&fit=crop&w=1000&q=80', 22, 'Barca,Ghid local,Foto hide,Dusuri'],
        ];

        foreach ($campings as $camping) {
            $id = CampingModel::save([
                'name' => $camping[0],
                'zone' => $camping[1],
                'description' => $camping[2],
                'price_per_night' => $camping[3],
                'rating' => $camping[4],
                'latitude' => $camping[5],
                'longitude' => $camping[6],
                'image_url' => $camping[7],
                'capacity' => $camping[8],
                'facilities' => $camping[9],
            ]);

            ReviewModel::create(UserModel::demoUserId('member'), $id, 5, 'Experienta foarte buna, loc curat si usor de rezervat.', null, null);
        }
    }
}
