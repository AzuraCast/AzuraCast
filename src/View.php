<?php

declare(strict_types=1);

namespace App;

use App\Http\RouterInterface;
use App\Http\ServerRequest;
use App\Traits\RequestAwareTrait;
use Doctrine\Inflector\InflectorFactory;
use League\Plates\Engine;
use League\Plates\Template\Data;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;

class View extends Engine
{
    use RequestAwareTrait;

    public function __construct(
        Environment $environment,
        EventDispatcherInterface $dispatcher,
        Version $version,
        RouterInterface $router,
        protected Assets $assets
    ) {
        parent::__construct($environment->getViewsDirectory(), 'phtml');

        // Add non-request-dependent content.
        $this->addData(
            [
                'environment' => $environment,
                'version' => $version,
                'router' => $router,
                'assets' => $this->assets,
            ]
        );

        $this->registerFunction(
            'escapeJs',
            function ($string) {
                return json_encode($string, JSON_THROW_ON_ERROR);
            }
        );

        $this->registerFunction(
            'dump',
            function ($value) {
                if (class_exists(VarCloner::class)) {
                    $varCloner = new VarCloner();

                    $dumpedValue = (new CliDumper())->dump($varCloner->cloneVar($value), true);
                } else {
                    $dumpedValue = json_encode($value, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
                }

                return '<pre>' . htmlspecialchars($dumpedValue ?? '', ENT_QUOTES | ENT_HTML5) . '</pre>';
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

                return InflectorFactory::create()->build()->pluralize($word);
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
                $url = htmlspecialchars($url, ENT_QUOTES);

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

    public function setRequest(?ServerRequestInterface $request): void
    {
        $this->assets = $this->assets->withRequest($request);
        $this->request = $request;

        if (null !== $request) {
            $this->addData(
                [
                    'assets' => $this->assets,
                    'request' => $request,
                    'router' => $request->getAttribute(ServerRequest::ATTR_ROUTER),
                    'auth' => $request->getAttribute(ServerRequest::ATTR_AUTH),
                    'acl' => $request->getAttribute(ServerRequest::ATTR_ACL),
                    'customization' => $request->getAttribute(ServerRequest::ATTR_CUSTOMIZATION),
                    'flash' => $request->getAttribute(ServerRequest::ATTR_SESSION_FLASH),
                    'user' => $request->getAttribute(ServerRequest::ATTR_USER),
                ]
            );
        }
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
