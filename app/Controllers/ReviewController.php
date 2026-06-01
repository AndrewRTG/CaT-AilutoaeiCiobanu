<?php
declare(strict_types=1);

class ReviewController
{
    public static function handle(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            json_response(['reviews' => ReviewModel::forCamping((int) ($_GET['camping_id'] ?? $_GET['id'] ?? 0))]);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $user = require_login();
            $upload = save_upload('media');
            $campingId = (int) $_POST['camping_id'];

            ReviewModel::create($user['id'], $campingId, (int) $_POST['rating'], $_POST['comment'] ?? '', $upload['type'], $upload['path']);
            CampingModel::updateRating($campingId);

            json_response(['ok' => true], 201);
        }

        json_response(['error' => 'Metoda nepermisa.'], 405);
    }
}
