<?php

declare(strict_types=1);

namespace App;

use App\Entity\User;
use App\Enums\SupportedLocales;
use App\Http\RouterInterface;
use App\Http\ServerRequest;
use App\Traits\RequestAwareTrait;
use App\Utilities\Json;
use App\View\GlobalSections;
use Doctrine\Common\Collections\ArrayCollection;
use League\Plates\Engine;
use League\Plates\Template\Data;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use stdClass;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;

final class View extends Engine
{
    use RequestAwareTrait;

    private GlobalSections $sections;

    /** @var ArrayCollection<string, array|object|string|int|bool> */
    private ArrayCollection $globalProps;

    public function __construct(
        Customization $customization,
        Environment $environment,
        EventDispatcherInterface $dispatcher,
        Version $version,
        RouterInterface $router
    ) {
        parent::__construct(
            $environment->getBackendDirectory() . '/templates',
            'phtml'
        );

        $this->sections = new GlobalSections();
        $this->globalProps = new ArrayCollection();

        // Add non-request-dependent content.
        $this->addData(
            [
                'sections' => $this->sections,
                'globalProps' => $this->globalProps,
                'customization' => $customization,
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

        $vueComponents = (!$environment->isDevelopment())
            ? Json::loadFromFile($environment->getBaseDirectory() . '/web/static/vite_dist/.vite/manifest.json')
            : [];

        $this->registerFunction(
            'getVueComponentInfo',
            function (string $componentPath) use ($vueComponents, $environment) {
                $assetRoot = '/static/vite_dist';

                if ($environment->isDevelopment() || $environment->isTesting()) {
                    return [
                        'js' => $assetRoot . '/' . $componentPath,
                        'css' => [],
                        'prefetch' => [],
                    ];
                }

                if (!isset($vueComponents[$componentPath])) {
                    return null;
                }

                $includes = [
                    'js' => $assetRoot . '/' . $vueComponents[$componentPath]['file'],
                    'css' => [],
                    'prefetch' => [],
                ];

                $visitedNodes = [];
                $fetchCss = function ($component) use (
                    $vueComponents,
                    $assetRoot,
                    &$includes,
                    &$fetchCss,
                    &$visitedNodes
                ): void {
                    if (!isset($vueComponents[$component]) || isset($visitedNodes[$component])) {
                        return;
                    }

                    $visitedNodes[$component] = true;

                    $componentInfo = $vueComponents[$component];
                    if (isset($componentInfo['css'])) {
                        foreach ($componentInfo['css'] as $css) {
                            $includes['css'][] = $assetRoot . '/' . $css;
                        }
                    }

                    if (isset($componentInfo['file'])) {
                        $fileUrl = $assetRoot . '/' . $componentInfo['file'];
                        if ($fileUrl !== $includes['js']) {
                            $includes['prefetch'][] = $fileUrl;
                        }
                    }

                    if (isset($componentInfo['imports'])) {
                        foreach ($componentInfo['imports'] as $import) {
                            $fetchCss($import);
                        }
                    }
                };

                $fetchCss($componentPath);

                return $includes;
            }
        );

        $dispatcher->dispatch(new Event\BuildView($this));
    }

    public function setRequest(?ServerRequest $request): void
    {
        $this->request = $request;

        if (null !== $request) {
            $requestData = [
                'request' => $request,
                'auth' => $request->getAttribute(ServerRequest::ATTR_AUTH),
                'acl' => $request->getAttribute(ServerRequest::ATTR_ACL),
                'flash' => $request->getAttribute(ServerRequest::ATTR_SESSION_FLASH),
            ];

            $router = $request->getAttribute(ServerRequest::ATTR_ROUTER);
            if (null !== $router) {
                $requestData['router'] = $router;
            }

            $customization = $request->getAttribute(ServerRequest::ATTR_CUSTOMIZATION);
            if ($customization instanceof Customization) {
                $requestData['customization'] = $customization;
            }

            $localeObj = $request->getAttribute(ServerRequest::ATTR_LOCALE);
            if (!($localeObj instanceof SupportedLocales)) {
                $localeObj = SupportedLocales::default();
            }

            $locale = $localeObj->getLocaleWithoutEncoding();
            $localeShort = substr($locale, 0, 2);
            $localeWithDashes = str_replace('_', '-', $locale);

            $this->globalProps->set('locale', $locale);
            $this->globalProps->set('localeShort', $localeShort);
            $this->globalProps->set('localeWithDashes', $localeWithDashes);

            // User profile-specific 24-hour display setting.
            $userObj = $request->getAttribute(ServerRequest::ATTR_USER);
            $requestData['user'] = $userObj;

            $timeConfig = new stdClass();

            if ($userObj instanceof User) {
                // See: https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Intl/DateTimeFormat/DateTimeFormat#hourcycle
                $timeConfig->hourCycle = $userObj->show_24_hour_time ? 'h23' : 'h12';

                $globalPermissions = [];
                $stationPermissions = [];

                foreach ($userObj->roles as $role) {
                    foreach ($role->permissions as $permission) {
                        $station = $permission->station;
                        if (null !== $station) {
                            $stationPermissions[$station->id][] = $permission->action_name;
                        } else {
                            $globalPermissions[] = $permission->action_name;
                        }
                    }
                }

                $this->globalProps->set('user', [
                    'id' => $userObj->id,
                    'displayName' => $userObj->getDisplayName(),
                    'globalPermissions' => $globalPermissions,
                    'stationPermissions' => $stationPermissions,
                ]);
            }

            $this->globalProps->set('timeConfig', $timeConfig);

            $this->addData($requestData);
        }
    }

    public function getSections(): GlobalSections
    {
        return $this->sections;
    }

    /** @return ArrayCollection<string, array|object|string|int|bool> */
    public function getGlobalProps(): ArrayCollection
    {
        return $this->globalProps;
    }

    public function reset(): void
    {
        $this->sections = new GlobalSections();
        $this->globalProps = new ArrayCollection();
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
        string $layout = 'panel',
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
