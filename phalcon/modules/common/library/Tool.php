<?php

namespace Baseapp\Library;

/**
 * Tool Library
 *
 * @package     base-app
 * @category    Library
 * @version     2.0
 */
class Tool
{

    /**
     * Minify css and js collection
     *
     * @package     base-app
     * @version     2.0
     *
     * @return void
     */
    public static function assetsMinification()
    {
        $config = \Phalcon\DI::getDefault()->getShared('config');

        foreach (array('Css', 'Js') as $asset) {
            $get = 'get' . $asset;
            $filter = '\Phalcon\Assets\Filters\\' . $asset . 'min';

            foreach (\Phalcon\DI::getDefault()->getShared('assets')->$get() as $resource) {
                $min = new $filter();
                $resource->setSourcePath(ROOT_PATH . '/public/' . $resource->getPath());
                $resource->setTargetUri('min/' . $resource->getPath());

                if ($config->app->env != 'production') {
                    if (!is_dir(dirname(ROOT_PATH . '/public/min/' . $resource->getPath()))) {
                        $old = umask(0);
                        mkdir(dirname(ROOT_PATH . '/public/min/' . $resource->getPath()), 0777, true);
                        umask($old);
                    }

                    if ($config->app->env == 'development' || !file_exists(ROOT_PATH . '/public/min/' . $resource->getPath())) {
                        file_put_contents(ROOT_PATH . '/public/min/' . $resource->getPath(), $min->filter($resource->getContent()));
                    } elseif (md5($min->filter($resource->getContent())) != md5_file(ROOT_PATH . '/public/min/' . $resource->getPath())) {
                        file_put_contents(ROOT_PATH . '/public/min/' . $resource->getPath(), $min->filter($resource->getContent()));
                    }
                }
            }
        }
    }

    /**
     * Replace CamelCase and under_scores to spaces
     *
     * @package     base-app
     * @version     2.0
     *
     * @param string $str string to replace to human readable
     * @param char $space default spacer
     *
     * @return string
     */
    public static function label($str, $space = ' ')
    {
        $str = preg_replace('/(?<=\\w)(?=[A-Z])/', $space . "$1", $str);
        return $space === ' ' ? ucfirst(trim(str_replace('_', ' ', strtolower($str)))) : $str;
    }

