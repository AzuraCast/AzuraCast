<?php
namespace App\View\Helper;
class Paginate extends HelperAbstract
{
    /**
     * @param $pager \App\Paginator\Doctrine|\Zend\Paginator\Paginator
     * @return string
     */
    public function paginate($pager, $show_if_zero_pages = false)
    {
        $pages = (array)$pager->getPages();

        $query_string = '';
        if (!empty($_GET))
            $query_string = '?'.http_build_query($_GET);
        
        $return_string = '';

        $url = $this->di['url'];
        
        if ($pages['pageCount'] > 1 || $show_if_zero_pages)
        {
            $return_string .= '<nav><ul class="pagination">';
            
            // First page link
            if ($pages['first'] != $pages['current'])
                $return_string .= '<li class="prev"><a href="'.$url->routeFromHere(array('page' => $pages['first'])).$query_string.'" rel="'.$pages['first'].'">&laquo;</a></li>';
            else
                $return_string .= '<li class="prev disabled"><a href="#">&laquo;</a></li>';
            
            // Previous page link
            if ($pages['previous'])
                $return_string .= '<li><a href="'.$url->routeFromHere(array('page' => $pages['previous'])).$query_string.'" rel="'.$pages['previous'].'">&lt;</a></li>';
            else
                $return_string .= '<li class="disabled"><a href="#">&lt;</a></li>';
            
            // Produce full page range
            foreach($pages['pagesInRange'] as $page)
            {
                if ($page != $pages['current'])
                    $return_string .= '<li><a href="'.$url->routeFromHere(array('page' => $page)).$query_string.'" rel="'.$page.'">'.$page.'</a></li>';
                else
                    $return_string .= '<li class="active"><a href="#">'.$page.'</a></li>';
            }
            
            // Next page link
            if ($pages['next'])
                $return_string .= '<li><a href="'.$url->routeFromHere(array('page' => $pages['next'])).$query_string.'" rel="'.$pages['next'].'">&gt;</a></li>';
            else
                $return_string .= '<li class="disabled"><a href="#">&gt;</a></li>';
            
            // Last page link
            if ($pages['last'] != $pages['current'])
                $return_string .= '<li class="next"><a href="'.$url->routeFromHere(array('page' => $pages['last'])).$query_string.'" rel="'.$pages['last'].'">&raquo;</a></li>';
            else
                $return_string .= '<li class="next disabled"><a href="#">&raquo;</a></li>';
            
            $return_string .= '</ul></nav>';
        }
        
        return $return_string;
    }
}