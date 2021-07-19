<?php

declare(strict_types=1);

namespace App;

use App\Traits\RequestAwareTrait;
use App\Utilities\Json;
use GuzzleHttp\Psr7\Uri;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;

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
    use RequestAwareTrait;

    /** @var array<string, array> Known libraries loaded in initialization. */
    protected array $libraries = [];

    /** @var array<string, string> An optional array lookup for versioned files. */
    protected array $versioned_files = [];

    /** @var array<string, array> Loaded libraries. */
    protected array $loaded = [];

    /** @var bool Whether the current loaded libraries have been sorted by order. */
    protected bool $is_sorted = true;

    /** @var string A randomly generated number-used-once (nonce) for inline CSP. */
    protected string $csp_nonce;

    /** @var array The loaded domains that should be included in the CSP header. */
    protected array $csp_domains;

    public function __construct(
        protected Environment $environment,
        Config $config
    ) {
        foreach ($config->get('assets') as $library_name => $library) {
            $this->addLibrary($library, $library_name);
        }

        $versioned_files = Json::loadFromFile($environment->getBaseDirectory() . '/web/static/assets.json');
        $this->versioned_files = $versioned_files;

        $vueComponents = Json::loadFromFile($environment->getBaseDirectory() . '/web/static/webpack.json');
        $this->addVueComponents($vueComponents);

        $this->csp_nonce = (string)preg_replace('/[^A-Za-z0-9\+\/=]/', '', base64_encode(random_bytes(18)));
        $this->csp_domains = [];
    }

    protected function addVueComponents(array $vueComponents = []): void
    {
        if (!empty($vueComponents['entrypoints'])) {
            foreach ($vueComponents['entrypoints'] as $componentName => $componentDeps) {
                $componentName = 'Vue_' . $componentName;

                $library = $this->libraries[$componentName] ?? [
                        'order' => 10,
                        'require' => [],
                        'files' => [],
                    ];

                if (!in_array('vue-component-common', $library['require'], true)) {
                    $library['require'][] = 'vue-component-common';
                }

                foreach ($componentDeps['js'] as $componentDep) {
                    if ('dist/vendor.js' !== $componentDep) {
                        $library['files']['js'][] = [
                            'src' => $componentDep,
                        ];
                    }
                }

                $this->addLibrary($library, $componentName);
            }
        }
    }

    /**
     * Add a library to the collection.
     *
     * @param array $data Array with asset data.
     * @param string|null $library_name
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
     * @return mixed[]|null
     */
    public function getLibrary(string $library_name): ?array
    {
        return $this->libraries[$library_name] ?? null;
    }

    /**
     * Returns the randomly generated nonce for inline CSP for this request.
     */
    public function getCspNonce(): string
    {
        return $this->csp_nonce;
    }

    /**
     * Returns the list of approved domains for CSP header inclusion.
     *
     * @return string[]
     */
    public function getCspDomains(): array
    {
        return $this->csp_domains;
    }

    /**
     * Add a single javascript file.
     *
     * @param array|string $js_script
     */
    public function addJs(array|string $js_script): self
    {
        $this->load(
            [
                'order' => 100,
                'files' => [
                    'js' => [
                        (is_array($js_script)) ? $js_script : ['src' => $js_script],
                    ],
                ],
            ]
        );

        return $this;
    }

    /**
     * Loads assets from given name or array definition.
     *
     * @param mixed $data Name or array definition of library/asset.
     */
    public function load(mixed $data): self
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
        foreach ($this->loaded as $loaded_item) {
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
     * @param array|string $js_script
     * @param int $order
     */
    public function addInlineJs(array|string $js_script, int $order = 100): self
    {
        $this->load(
            [
                'order' => $order,
                'inline' => [
                    'js' => (is_array($js_script)) ? $js_script : [$js_script],
                ],
            ]
        );

        return $this;
    }

    public function addVueRender(string $name, string $elementId, array $props = []): self
    {
        $this->load($name);

        $nameWithoutPrefix = str_replace('Vue_', '', $name);
        $propsJson = json_encode($props, JSON_THROW_ON_ERROR);

        $this->addInlineJs(
            <<<JS
                let ${name};

                $(function () {
                    ${name} = new Vue({
                        el: '${elementId}',
                        render: function (createElement) {
                            return createElement(${nameWithoutPrefix}.default, {
                                props: ${propsJson}
                            });
                        }
                    });
                });
            JS
        );

        return $this;
    }

    /**
     * Add a single CSS file.
     *
     * @param array|string $css_script
     * @param int $order
     */
    public function addCss(array|string $css_script, int $order = 100): self
    {
        $this->load(
            [
                'order' => $order,
                'files' => [
                    'css' => [
                        (is_array($css_script)) ? $css_script : ['src' => $css_script],
                    ],
                ],
            ]
        );

        return $this;
    }

    /**
     * Add a single inline CSS file[s].
     *
     * @param array|string $css_script
     */
    public function addInlineCss(array|string $css_script): self
    {
        $this->load(
            [
                'order' => 100,
                'inline' => [
                    'css' => (is_array($css_script)) ? $css_script : [$css_script],
                ],
            ]
        );

        return $this;
    }

    /**
     * Returns all CSS includes and inline styles.
     *
     * @return string HTML tags as string.
     */
    public function css(): string
    {
        $this->sort();

        $result = [];
        foreach ($this->loaded as $item) {
            if (!empty($item['files']['css'])) {
                foreach ($item['files']['css'] as $file) {
                    $compiled_attributes = $this->compileAttributes(
                        $file,
                        [
                            'rel' => 'stylesheet',
                            'type' => 'text/css',
                        ]
                    );

                    $result[] = '<link ' . implode(' ', $compiled_attributes) . ' />';
                }
            }

            if (!empty($item['inline']['css'])) {
                foreach ($item['inline']['css'] as $inline) {
                    if (!empty($inline)) {
                        $result[] = sprintf(
                            '<style type="text/css" nonce="%s">%s%s</style>',
                            $this->csp_nonce,
                            "\n",
                            $inline
                        );
                    }
                }
            }
        }

        return implode("\n", $result) . "\n";
    }

    /**
     * Returns all script include tags.
     *
     * @return string HTML tags as string.
     */
    public function js(): string
    {
        $this->sort();

        $result = [];
        foreach ($this->loaded as $item) {
            if (!empty($item['files']['js'])) {
                foreach ($item['files']['js'] as $file) {
                    $compiled_attributes = $this->compileAttributes(
                        $file,
                        [
                            'type' => 'text/javascript',
                        ]
                    );

                    $result[] = '<script ' . implode(' ', $compiled_attributes) . '></script>';
                }
            }
        }

        return implode("\n", $result) . "\n";
    }

    /**
     * Return any inline JavaScript.
     */
    public function inlineJs(): string
    {
        $this->sort();

        $result = [];
        foreach ($this->loaded as $item) {
            if (!empty($item['inline']['js'])) {
                foreach ($item['inline']['js'] as $inline) {
                    if (is_callable($inline)) {
                        $inline = $inline($this->request);
                    }

                    if (!empty($inline)) {
                        $result[] = sprintf(
                            '<script type="text/javascript" nonce="%s">%s%s</script>',
                            $this->csp_nonce,
                            "\n",
                            $inline
                        );
                    }
                }
            }
        }

        return implode("\n", $result) . "\n";
    }

    /**
     * Sort the list of loaded libraries.
     */
    protected function sort(): void
    {
        if (!$this->is_sorted) {
            uasort(
                $this->loaded,
                static function ($a, $b): int {
                    return $a['order'] <=> $b['order']; // SPACESHIP!
                }
            );

            $this->is_sorted = true;
        }
    }

    /**
     * Build the proper include tag for a JS/CSS include.
     *
     * @param array $file
     * @param array $defaults
     *
     * @return string[]
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
     * @return string[] The paths to all currently loaded files.
     */
    public function getLoadedFiles(): array
    {
        $this->sort();

        $result = [];
        foreach ($this->loaded as $item) {
            if (!empty($item['files']['js'])) {
                foreach ($item['files']['js'] as $file) {
                    if (isset($file['src'])) {
                        $result[] = $this->getUrl($file['src']);
                    }
                }
            }

            if (!empty($item['files']['css'])) {
                foreach ($item['files']['css'] as $file) {
                    if (isset($file['href'])) {
                        $result[] = $this->getUrl($file['href']);
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Resolve the URI of the resource, whether local or remote/CDN-based.
     *
     * @param string $resource_uri
     *
     * @return string The resolved resource URL.
     */
    public function getUrl(string $resource_uri): string
    {
        if (isset($this->versioned_files[$resource_uri])) {
            $resource_uri = $this->versioned_files[$resource_uri];
        }

        if (str_starts_with($resource_uri, 'http')) {
            $this->addDomainToCsp($resource_uri);
            return $resource_uri;
        }

        return $this->environment->getAssetUrl() . '/' . $resource_uri;
    }

    /**
     * Add the loaded domain to the full list of CSP-approved domains.
     *
     * @param string $src
     */
    protected function addDomainToCsp(string $src): void
    {
        $uri = new Uri($src);

        $domain = $uri->getScheme() . '://' . $uri->getHost();
        if (!isset($this->csp_domains[$domain])) {
            $this->csp_domains[$domain] = $domain;
        }
    }

    public function writeCsp(ResponseInterface $response): ResponseInterface
    {
        $csp = [];
        if (null !== $this->request && 'https' === $this->request->getUri()->getScheme()) {
            $csp[] = 'upgrade-insecure-requests';
        }

        // CSP JavaScript policy
        // Note: unsafe-eval included for Vue template compiling
        $cspScriptSrc = $this->getCspDomains();
        $cspScriptSrc[] = "'self'";
        $cspScriptSrc[] = "'unsafe-eval'";
        $cspScriptSrc[] = "'nonce-" . $this->getCspNonce() . "'";
        $csp[] = 'script-src ' . implode(' ', $cspScriptSrc);

        $cspWorkerSrc = [];
        $cspWorkerSrc[] = "blob:";
        $cspWorkerSrc[] = "'self'";

        $csp[] = 'worker-src ' . implode(' ', $cspWorkerSrc);

        return $response->withHeader('Content-Security-Policy', implode('; ', $csp));
    }
}
