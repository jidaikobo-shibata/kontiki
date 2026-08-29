<?php

namespace Jidaikobo\Kontiki\Controllers\FileControllerTraits;

use Jidaikobo\Kontiki\Utils\Pagination;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

trait ListTrait
{
    /**
     * Handles the AJAX request to fetch the file list.
     *
     * This method retrieves a list of files from the model, applies security headers
     * to the response, and then renders a view to display the file list.
     *
     * @return Response
     */
    public function filelist(Request $request, Response $response): Response
    {
        // Initialize Pagination and set total items
        $page = $request->getQueryParams()['page'] ?? 1;
        $itemsPerPage = 10;
        $pagination = new Pagination($page, $itemsPerPage);

        $keyword = $request->getQueryParams()['s'] ?? '';
        $query = $this->model->getQuery();
        $query = $this->model->buildSearchConditions($query, $keyword);
        $totalItems = $query->count();

        $pagination->setTotalItems($totalItems);
        $paginationHtml = $pagination->render(env('BASEPATH', '') . "/filelist", true);

        $items = $query->limit($pagination->getLimit())
                  ->offset($pagination->getOffset())
                  ->orderBy('created_at', 'desc')
                  ->get()
                  ->map(fn($item) => (array) $item)
                  ->toArray();

        $items = $this->processItemsForList($request, $items);

        $content = $this->view->fetch(
            'forms/incFilelist.php',
            [
                'items' => $items,
                'pagination' => $paginationHtml
            ]
        );

        $response->getBody()->write($content);
        return $response->withHeader('Content-Type', 'text/html')->withStatus(200);
    }

    private function processItemsForList(Request $request, array $items): array
    {
        foreach ($items as $key => $value) {
            $url = $this->pathToUrl($items[$key]['path']);
            $items[$key]['imageOrLink'] = $this->renderImageOrLink($url, $items[$key]['description'] ?? '');
            $items[$key]['url'] = $url;
            $items[$key]['description'] = $items[$key]['description'] ?? ''; // don't use null
            $items[$key]['isImage'] = $this->isImageUrl($url);
        }
        return $items;
    }

    /**
     * Determine whether a given URL points to an image file abased on its extension.
     *
     * @param string $url The URL to check.
     * @return bool True if it's an image, false otherwise.
     */
    private function isImageUrl(string $url): bool
    {
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
        $pathInfo = pathinfo(parse_url($url, PHP_URL_PATH));

        return isset($pathInfo['extension']) &&
            in_array(strtolower($pathInfo['extension']), $imageExtensions, true);
    }

    /**
     * Render an image or a link based on the provided URL.
     *
     * @param string $url The input URL, either an image URL or a standard URL.
     * @param string|null $desc description text.
     * @return string The generated HTML.
     */
    private function renderImageOrLink(string $url, ?string $desc): string
    {
        if ($this->isImageUrl($url)) {
            $descText = e($desc);
            $imgSrc = e($url);
            $imgTag = '<a href="' . $imgSrc . '"'
                . ' class="img-thumbnail"'
                . ' data-action="preview"'
                . ' data-url="' . $imgSrc . '"'
                . ' data-alt="' . $descText . '">'
                . '<img src="' . $imgSrc . '"'
                . ' alt="' . __('enlarge_x', 'Enlarge :name', ['name' => $descText]) . '"'
                . ' class="img-thumbnail">'
                . '</a>';
            return $imgTag;
        }

        // Otherwise, return an <a> tag for links
        $linkHref = e($url);
        $pathInfo = pathinfo(parse_url($url, PHP_URL_PATH));
        $extension = isset($pathInfo['extension']) ? strtolower($pathInfo['extension']) : null;

        switch ($extension) {
            case 'pdf':
                $class = 'fa-file-pdf';
                break;
            case 'zip':
                $class = 'fa-file-zip';
                break;
            default:
                $class = 'fa-file-text';
                break;
        }

        return '<a href="' . $linkHref . '" target="_blank" aria-label="' . __('downlaod') . '" download class="fa-solid ' . $class . ' display-3 mb-2"><span class="visually-hidden">' . __('downlaod_x', 'Download :name', ['name' => $desc]) . '</span></a>';
    }
}