    /**
     * Prepare HTML pagination.
     * First Previous 1 2 3 ... 22 23 24 25 26 [27] 28 29 30 31 32 ... 48 49 50 Next Last
     *
     * @package     base-app
     * @version     2.0
     *
     * @param object $pagination Phalcon Paginator object
     * @param mixed $url URL with pagination
     * @param string $class CSS class to adding to div
     * @param int $countOut Number of page links in the begin and end of whole range
     * @param int $countIn Number of page links on each side of current page
     *
     * @return  string
     */
    public static function pagination($pagination, $url = null, $class = 'pagination', $countOut = 0, $countIn = 2)
    {
        if ($pagination->total_pages < 2) {
            return;
        }
        // Beginning group of pages: $n1...$n2
        $n1 = 1;
        $n2 = min($countOut, $pagination->total_pages);

        // Ending group of pages: $n7...$n8
        $n7 = max(1, $pagination->total_pages - $countOut + 1);
        $n8 = $pagination->total_pages;

        // Middle group of pages: $n4...$n5
        $n4 = max($n2 + 1, $pagination->current - $countIn);
        $n5 = min($n7 - 1, $pagination->current + $countIn);
        $useMiddle = ($n5 >= $n4);

        // Point $n3 between $n2 and $n4
        $n3 = (int) (($n2 + $n4) / 2);
        $useN3 = ($useMiddle && (($n4 - $n2) > 1));

        // Point $n6 between $n5 and $n7
        $n6 = (int) (($n5 + $n7) / 2);
        $useN6 = ($useMiddle && (($n7 - $n5) > 1));

        // Links to display as array(page => content)
        $links = array();

        // Generate links data in accordance with calculated numbers
        for ($i = $n1; $i <= $n2; $i++) {
            $links[$i] = $i;
        }

        if ($useN3) {
            $links[$n3] = '&hellip;';
        }

        for ($i = $n4; $i <= $n5; $i++) {
            $links[$i] = $i;
        }

        if ($useN6) {
            $links[$n6] = '&hellip;';
        }

        for ($i = $n7; $i <= $n8; $i++) {
            $links[$i] = $i;
        }

        // Detect URL
        $tag = \Phalcon\DI::getDefault()->getShared('tag');
        $query = \Phalcon\DI::getDefault()->getShared('request')->getQuery();
        $url = $url ? $url : substr($query['_url'], 1);
        unset($query['_url']);

        // Prepare list
        $html = '<ul class="' . $class . '">';

        // Prepare First button
        if ($pagination->current != $pagination->first) {
            unset($query['page']);
            $html .= '<li>' . $tag->linkTo(array($url, 'query' => $query, 'rel' => 'first', __('First'))) . '</li>';
        } else {
            $html .= '<li class="disabled"><span>' . __('First') . '</span></li>';
        }

        // Prepare Previous button
        if ($pagination->current > $pagination->before) {
            $query['page'] = $pagination->before;
            $html .= '<li>' . $tag->linkTo(array($url, 'query' => $query, 'rel' => 'prev', 'title' => __('Previous'), '«')) . '</li>';
        } else {
            $html .= '<li class="disabled"><span>«</span></li>';
        }

        // Prepare Pages
        $paginations = array();
        foreach ($links as $number => $content) {
            if ($number === $pagination->current) {
                $paginations[] = '<li class="active"><span>' . $content . '</span></li>';
            } else {
                $query['page'] = $number;
                $paginations[] = '<li' . ($content == '&hellip;' ? ' class="disabled"' : '') . '>' . $tag->linkTo(array($url, 'query' => $query, $content)) . '</li>';
            }
        }

        $html .= implode('', $paginations);

        // Prepare Next button
        if ($pagination->current < $pagination->next) {
            $query['page'] = $pagination->next;
            $html .= '<li>' . $tag->linkTo(array($url, 'query' => $query, 'rel' => 'next', 'title' => __('Next'), '»')) . '</li>';
        } else {
            $html .= '<li class="disabled"><span>»</span></li>';
        }

        // Prepare Last button
        if ($pagination->current != $pagination->last) {
            $query['page'] = $pagination->last;
            $html .= '<li>' . $tag->linkTo(array($url, 'query' => $query, 'rel' => 'last', __('Last'))) . '</li>';
        } else {
            $html .= '<li class="disabled"><span>' . __('Last') . '</span></li>';
        }

        // Close list
        $html .= '</ul>';

        return $html;
    }

    /**
     * Register the Volt engines
     *
     * @package     base-app
     * @version     2.0
     *
     * @param object $view Phalcon\Mvc\View
     * @param object $di dependency Injection
     *
     * @return array array of template engines
     */
    public static function registerEngines($view, $di)
    {
        $config = \Phalcon\DI::getDefault()->getShared('config');

        $volt = new \Phalcon\Mvc\View\Engine\Volt($view, $di);
        $volt->setOptions(array(
            // Don't check on 'production' for differences between the template file and its compiled path
            // Compile always on 'development', on 'testing'/'staging' only checks for changes in the children templates
            'stat' => $config->app->env == 'production' ? false : true,
            'compileAlways' => $config->app->env == 'development' ? true : false,
            'compiledPath' => function($templatePath) {
        list($junk, $path) = explode(ROOT_PATH, $templatePath);
        $dir = dirname($path);
        $file = basename($path, '.volt');

        if (!is_dir(ROOT_PATH . '/app/common/cache/volt' . $dir)) {
            $old = umask(0);
            mkdir(ROOT_PATH . '/app/common/cache/volt' . $dir, 0777, true);
            umask($old);
        }
        return ROOT_PATH . '/app/common/cache/volt' . $dir . '/' . $file . '.phtml';
    }
        ));

        $compiler = $volt->getCompiler();
        $compiler->addExtension(new \Baseapp\Extension\VoltStaticFunctions());
        $compiler->addExtension(new \Baseapp\Extension\VoltPHPFunctions());

        return array(
            // Try to load .phtml file from ViewsDir first,
            ".phtml" => "Phalcon\Mvc\View\Engine\Php",
            ".volt" => $volt,
            ".md" => 'Baseapp\Extension\Markdown',
        );
    }

}
