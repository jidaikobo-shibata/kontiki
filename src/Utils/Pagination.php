<?php

namespace Jidaikobo\Kontiki\Utils;

/**
 * Pagination class to handle pagination logic for SQL queries.
 *
 * This class provides methods to calculate LIMIT and OFFSET for pagination, as well as helper
 * methods to determine the existence of next and previous pages.
 */
class Pagination
{
    /** @var int Current page number */
    protected int $currentPage;

    /** @var int Number of items per page */
    protected int $itemsPerPage;

    /** @var int Total number of items in the dataset */
    protected int $totalItems;

    /** @var int Total number of pages calculated based on total items and items per page */
    protected int $totalPages;

    /**
     * Constructor to initialize pagination settings.
     *
     * @param int $currentPage The current page number.
     * @param int $itemsPerPage The number of items to display per page.
     */
    public function __construct(int $currentPage = 1, int $itemsPerPage = 10)
    {
        $this->currentPage = max(1, $currentPage); // Ensure current page is at least 1
        $this->itemsPerPage = max(1, $itemsPerPage); // Ensure items per page is at least 1
    }

    /**
     * Set the total number of items and calculate total pages.
     *
     * @param int $totalItems The total number of items.
     */
    public function setTotalItems(int $totalItems): void
    {
        $this->totalItems = $totalItems;
        $this->totalPages = (int) ceil($totalItems / $this->itemsPerPage);
    }

    /**
     * Get the offset for SQL queries.
     *
     * @return int The calculated offset based on the current page and items per page.
     */
    public function getOffset(): int
    {
        return ($this->currentPage - 1) * $this->itemsPerPage;
    }

    /**
     * Get the limit for SQL queries.
     *
     * @return int The limit, or number of items per page.
     */
    public function getLimit(): int
    {
        return $this->itemsPerPage;
    }

    public function getTotalItems(): int
    {
        return $this->totalItems;
    }

    /**
     * Check if there is a next page available.
     *
     * @return bool True if there is a next page, otherwise false.
     */
    public function hasNextPage(): bool
    {
        return $this->currentPage < $this->totalPages;
    }

    /**
     * Check if there is a previous page available.
     *
     * @return bool True if there is a previous page, otherwise false.
     */
    public function hasPreviousPage(): bool
    {
        return $this->currentPage > 1;
    }

    /**
     * Generate pagination links for navigation.
     *
     * @return array An array of page links with each link containing the page number and its current status.
     */
    public function getPageLinks(): array
    {
        $links = [];
        for ($i = 1; $i <= $this->totalPages; $i++) {
            $links[] = [
                'page' => $i,
                'isCurrent' => $i === $this->currentPage,
            ];
        }
        return $links;
    }

    /**
     * Get the current page number.
     *
     * @return int The current page number.
     */
    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    /**
     * Render pagination links as HTML.
     *
     * @param string $baseUrl The base URL for pagination links.
     * @param bool $isAjax Whether to add an AJAX-specific class to links.
     * @return string The generated pagination HTML.
     */
    public function render(string $baseUrl, bool $isAjax = false): string
    {
        $pageLinks = $this->getPageLinks();
        $ajaxClass = $isAjax ? ' page-link-ajax' : '';

        if (count($pageLinks) === 1) {
            return '';
        }

        $html = '<nav aria-label="Page navigation">';
        $html .= '<ul class="pagination">';
        $html .= $this->renderPreviousLink($baseUrl, $ajaxClass);
        $html .= $this->renderPageLinks($baseUrl, $ajaxClass);
        $html .= $this->renderNextLink($baseUrl, $ajaxClass);
        $html .= '</ul>';
        $html .= '</nav>';

        return $html;
    }

    private function renderPreviousLink(string $baseUrl, string $ajaxClass): string
    {
        if ($this->hasPreviousPage()) {
            $previousPage = $this->currentPage - 1;
            $html = '';
            $html .= '<li class="page-item">';
            $html .= '<a class="page-link' . $ajaxClass . '" href="' . $baseUrl . '?paged=' . $previousPage . '" aria-label="Previous" data-page="' . $previousPage . '">';
            $html .= '<span aria-hidden="true">&laquo;</span>';
            $html .= '</a></li>';
            return $html;
        }
        return '<li class="page-item disabled"><span class="page-link">&laquo;</span></li>';
    }

    private function renderNextLink(string $baseUrl, string $ajaxClass): string
    {
        if ($this->hasNextPage()) {
            $html = '';
            $nextPage = $this->currentPage + 1;
            $nextPage = $this->currentPage + 1;
            $html .= '<li class="page-item">';
            $html .= '<a class="page-link' . $ajaxClass . '" href="' . $baseUrl . '?paged=' . $nextPage . '" aria-label="Next" data-page="' . $nextPage . '">';
            $html .= '<span aria-hidden="true">&raquo;</span>';
            $html .= '</a></li>';
            return $html;
        }
        return '<li class="page-item disabled"><span class="page-link">&raquo;</span></li>';
    }

    private function renderPageLinks(string $baseUrl, string $ajaxClass): string
    {
        $html = '';
        foreach ($this->getPageLinks() as $link) {
            $eachPage = $link['page'];
            $activeClass = $link['isCurrent'] ? ' active' : '';
            $ariaCurrent = $link['isCurrent'] ? ' aria-current="page"' : '';
            $html .= '<li class="page-item' . $activeClass . '">';
            $html .= '<a class="page-link' . $ajaxClass . '" href="' . $baseUrl . '?paged=' . $eachPage . '"' . $ariaCurrent . ' data-page="' . $eachPage . '">' . $eachPage . '</a>';
            $html .= '</li>';
        }
        return $html;
    }
}
