<?php

namespace App;

use App\Http\Router;
use App\Http\ServerRequest;
use DI\FactoryInterface;
use Doctrine\Inflector\InflectorFactory;
use League\Plates\Engine;
use League\Plates\Template\Data;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;

class View extends Engine
{
    protected Assets $assets;

    protected ?ServerRequestInterface $request = null;

    public function __construct(
        FactoryInterface $factory,
        Environment $environment,
        EventDispatcher $dispatcher,
        Version $version,
        ?ServerRequestInterface $request = null
    ) {
        parent::__construct($environment->getViewsDirectory(), 'phtml');

        // Add non-request-dependent content.
        $this->assets = $factory->make(
            Assets::class,
            [
                'request' => $request,
            ]
        );

        $this->addData(
            [
                'environment' => $environment,
                'version' => $version,
                'assets' => $this->assets,
            ]
        );

        // Add request-dependent content.
        $this->request = $request;

        if ($request instanceof ServerRequestInterface) {
            $this->addData(
                [
                    'request' => $request,
                    'router' => $request->getAttribute(ServerRequest::ATTR_ROUTER),
                    'auth' => $request->getAttribute(ServerRequest::ATTR_AUTH),
                    'acl' => $request->getAttribute(ServerRequest::ATTR_ACL),
                    'customization' => $request->getAttribute(ServerRequest::ATTR_CUSTOMIZATION),
                    'flash' => $request->getAttribute(ServerRequest::ATTR_SESSION_FLASH),
                    'user' => $request->getAttribute(ServerRequest::ATTR_USER),
                ]
            );
        } else {
            $this->addData(
                [
                    'router' => $factory->make(Router::class),
                ]
            );
        }

        $this->registerFunction(
            'escapeJs',
            function ($string) {
                return json_encode($string, JSON_THROW_ON_ERROR, 512);
            }
        );

        $this->registerFunction(
            'dump',
            function ($value) {
                if (class_exists(VarCloner::class)) {
                    $varCloner = new VarCloner();

                    $dumper = new CliDumper();
                    $dumpedValue = $dumper->dump($varCloner->cloneVar($value), true);
                } else {
                    $dumpedValue = json_encode($value, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
                }

                return '<pre>' . htmlspecialchars($dumpedValue) . '</pre>';
            }
        );

        $this->registerFunction(
            'mailto',
            function ($address, $link_text = null) {
                $address = substr(chunk_split(bin2hex(" $address"), 2, ';&#x'), 3, -3);
                $link_text = $link_text ?? $address;
                return '<a href="mailto:' . $address . '">' . $link_text . '</a>';
            }
        );

        $this->registerFunction(
            'pluralize',
            function ($word, $num = 0) {
                if ((int)$num === 1) {
                    return $word;
                }

                $inflector = InflectorFactory::create()->build();
                return $inflector->pluralize($word);
            }
        );

        $this->registerFunction(
            'truncate',
            function ($text, $length = 80) {
                return Utilities\Strings::truncateText($text, $length);
            }
        );

        $this->registerFunction(
            'truncateUrl',
            function ($url) {
                return Utilities\Strings::truncateUrl($url);
            }
        );

        $this->registerFunction(
            'link',
            function ($url, $external = true, $truncate = true) {
                $url = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');

                $a = ['href="' . $url . '"'];
                if ($external) {
                    $a[] = 'target="_blank"';
                }

                $a_body = ($truncate) ? Utilities\Strings::truncateUrl($url) : $url;
                return '<a ' . implode(' ', $a) . '>' . $a_body . '</a>';
            }
        );

        $dispatcher->dispatch(new Event\BuildView($this));
    }

    public function reset(): void
    {
        $this->data = new Data();
    }

    /**
     * @param string $name
     * @param array $data
     */
    public function fetch(string $name, array $data = []): string
    {
        return $this->render($name, $data);
    }

    /**
     * Trigger rendering of template and write it directly to the PSR-7 compatible Response object.
     *
     * @param ResponseInterface $response
     * @param string $templateName
     * @param array $templateArgs
     */
    public function renderToResponse(
        ResponseInterface $response,
        string $templateName,
        array $templateArgs = []
    ): ResponseInterface {
        $template = $this->render($templateName, $templateArgs);

        $response->getBody()->write($template);
        $response = $response->withHeader('Content-type', 'text/html; charset=utf-8');

        return $this->assets->writeCsp($response);
    }
}
