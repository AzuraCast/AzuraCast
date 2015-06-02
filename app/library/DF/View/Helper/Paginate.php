<?php
namespace DF\View\Helper;
class Paginate extends HelperAbstract
{
    /**
     * @param $pager \DF\Paginator\Doctrine|\Zend\Paginator\Paginator
     * @return string
     */
    public function paginate($pager)
    {
        $pages = (array)$pager->getPages();

        $query_string = '';
        if (!empty($_GET))
            $query_string = '?'.http_build_query($_GET);
        
        $return_string = '';
        
        if ($pages['pageCount'] > 1)
        {
            $return_string .= '<div class="pagination"><ul>';
            
            // First page link
            if ($pages['first'] != $pages['current'])
                $return_string .= '<li class="prev"><a href="'.$this->viewHelper->routeFromHere(array('page' => $pages['first'])).$query_string.'">&laquo; First</a></li>';
            else
                $return_string .= '<li class="prev disabled"><a href="#">&laquo; First</a></li>';
            
            // Previous page link
            if ($pages['previous'])
                $return_string .= '<li><a href="'.$this->viewHelper->routeFromHere(array('page' => $pages['previous'])).$query_string.'">&lt; Previous</a></li>';
            else
                $return_string .= '<li class="disabled"><a href="#">&lt; Previous</a></li>';
            
            // Produce full page range
            foreach($pages['pagesInRange'] as $page)
            {
                if ($page != $pages['current'])
                    $return_string .= '<li><a href="'.$this->viewHelper->routeFromHere(array('page' => $page)).$query_string.'">'.$page.'</a></li>';
                else
                    $return_string .= '<li class="active"><a href="#">'.$page.'</a></li>';
            }
            
            // Next page link
            if ($pages['next'])
                $return_string .= '<li><a href="'.$this->viewHelper->routeFromHere(array('page' => $pages['next'])).$query_string.'">Next &gt;</a></li>';
            else
                $return_string .= '<li class="disabled"><a href="#">Next &gt;</a></li>';
            
            // Last page link
            if ($pages['last'] != $pages['current'])
                $return_string .= '<li class="next"><a href="'.$this->viewHelper->routeFromHere(array('page' => $pages['last'])).$query_string.'">Last &raquo;</a></li>';
            else
                $return_string .= '<li class="next disabled"><a href="#">Last &raquo;</a></li>';
            
            $return_string .= '</ul></div>';
        }
        
        return $return_string;
    }
}