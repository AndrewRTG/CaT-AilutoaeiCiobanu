<?php
declare(strict_types=1);

class MessageController
{
    public static function handle(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            json_response(['messages' => MessageModel::latest()]);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $user = require_login();
            $upload = save_upload('media');
            $campingId = !empty($_POST['camping_id']) ? (int) $_POST['camping_id'] : null;

            MessageModel::create($user['id'], $campingId, $_POST['content'] ?? '', $upload['type'], $upload['path']);
            json_response(['ok' => true], 201);
        }

        json_response(['error' => 'Metoda nepermisa.'], 405);
    }
}
