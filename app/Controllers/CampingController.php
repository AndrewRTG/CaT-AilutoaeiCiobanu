<?php
declare(strict_types=1);

class CampingController
{
    public static function handle(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];

        if ($method === 'GET') {
            self::indexOrShow();
        }
        if ($method === 'POST') {
            self::create();
        }
        if ($method === 'PATCH') {
            self::update();
        }
        if ($method === 'DELETE') {
            self::delete();
        }

        json_response(['error' => 'Metoda nepermisa.'], 405);
    }

    private static function indexOrShow(): void
    {
        if (!empty($_GET['id'])) {
            $id = (int) $_GET['id'];
            $camping = CampingModel::find($id);
            if (!$camping) {
                json_response(['error' => 'Campingul nu exista.'], 404);
            }

            json_response([
                'camping' => $camping,
                'reviews' => ReviewModel::forCamping($id),
                'messages' => MessageModel::forCamping($id),
            ]);
        }

        json_response([
            'campings' => CampingModel::all($_GET['search'] ?? '', $_GET['zone'] ?? 'all'),
            'zones' => CampingModel::zones(),
        ]);
    }

    private static function create(): void
    {
        require_admin();
        $id = CampingModel::save(body_json());
        json_response(['camping' => CampingModel::find($id)], 201);
    }

    private static function update(): void
    {
        require_admin();
        $id = (int) ($_GET['id'] ?? 0);
        $current = CampingModel::rawFind($id);
        if (!$current) {
            json_response(['error' => 'Campingul nu exista.'], 404);
        }

        CampingModel::save(array_merge($current, body_json()), $id);
        json_response(['ok' => true]);
    }

    private static function delete(): void
    {
        require_admin();
        CampingModel::delete((int) ($_GET['id'] ?? 0));
        json_response(['ok' => true]);
    }
}
