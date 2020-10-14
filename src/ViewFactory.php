<?php

namespace App;

use App\Http\ServerRequest;
use Doctrine\Inflector\InflectorFactory;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;

use const JSON_PRETTY_PRINT;

class ViewFactory
{
    protected ContainerInterface $di;

    protected Settings $settings;

    protected EventDispatcher $dispatcher;

    protected Version $version;

    protected Assets $assets;

    public function __construct(
        ContainerInterface $di,
        Settings $settings,
        EventDispatcher $dispatcher,
        Version $version,
        Assets $assets
    ) {
        $this->di = $di;
        $this->settings = $settings;
        $this->dispatcher = $dispatcher;
        $this->version = $version;
        $this->assets = $assets;
    }

    public function create(ServerRequestInterface $request): View
    {
        $view = new View($this->settings[Settings::VIEWS_DIR], 'phtml');

        // Add non-request-dependent content.
        $view->addData([
            'settings' => $this->settings,
            'version' => $this->version,
        ]);

        // Add request-dependent content.
        $assets = $this->assets->withRequest($request);

        $view->addData([
            'request' => $request,
            'router' => $request->getAttribute(ServerRequest::ATTR_ROUTER),
            'auth' => $request->getAttribute(ServerRequest::ATTR_AUTH),
            'acl' => $request->getAttribute(ServerRequest::ATTR_ACL),
            'customization' => $request->getAttribute(ServerRequest::ATTR_CUSTOMIZATION),
            'flash' => $request->getAttribute(ServerRequest::ATTR_SESSION_FLASH),
            'assets' => $assets,
        ]);

        $view->registerFunction('service', function ($service) {
            return $this->di->get($service);
        });

        $view->registerFunction('escapeJs', function ($string) {
            return json_encode($string, JSON_THROW_ON_ERROR, 512);
        });

        $view->registerFunction('dump', function ($value) {
            if (class_exists(VarCloner::class)) {
                $varCloner = new VarCloner();

                $dumper = new CliDumper();
                $dumpedValue = $dumper->dump($varCloner->cloneVar($value), true);
            } else {
                $dumpedValue = json_encode($value, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
            }

            return '<pre>' . htmlspecialchars($dumpedValue) . '</pre>';
        });

        $view->registerFunction('mailto', function ($address, $link_text = null) {
            $address = substr(chunk_split(bin2hex(" $address"), 2, ';&#x'), 3, -3);
            $link_text = $link_text ?? $address;
            return '<a href="mailto:' . $address . '">' . $link_text . '</a>';
        });

        $view->registerFunction('pluralize', function ($word, $num = 0) {
            if ((int)$num === 1) {
                return $word;
            }

            $inflector = InflectorFactory::create()->build();
            return $inflector->pluralize($word);
        });

        $view->registerFunction('truncate', function ($text, $length = 80) {
            return Utilities::truncateText($text, $length);
        });

        $view->registerFunction('truncateUrl', function ($url) {
            return Utilities::truncateUrl($url);
        });

        $view->registerFunction('link', function ($url, $external = true, $truncate = true) {
            $url = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');

            $a = ['href="' . $url . '"'];
            if ($external) {
                $a[] = 'target="_blank"';
            }

            $a_body = ($truncate) ? Utilities::truncateUrl($url) : $url;
            return '<a ' . implode(' ', $a) . '>' . $a_body . '</a>';
        });

        $this->dispatcher->dispatch(new Event\BuildView($view));

        return $view;
    }
}
