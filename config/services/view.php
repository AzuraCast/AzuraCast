<?php
return [

    // View (Plates Templates)
    App\View::class => function (
        Psr\Container\ContainerInterface $di,
        App\Settings $settings,
        App\Http\RouterInterface $router,
        App\EventDispatcher $dispatcher
    ) {
        $view = new App\View($settings[App\Settings::VIEWS_DIR], 'phtml');

        $view->registerFunction('service', function ($service) use ($di) {
            return $di->get($service);
        });

        $view->registerFunction('escapeJs', function ($string) {
            return json_encode($string, JSON_THROW_ON_ERROR, 512);
        });

        $view->addData([
            'settings' => $settings,
            'router' => $router,
            'assets' => $di->get(App\Assets::class),
            'auth' => $di->get(App\Auth::class),
            'acl' => $di->get(App\Acl::class),
            'customization' => $di->get(App\Customization::class),
            'version' => $di->get(App\Version::class),
        ]);

        $view->registerFunction('mailto', function ($address, $link_text = null) {
            $address = substr(chunk_split(bin2hex(" $address"), 2, ';&#x'), 3, -3);
            $link_text = $link_text ?? $address;
            return '<a href="mailto:' . $address . '">' . $link_text . '</a>';
        });
        $view->registerFunction('pluralize', function ($word, $num = 0) {
            if ((int)$num === 1) {
                return $word;
            }
            return Doctrine\Common\Inflector\Inflector::pluralize($word);
        });
        $view->registerFunction('truncate', function ($text, $length = 80) {
            return App\Utilities::truncateText($text, $length);
        });
        $view->registerFunction('truncateUrl', function ($url) {
            return App\Utilities::truncateUrl($url);
        });
        $view->registerFunction('link', function ($url, $external = true, $truncate = true) {
            $url = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');

            $a = ['href="' . $url . '"'];
            if ($external) {
                $a[] = 'target="_blank"';
            }

            $a_body = ($truncate) ? App\Utilities::truncateUrl($url) : $url;
            return '<a ' . implode(' ', $a) . '>' . $a_body . '</a>';
        });

        $dispatcher->dispatch(new App\Event\BuildView($view));

        return $view;
    },

];