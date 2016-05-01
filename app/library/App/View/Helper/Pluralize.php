<?php
namespace App\View\Helper;
class Pluralize extends HelperAbstract
{
    public function pluralize($word, $num = 0)
    {
        $num = (int)$num;
        
        if ($num == 1)
            return $word;
        else
            return \Doctrine\Common\Inflector\Inflector::pluralize($word);
    }
}