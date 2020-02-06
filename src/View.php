<?php
namespace App;

use League\Plates\Engine;
use League\Plates\Template\Data;
use Psr\Http\Message\ResponseInterface;

class View extends Engine
{
    public function reset(): void
    {
        $this->data = new Data();
    }

    /**
     * @param string $name
     * @param array $data
     *
     * @return string
     */
    public function fetch(string $name, array $data = []): string
    {
        return $this->render($name, $data);
    }

    /**
     * Trigger rendering of template and write it directly to the PSR-7 compatible Response object.
     *
     * @param ResponseInterface $response
     * @param string $template_name
     * @param array $template_args
     *
     * @return ResponseInterface
     */
    public function renderToResponse(
        ResponseInterface $response,
        $template_name,
        array $template_args = []
    ): ResponseInterface {
        $template = $this->render($template_name, $template_args);

        $response->getBody()->write($template);
        return $response->withHeader('Content-type', 'text/html; charset=utf-8');
    }
}
