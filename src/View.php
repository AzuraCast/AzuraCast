<?php
namespace App;

use App\Http\Response;
use Interop\Container\ContainerInterface;
use League\Plates\Template\Data;

class View extends \League\Plates\Engine
{
    public function reset()
    {
        $this->data = new Data();
    }

    public function __set($key, $value)
    {
        $this->addData([$key => $value]);
    }

    public function __isset($key)
    {
        return ($this->getData($key) === null);
    }

    public function __get($key)
    {
        return $this->getData($key);
    }

    public function fetch($name, array $data = [])
    {
        return parent::render($name, $data);
    }

    public function setFolder($name, $directory, $fallback = false)
    {
        if ($this->folders->exists($name)) {
            $this->folders->remove($name);
        }

        $this->folders->add($name, $directory, $fallback);

        return $this;
    }

    /**
     * Trigger rendering of template and write it directly to the PSR-7 compatible Response object.
     *
     * @param Response $response
     * @param null $template_name
     * @param array $template_args
     * @return Response
     */
    public function renderToResponse(Response $response, $template_name, array $template_args = []): Response
    {
        $template = $this->render($template_name, $template_args);

        return $response
            ->withHeader('Content-type', 'text/html; charset=utf-8')
            ->write($template);
    }
}
