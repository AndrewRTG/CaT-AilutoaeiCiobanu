<?php
declare(strict_types=1);

class StatsController
{
    public static function handle(): void
    {
        require_admin();
        json_response(['stats' => StatsModel::dashboard()]);
    }
}
