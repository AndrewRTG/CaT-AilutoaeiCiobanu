<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/core.php';

check_csrf();

set_exception_handler(function (Throwable $error): void {
    json_response(['error' => 'Eroare server: ' . $error->getMessage()], 500);
});
