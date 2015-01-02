<?php
namespace DF\View\Helper;
class Table extends \Zend_View_Helper_Abstract
{
    protected $header;
    protected $footer;
    protected $body;
    protected $options;
    
    protected function _setOptions(array $options)
    {
        $reflection = new ReflectionObject($this);
        
        foreach($options as $function => $params)
        {
            if($reflection->hasMethod("set$function"))
                $reflection->getMethod("set$function")->invokeArgs($this, $params);
        }
    }
    
    public function table(array $options = null)
    {
        if( $options )
            $this->_setOptions($options);
        
        return $this;
    }
    
    public function setHeader(array $header)
    {
        $this->header = $header;
        
        return $this;
    }
    
    public function setBody(array $data)
    {
        $this->body = $data;
        
        return $this;
    }
    
    public function setFooter(array $footer)
    {
        $this->footer = $footer;
        
        return $this;
    }
    
    public function setOptions(array $options)
    {
        $this->options = $options;
        
        if( !isset($this->options['id']) )
            $this->options['id'] = sprintf('table_%u', time());
        
        return $this;
    }
    
    public function render()
    {
        $options = $this->_renderOptions();
        
        $output = "<table $options>\n";
        
        $output .= $this->_renderHeader();
        $output .= $this->_renderBody();
        $output .= $this->_renderFooter();
        
        $output .= "</table>\n\n";
        
        return $output;
    }
    
    protected function _renderOptions()
    {
        if( !isset($this->options['id']) )
            $this->options['id'] = sprintf('table_%u', time());
        
        if( !isset($this->options['class']) )
            $this->options['class'] = 'datatable';
            
        $options = array();
        foreach( (array)$this->options as $attrib => $value )
        {
            $options[] = htmlspecialchars($attrib) . '="' . htmlspecialchars($value) . '"';
        }
        
        if( !empty($options) )
            return implode(" ", $options);
        else
            return "";
    }
    
    protected function _renderHeader()
    {
        if( empty($this->header) )
            return "";
        
        $output = "<thead>\n";
        
        foreach( (array)$this->header as $row )
        {
            $tr = "";
            if( is_array($row) )
            {
                foreach( $row as $column )
                {
                    if( is_array($column) )
                    {
                        $data = isset($column['data']) ? $column['data'] : "";
                        unset($column['data']);

                        $sortable = isset($column['sortable']) ? (array)$column['sortable'] : array();
                        unset($column['sortable']);

                        $tab = isset($column['tab']) ? $column['tab'] : "";
                        unset($column['tab']);

                        $attributes = array();
                        foreach( $column as $attrib => $value )
                        {
                            $attributes[] = htmlspecialchars($attrib) . '="' . htmlspecialchars($value) . '"';
                        }
                        
                        if( !empty($attributes) )
                            $attributes = " " . implode(" ", $attributes);
                        else
                            $attributes = "";


                        if( !empty($sortable) )
                        {
                            $class = array();

                            $direction = isset($sortable['default_dir']) && in_array(strtolower($sortable['default_dir']), array('asc', 'desc'))
                                    ? strtolower($sortable['default_dir']) : 'asc';

                            $sorted = false;
                            if( isset($sortable['default']) && $sortable['default'] == true )
                                $sorted = true;

                            if( $current_sortby = \Zend_Controller_Front::getInstance()->getRequest()->getParam('sortby') )
                            {
                                if( strtolower($current_sortby) == strtolower($sortable['key']) )
                                    $sorted = true;
                                else
                                    $sorted = false;
                            }

                            if( $current_sortdir = \Zend_Controller_Front::getInstance()->getRequest()->getParam('sortdir') )
                            {
                                if( $sorted )
                                {
                                    $class[] = 'sort-' . strtolower($current_sortdir);

                                    if(  strtolower($current_sortdir) == 'asc' )
                                        $direction = 'desc';
                                    else
                                        $direction = 'asc';
                                }
                            }

                            $url_append = isset($sortable['url_append']) ? $sortable['url_append'] : '';

                            $tab_append = !empty($tab) ? '#'.$tab.'-tab' : '';

                            $uri = \Zend_Controller_Front::getInstance()->getRequest()->getRequestUri();
                            $query = parse_url($uri, PHP_URL_QUERY);
                            $query_append = isset($query) ? '?'.$query : '';

                            $sort_url = \DF\Url::route(array('sortby' => $sortable['key'], 'sortdir' => $direction), 'default', false) . $url_append . $query_append . $tab_append;

                            $data = '<a href="'.$sort_url.'" class="'.implode(' ', $class).'">' . $data . '</a>';
                        }
                        
                        $tr .= "\t\t<th$attributes>$data</th>\n";
                    }
                    else
                    {
                        $tr .= "\t\t<th>$column</th>\n";
                    }
                }
            }
            
            $output .= "\t<tr>\n$tr\t</tr>\n";
        }
        
        
        $output .= "</thead>\n";
        
        return $output;
    }
    
