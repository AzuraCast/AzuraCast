<?php
namespace App;

use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use function base64_encode;
use function is_array;
use function is_callable;
use function preg_replace;
use function random_bytes;

/**
 * Asset management helper class.
 * Inspired by Asseter by Adam Banaszkiewicz: https://github.com/requtize
 * @link      https://github.com/requtize/assetter
 */
class Assets
{
    /** @var array Known libraries loaded in initialization. */
    protected $libraries = [];

    /** @var array An optional array lookup for versioned files. */
    protected $versioned_files = [];

    /** @var array Loaded libraries. */
    protected $loaded = [];

    /** @var bool Whether the current loaded libraries have been sorted by order. */
    protected $is_sorted = true;

    /** @var string A randomly generated number-used-once (nonce) for inline CSP. */
    protected $csp_nonce;

    /** @var array The loaded domains that should be included in the CSP header. */
    protected $csp_domains;

    /**
     * Assets constructor.
     *
     * @param array $libraries
     * @param array $versioned_files
     *
     * @throws \Exception
     */
    public function __construct(array $libraries = [], array $versioned_files = [])
    {
        foreach ($libraries as $library_name => $library) {
            $this->addLibrary($library, $library_name);
        }

        $this->versioned_files = $versioned_files;
        $this->csp_nonce = preg_replace('/[^A-Za-z0-9\+\/=]/', '', base64_encode(random_bytes(18)));
        $this->csp_domains = [];
    }

    /**
     * Add a library to the collection.
     *
     * @param array $data Array with asset data.
     * @param string|null $library_name
     *
     * @return $this
     */
    public function addLibrary(array $data, string $library_name = null): self
    {
        $library_name = $library_name ?? uniqid('', false);

        $this->libraries[$library_name] = [
            'name' => $library_name,
            'order' => $data['order'] ?? 0,
            'files' => $data['files'] ?? [],
            'inline' => $data['inline'] ?? [],
            'require' => $data['require'] ?? [],
            'replace' => $data['replace'] ?? [],
        ];

        return $this;
    }

    /**
     * @param string $library_name
     *
     * @return array|null
     */
    public function getLibrary(string $library_name): ?array
    {
        return $this->libraries[$library_name] ?? null;
    }

    /**
     * Returns the randomly generated nonce for inline CSP for this request.
     * @return string
     */
    public function getCspNonce(): string
    {
        return $this->csp_nonce;
    }

    /**
     * Returns the list of approved domains for CSP header inclusion.
     * @return array
     */
    public function getCspDomains(): array
    {
        return $this->csp_domains;
    }

    /**
     * Add a single javascript file.
     *
     * @param string|array $js_script
     *
     * @return $this
     */
    public function addJs($js_script): self
    {
        $this->load([
            'order' => 100,
            'files' => [
                'js' => [
                    (is_array($js_script)) ? $js_script : ['src' => $js_script],
                ],
            ],
        ]);

        return $this;
    }

    /**
     * Loads assets from given name or array definition.
     *
     * @param mixed $data Name or array definition of library/asset.
     *
     * @return self
     */
    public function load($data): self
    {
        if (is_array($data)) {
            $item = [
                'name' => $data['name'] ?? uniqid('', false),
                'order' => $data['order'] ?? 0,
                'files' => $data['files'] ?? [],
                'inline' => $data['inline'] ?? [],
                'require' => $data['require'] ?? [],
                'replace' => $data['replace'] ?? [],
            ];
        } elseif (isset($this->libraries[$data])) {
            $item = $this->libraries[$data];
        } else {
            throw new InvalidArgumentException(sprintf('Library %s not found!', $data));
        }

        $name = $item['name'];

        // Check if a library is "replaced" by other libraries already loaded.
        $is_replaced = false;
        foreach ($this->loaded as $loaded_name => $loaded_item) {
            if (!empty($loaded_item['replace']) && in_array($name, (array)$loaded_item['replace'], true)) {
                $is_replaced = true;
                break;
            }
        }

        if (!$is_replaced && !isset($this->loaded[$name])) {
            if (!empty($item['replace'])) {
                foreach ((array)$item['replace'] as $replace_name) {
                    $this->unload($replace_name);
                }
            }

            if (!empty($item['require'])) {
                foreach ((array)$item['require'] as $require_name) {
                    $this->load($require_name);
                }
            }

            $this->loaded[$name] = $item;
            $this->is_sorted = false;
        }

        return $this;
    }

    /**
     * Unload a given library if it's already loaded.
     *
     * @param string $name
     *
     * @return self
     */
    public function unload(string $name): self
    {
        if (isset($this->loaded[$name])) {
            unset($this->loaded[$name]);
            $this->is_sorted = false;
        }

        return $this;
    }

    /**
     * Add a single javascript inline script.
     *
     * @param string|array $js_script
     *
     * @return $this
     */
    public function addInlineJs($js_script, $order = 100): self
    {
        $this->load([
            'order' => $order,
            'inline' => [
                'js' => (is_array($js_script)) ? $js_script : [$js_script],
            ],
        ]);

        return $this;
    }

