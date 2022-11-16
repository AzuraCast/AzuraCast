<?php

declare(strict_types=1);

namespace App;

use App\Http\RouterInterface;
use App\Http\ServerRequest;
use App\Traits\RequestAwareTrait;
use App\Utilities\Json;
use App\View\GlobalSections;
use League\Plates\Engine;
use League\Plates\Template\Data;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;

final class View extends Engine
{
    use RequestAwareTrait;

    private readonly GlobalSections $sections;

    public function __construct(
        Environment $environment,
        EventDispatcherInterface $dispatcher,
        Version $version,
        RouterInterface $router
    ) {
        parent::__construct($environment->getViewsDirectory(), 'phtml');

        $this->sections = new GlobalSections();

        // Add non-request-dependent content.
        $this->addData(
            [
                'sections' => $this->sections,
                'environment' => $environment,
                'version' => $version,
                'router' => $router,
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

        $vueComponents = Json::loadFromFile($environment->getBaseDirectory() . '/web/static/webpack.json');
        $this->registerFunction(
            'getVueComponentInfo',
            fn(string $component) => $vueComponents['entrypoints'][$component] ?? null
        );

        $versionedFiles = Json::loadFromFile($environment->getBaseDirectory() . '/web/static/assets.json');
        $this->registerFunction(
            'assetUrl',
            function (string $url) use ($environment, $versionedFiles): string {
                if (isset($versionedFiles[$url])) {
                    $url = $versionedFiles[$url];
                }

                if (str_starts_with($url, 'http')) {
                    return $url;
                }

                if (str_starts_with($url, '/')) {
                    return $url;
                }

                return $environment->getAssetUrl() . '/' . $url;
            }
        );

        $dispatcher->dispatch(new Event\BuildView($this));
    }

    public function setRequest(?ServerRequestInterface $request): void
    {
        $this->request = $request;

        if (null !== $request) {
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
        }
    }

    public function getSections(): GlobalSections
    {
        return $this->sections;
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
        $response->getBody()->write(
            $this->render($templateName, $templateArgs)
        );
        return $response->withHeader('Content-type', 'text/html; charset=utf-8');
    }

    public function renderVuePage(
        ResponseInterface $response,
        string $component,
        ?string $id = null,
        string $layout = 'main',
        ?string $title = null,
        array $layoutParams = [],
        array $props = [],
    ): ResponseInterface {
        $id ??= $component;

        return $this->renderToResponse(
            $response,
            'system/vue_page',
            [
                'component' => $component,
                'id' => $id,
                'layout' => $layout,
                'title' => $title,
                'layoutParams' => $layoutParams,
                'props' => $props,
            ]
        );
    }
}