    protected function _renderBody()
    {
        if( empty($this->body) )
            return "<tbody></tbody>";
        
        $output = "<tbody>\n";
        
        $even = false;
        
        foreach( (array)$this->body as $row )
        {
            if( isset($row['data']) )
            {
                $columns = $row['data'];
                unset($row['data']);

                $rowattributes = array();
                foreach( $row as $attrib => $value )
                {
                    if (($attrib == 'class'))
                        $value .= ($even) ? ' even' : ' odd';
                    
                    $rowattributes[] = htmlspecialchars($attrib) . '="' . htmlspecialchars($value) . '"';
                }

                if ( !empty($rowattributes) )
                    $rowattributes = " " . implode(" ", $rowattributes);
                else
                    $rowattributes = "";
            }
            else
            {
                $columns = $row;
                
                $rowattributes = "";
            }

            $tr = "";
            if( is_array($columns) )
            {
                foreach( $columns as $column )
                {
                    if( is_array($column) )
                    {
                        $data = isset($column['data']) ? $column['data'] : "";
                        unset($column['data']);
                        
                        $heading = isset($column['heading']) ? (bool)$column['heading'] : false;
                        unset($column['heading']);
                        
                        $attributes = array();
                        foreach( $column as $attrib => $value )
                        {
                            $attributes[] = htmlspecialchars($attrib) . '="' . htmlspecialchars($value) . '"';
                        }
                        
                        if( $heading )
                            $attributes[] = 'scope="row"';
                        
                        if( !empty($attributes) )
                            $attributes = " " . implode(" ", $attributes);
                        else
                            $attributes = "";
                        
                        if( !$heading )
                            $tr .= "\t\t<td$attributes>$data</td>\n";
                        else
                            $tr .= "\t\t<th$attributes>$data</th>\n";
                    }
                    else
                    {
                        $tr .= "\t\t<td>$column</td>\n";
                    }
                }
            }
            
            if( $even || $rowattributes )
                $output .= "\t<tr$rowattributes>\n$tr\n\t</tr>\n";
            else
                $output .= "\t<tr class=\"odd\">\n$tr\n\t</tr>\n";
            
            $even = !$even;
        }
        
        
        $output .= "</tbody>\n";
        
        return $output;
    }
    
    protected function _renderFooter()
    {
        if( empty($this->footer) )
            return "";
        
        $output = "<tfoot>\n";
        
        foreach( (array)$this->footer as $row )
        {
            $tr = "";
            if( is_array($row) )
            {
                foreach( $row as $column )
                {
                    if( is_array($column) )
                    {
                        $data = isset($column['data']) ? $column['data'] : "";
                        unset($column['data']);
                        
                        $heading = isset($column['heading']) ? (bool)$column['heading'] : false;
                        unset($column['heading']);
                        
                        $attributes = array();
                        foreach( $column as $attrib => $value )
                        {
                            $attributes[] = htmlspecialchars($attrib) . '="' . htmlspecialchars($value) . '"';
                        }
                        
                        if( $heading )
                            $attributes[] = 'scope="row"';
                        
                        if( !empty($attributes) )
                            $attributes = " " . implode(" ", $attributes);
                        else
                            $attributes = "";
                        
                        if( !$heading )
                            $tr .= "\t\t<td$attributes>$data</td>\n";
                        else
                            $tr .= "\t\t<th$attributes>$data</th>\n";
                    }
                    else
                    {
                        $tr .= "\t\t<td>$column</td>\n";
                    }
                }
            }
            
            $output .= "\t<tr>\n$tr\n</tr>\n";
        }
        
        
        $output .= "</tfoot>\n";
        
        return $output;
    }
    
    public function __toString()
    {
        return $this->render();
    }
}