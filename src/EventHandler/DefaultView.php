<?php
namespace App\EventHandler;

use App\Event\BuildView;
use Slim\Container;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DefaultView implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            BuildView::NAME => [
                ['addViewFunctions', 1],
                ['addViewData', 0],
            ],
        ];
    }

    /** @var Container */
    protected $di;

    public function __construct(Container $di)
    {
        $this->di = $di;
    }

    public function addViewFunctions(BuildView $event)
    {
        $view = $event->getView();

        $view->registerFunction('service', function($service) {
            return $this->di->get($service);
        });

        $view->registerFunction('escapeJs', function($string) {
            return json_encode($string);
        });

        $view->registerFunction('mailto', function ($address, $link_text = null) {
            $address = substr(chunk_split(bin2hex(" $address"), 2, ";&#x"), 3, -3);
            $link_text = $link_text ?? $address;

            return '<a href="mailto:' . $address . '">' . $link_text . '</a>';
        });

        $view->registerFunction('pluralize', function ($word, $num = 0) {
            if ((int)$num === 1) {
                return $word;
            } else {
                return \Doctrine\Common\Inflector\Inflector::pluralize($word);
            }
        });

        $view->registerFunction('truncate', function ($text, $length = 80) {
            return \App\Utilities::truncate_text($text, $length);
        });
    }

    public function addViewData(BuildView $event)
    {
        /** @var \App\Session $session */
        $session = $this->di[\App\Session::class];

        $event->getView()->addData([
            'app_settings' => $this->di['app_settings'],
            'router' => $this->di['router'],
            'request' => $this->di['request'],
            'assets' => $this->di[\App\Assets::class],
            'auth' => $this->di[\App\Auth::class],
            'acl' => $this->di[\App\Acl::class],
            'flash' => $session->getFlash(),
            'customization' => $this->di[\App\Customization::class],
        ]);
    }
}
