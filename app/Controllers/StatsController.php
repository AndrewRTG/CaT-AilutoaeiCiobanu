<?php
declare(strict_types=1);

class StatsController
{
    public static function handle(): void
    {
        require_permission('view_stats');
        json_response(['stats' => StatsModel::dashboard()]);
    }
}