    /**
     * Add a single CSS file.
     *
     * @param string|array $css_script
     *
     * @return $this
     */
    public function addCss($css_script, $order = 100): self
    {
        $this->load([
            'order' => $order,
            'files' => [
                'css' => [
                    (is_array($css_script)) ? $css_script : ['src' => $css_script],
                ],
            ],
        ]);

        return $this;
    }

    /**
     * Add a single inline CSS file[s].
     *
     * @param string|array $css_script
     *
     * @return $this
     */
    public function addInlineCss($css_script): self
    {
        $this->load([
            'order' => 100,
            'inline' => [
                'css' => (is_array($css_script)) ? $css_script : [$css_script],
            ],
        ]);

        return $this;
    }

    /**
     * Returns all CSS includes and inline styles.
     * @return string HTML tags as string.
     */
    public function css()
    {
        $this->_sort();

        $result = [];
        foreach ($this->loaded as $item) {
            if (!empty($item['files']['css'])) {
                foreach ($item['files']['css'] as $file) {
                    $compiled_attributes = $this->compileAttributes($file, [
                        'rel' => 'stylesheet',
                        'type' => 'text/css',
                    ]);

                    $result[] = '<link ' . implode(' ', $compiled_attributes) . ' />';
                }
            }

            if (!empty($item['inline']['css'])) {
                foreach ($item['inline']['css'] as $inline) {
                    if (!empty($inline)) {
                        $result[] = '<style type="text/css" nonce="' . $this->csp_nonce . '">' . "\n" . $inline . '</style>';
                    }
                }
            }
        }

        return implode("\n", $result) . "\n";
    }

    /**
     * Returns all script include tags.
     * @return string HTML tags as string.
     */
    public function js()
    {
        $this->_sort();

        $result = [];
        foreach ($this->loaded as $item) {
            if (!empty($item['files']['js'])) {
                foreach ($item['files']['js'] as $file) {
                    $compiled_attributes = $this->compileAttributes($file, [
                        'type' => 'text/javascript',
                    ]);

                    $result[] = '<script ' . implode(' ', $compiled_attributes) . '></script>';
                }
            }
        }

        return implode("\n", $result) . "\n";
    }

    /**
     * Return any inline JavaScript.
     *
     * @param ServerRequestInterface $request
     *
     * @return string
     */
    public function inlineJs(ServerRequestInterface $request): string
    {
        $this->_sort();

        $result = [];
        foreach ($this->loaded as $item) {
            if (!empty($item['inline']['js'])) {
                foreach ($item['inline']['js'] as $inline) {
                    if (is_callable($inline)) {
                        $inline = $inline($request);
                    }

                    if (!empty($inline)) {
                        $result[] = '<script type="text/javascript" nonce="' . $this->csp_nonce . '">' . "\n" . $inline . '</script>';
                    }
                }
            }
        }

        return implode("\n", $result) . "\n";
    }

    /**
     * Sort the list of loaded libraries.
     */
    protected function _sort()
    {
        if (!$this->is_sorted) {
            uasort($this->loaded, function ($a, $b) {
                return $a['order'] <=> $b['order']; // SPACESHIP!
            });

            $this->is_sorted = true;
        }
    }

    /**
     * Build the proper include tag for a JS/CSS include.
     *
     * @param array $file
     * @param array $defaults
     *
     * @return array
     */
    protected function compileAttributes(array $file, array $defaults = []): array
    {
        if (isset($file['src'])) {
            $defaults['src'] = $this->getUrl($file['src']);
            unset($file['src']);
        }

        if (isset($file['href'])) {
            $defaults['href'] = $this->getUrl($file['href']);
            unset($file['href']);
        }

        if (isset($file['integrity'])) {
            $defaults['crossorigin'] = 'anonymous';
        }

        $attributes = array_merge($defaults, $file);

        $compiled_attributes = [];
        foreach ($attributes as $attr_key => $attr_val) {
            // Check for attributes like "defer"
            if ($attr_val === true) {
                $compiled_attributes[] = $attr_key;
            } else {
                $compiled_attributes[] = $attr_key . '="' . $attr_val . '"';
            }
        }

        return $compiled_attributes;
    }

    /**
     * Resolve the URI of the resource, whether local or remote/CDN-based.
     *
     * @param string $resource_uri
     *
     * @return string The resolved resource URL.
     */
    public function getUrl($resource_uri): string
    {
        if (isset($this->versioned_files[$resource_uri])) {
            $resource_uri = $this->versioned_files[$resource_uri];
        }

        if (preg_match('/^(https?:)?\/\//', $resource_uri)) {
            $this->addDomainToCsp($resource_uri);
            return $resource_uri;
        }

        return '/static/' . $resource_uri;
    }

    /**
     * Add the loaded domain to the full list of CSP-approved domains.
     *
     * @param string $src
     */
    protected function addDomainToCsp($src): void
    {
        $src_parts = parse_url($src);

        $domain = $src_parts['scheme'] . '://' . $src_parts['host'];

        if (!isset($this->csp_domains[$domain])) {
            $this->csp_domains[$domain] = $domain;
        }
    }
}
