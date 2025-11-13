<?php
namespace App\Includes;

/**
 * Simple Paginator utility
 * Usage:
 * $p = new Paginator((int)($_GET['page'] ?? 1), 20);
 * $offset = $p->getOffset();
 * $limit = $p->getLimit();
 * $rows = $db->fetchAll("SELECT ... LIMIT :limit OFFSET :offset", ['limit'=>$limit, 'offset'=>$offset]);
 * $total = $db->fetchOne("SELECT COUNT(*) as c FROM ...")["c"];
 * $p->setTotal($total);
 * $meta = $p->getMeta();
 */

class Paginator {
    protected int $page;
    protected int $perPage;
    protected int $total = 0;
    protected int $maxPagesToShow = 7;

    public function __construct(int $page = 1, int $perPage = 20)
    {
        $this->page = $page > 0 ? $page : 1;
        $this->perPage = $perPage > 0 ? $perPage : 20;
    }

    public function getLimit(): int
    {
        return $this->perPage;
    }

    public function getOffset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }

    public function setTotal(int $total): void
    {
        $this->total = max(0, $total);
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }

    public function getTotalPages(): int
    {
        if ($this->perPage <= 0) return 0;
        return (int) ceil($this->total / $this->perPage);
    }

    public function getMeta(): array
    {
        return [
            'total' => $this->getTotal(),
            'per_page' => $this->getPerPage(),
            'page' => $this->getPage(),
            'total_pages' => $this->getTotalPages(),
            'offset' => $this->getOffset(),
            'limit' => $this->getLimit()
        ];
    }

    /**
     * Render a simple HTML pager (ul > li > a). Caller should ensure URL is safe.
     * $baseUrl should include existing query string without the page param, e.g. '/admin/logs.php?filter=1'
     */
    public function renderLinks(string $baseUrl = '', string $pageParam = 'page'): string
    {
        $totalPages = $this->getTotalPages();
        if ($totalPages <= 1) return '';

        $current = $this->getPage();
        $html = '<nav class="pagination" aria-label="pagination"><ul class="flex gap-2">';

        // prev
        if ($current > 1) {
            $html .= '<li><a href="' . $this->buildUrl($baseUrl, $pageParam, $current - 1) . '" class="pagination-prev">&laquo; Prev</a></li>';
        }

        $start = max(1, $current - (int)floor($this->maxPagesToShow / 2));
        $end = min($totalPages, $start + $this->maxPagesToShow - 1);

        if ($end - $start + 1 < $this->maxPagesToShow) {
            $start = max(1, $end - $this->maxPagesToShow + 1);
        }

        for ($i = $start; $i <= $end; $i++) {
            if ($i === $current) {
                $html .= '<li aria-current="page"><span class="px-3 py-1 rounded bg-blue-600 text-white">' . $i . '</span></li>';
            } else {
                $html .= '<li><a href="' . $this->buildUrl($baseUrl, $pageParam, $i) . '" class="px-3 py-1 rounded border">' . $i . '</a></li>';
            }
        }

        // next
        if ($current < $totalPages) {
            $html .= '<li><a href="' . $this->buildUrl($baseUrl, $pageParam, $current + 1) . '" class="pagination-next">Next &raquo;</a></li>';
        }

        $html .= '</ul></nav>';
        return $html;
    }

    protected function buildUrl(string $baseUrl, string $param, int $value): string
    {
        // If baseUrl already contains ?, append with & otherwise with ?
        $sep = strpos($baseUrl, '?') === false ? '?' : '&';
        return htmlspecialchars($baseUrl . $sep . $param . '=' . $value, ENT_QUOTES, 'UTF-8');
    }
}
