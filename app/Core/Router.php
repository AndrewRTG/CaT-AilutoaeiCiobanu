<?php
declare(strict_types=1);

class Router
{
    private array $pages;
    private string $viewsPath;

    public function __construct(array $pages, string $viewsPath)
    {
        $this->pages = $pages;
        $this->viewsPath = rtrim($viewsPath, '/\\');
    }

    public function dispatch(array $query): void
    {
        $page = $this->pageFromQuery($query);
        $campingId = isset($query['id']) ? (int) $query['id'] : null;
        $user = null;
        $oauthError = (string) ($query['error'] ?? '');

        require $this->viewsPath . '/templates/header.php';
        require $this->viewsPath . '/pages/' . $page . '.php';
        require $this->viewsPath . '/templates/footer.php';
    }

    private function pageFromQuery(array $query): string
    {
        $page = (string) ($query['page'] ?? 'home');

        if (!in_array($page, $this->pages, true)) {
            return 'home';
        }

        return $page;
    }
}
