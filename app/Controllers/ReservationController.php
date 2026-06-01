<?php
declare(strict_types=1);

class ReservationController
{
    public static function handle(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];

        if ($method === 'GET') {
            json_response(['reservations' => ReservationModel::forUser(require_login())]);
        }

        if ($method === 'POST') {
            $user = require_login();
            $data = body_json();

            if (($data['start_date'] ?? '') >= ($data['end_date'] ?? '')) {
                json_response(['error' => 'Data de final trebuie sa fie dupa data de inceput.'], 422);
            }

            ReservationModel::create($user['id'], $data);
            json_response(['ok' => true], 201);
        }

        if ($method === 'PATCH') {
            require_admin();
            $data = body_json();
            $status = clean($data['status'] ?? '', 20);

            if (!in_array($status, ['pending', 'confirmed', 'cancelled'], true)) {
                json_response(['error' => 'Status invalid.'], 422);
            }

            ReservationModel::updateStatus((int) ($_GET['id'] ?? 0), $status);
            json_response(['ok' => true]);
        }

        json_response(['error' => 'Metoda nepermisa.'], 405);
    }
}
