<?php
/**
 * Asset management class for AzuraCast.
 * Inspired by Asseter by Adam Banaszkiewicz: https://github.com/requtize
 *
 * @link      https://github.com/requtize/assetter
 */

namespace AzuraCast;

class Assets
{
    /** @var array Known libraries loaded in initialization. */
    protected $libraries = [];

    /** @var array An optional array lookup for versioned files. */
    protected $versioned_files = [];

    /** @var array Loaded libraries. */
    protected $loaded = [];

    /** @var string Default library group, if not specified. */
    protected $default_group = 'body';

    /** @var \App\Url URL Resolver object */
    protected $url;

    /** @var bool Whether the current loaded libraries have been sorted by order. */
    protected $is_sorted = true;

    /**
     * Assets constructor.
     *
     * @param array $libraries
     * @param array $versioned_files
     * @param \App\Url $url URL Resolver object
     */
    public function __construct(array $libraries = [], array $versioned_files = [], \App\Url $url)
    {
        foreach($libraries as $library) {
            $this->addLibrary($library);
        }

        $this->versioned_files = $versioned_files;
        $this->url = $url;
    }

    /**
     * Add a library to the collection.
     *
     * @param array $data Array with asset data.
     * @return $this
     */
    public function addLibrary(array $data)
    {
        $library_name = $data['name'] ?? uniqid();

        $this->libraries[$library_name] = [
            'name'     => $library_name,
            'order'    => $data['order'] ?? 0,
            'files'    => $data['files'] ?? [],
            'inline'   => $data['inline'] ?? [],
            'group'    => $data['group'] ?? $this->default_group,
            'require'  => $data['require'] ?? [],
        ];

        return $this;
    }

    /**
     * Loads assets from given name or array definition.
     *
     * @param mixed $data Name or array definition of library/asset.
     * @return self
     */
    public function load($data)
    {
        if (is_array($data)) {
            $item = [
                'name'     => $data['name'] ?? uniqid(),
                'order'    => $data['order'] ?? 0,
                'files'    => $data['files'] ?? [],
                'inline'   => $data['inline'] ?? [],
                'group'    => isset($data['group']) ? $data['group'] : $this->default_group,
                'require'  => isset($data['require']) ? $data['require'] : []
            ];
        } elseif (isset($this->libraries[$data])) {
            $item = $this->libraries[$data];
        } else {
            throw new \InvalidArgumentException(sprintf('Library %s not found!', $data));
        }

        $name = $item['name'];

        if (!isset($this->loaded[$name])) {
            if (!empty($item['require'])) {
                foreach($item['require'] as $require_name) {
                    $this->load($require_name);
                }
            }

            $this->loaded[$name] = $item;
            $this->is_sorted = false;
        }

        return $this;
    }

    /**
     * Add a single (or array of) javascript file[s].
     *
     * @param $js_script
     * @param null $group
     * @return $this
     */
    public function addJs($js_script, $group = null)
    {
        $this->load([
            'group' => $group,
            'order' => 100,
            'files' => [
                'js' => (is_array($js_script)) ? $js_script : array($js_script),
            ],
        ]);

        return $this;
    }

    /**
     * Add a single (or array of) javascript inline scripts.
     *
     * @param $js_script
     * @param null $group
     * @return $this
     */
    public function addInlineJs($js_script, $group = null)
    {
        $this->load([
            'group' => $group,
            'order' => 100,
            'inline' => [
                'js' => (is_array($js_script)) ? $js_script : array($js_script),
            ],
        ]);

        return $this;
    }

    /**
     * Add a single (or array of) CSS file[s].
     *
     * @param $css_script
     * @param null $group
     * @return $this
     */
    public function addCss($css_script, $group = null)
    {
        $this->load([
            'group' => $group,
            'order' => 100,
            'files' => [
                'css' => (is_array($css_script)) ? $css_script : array($css_script),
            ],
        ]);

        return $this;
    }

    /**
     * Add a single (or array of) inline CSS file[s].
     *
     * @param $css_script
     * @param null $group
     * @return $this
     */
    public function addInlineCss($css_script, $group = null)
    {
        $this->load([
            'group' => $group,
            'order' => 100,
            'inline' => [
                'css' => (is_array($css_script)) ? $css_script : array($css_script),
            ],
        ]);

        return $this;
    }

    /**
     * Returns CSS includes and inline tags from given group.
     *
     * @param  string $group Group name, or default if not specified.
     * @return string HTML tags as string.
     */
    public function css($group = null)
    {
        $this->_sort();

        $group = $group ?? $this->default_group;
        $result = [];

        foreach($this->loaded as $item)
        {
            if($item['group'] != $group) {
                continue;
            }

            if (!empty($item['files']['css'])) {
                foreach($item['files']['css'] as $file) {
                    $result[] = '<link rel="stylesheet" type="text/css" href="'.$this->_getUrl($file).'" />';
                }
            }

            if (!empty($item['inline']['css'])) {
                foreach($item['inline']['css'] as $inline) {
                    $result[] = '<style type="text/css">'.$inline.'</style>';
                }
            }
        }

        return implode("\n", $result)."\n";
    }

    /**
     * Returns JS tags from given group name.
     * If group name is asterisk (*), will return from all loaded groups.
     * @param  string $group Group name.
     * @return string HTML tags as string.
     */
    public function js($group = null)
    {
        $this->_sort();

        $group = $group ?? $this->default_group;
        $result = [];

        foreach($this->loaded as $item)
        {
            if($item['group'] != $group) {
                continue;
            }

            if (!empty($item['files']['js'])) {
                foreach($item['files']['js'] as $file) {
                    $result[] = '<script type="text/javascript" src="'.$this->_getUrl($file).'"></script>';
                }
            }

            if (!empty($item['inline']['js'])) {
                foreach($item['inline']['js'] as $inline) {
                    $result[] = '<script type="text/javascript">'.$inline.'</script>';
                }
            }
        }

        return implode("\n", $result)."\n";
    }

    /**
     * Resolve the URI of the resource, whether local or remote/CDN-based.
     *
     * @param $resource_uri
     * @return string The resolved resource URL.
     */
    protected function _getUrl($resource_uri)
    {
        if (isset($this->versioned_files[$resource_uri])) {
            $resource_uri = $this->versioned_files[$resource_uri];
        }

        if (preg_match('/^(https?:)?\/\//', $resource_uri)) {
            return $resource_uri;
        } else {
            return $this->url->content($resource_uri);
        }
    }

    /**
     * Sort the list of loaded libraries.
     */
    protected function _sort()
    {
        if (!$this->is_sorted) {
            $this->loaded = \Packaged\Helpers\Arrays::isort($this->loaded, 'order');
            $this->is_sorted = true;
        }
    }
}
