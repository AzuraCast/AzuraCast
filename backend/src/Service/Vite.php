<?php

declare(strict_types=1);

namespace App\Service;

use App\Container\EnvironmentAwareTrait;
use App\Service\Vite\ScriptDependencies;
use App\Utilities\Json;
use InvalidArgumentException;

final class Vite
{
    use EnvironmentAwareTrait;

    private const string MANIFEST_PATH = '/web/static/vite_dist/.vite/manifest.json';
    private const string ASSET_BASE_URI = '/static/vite_dist';

    /**
     * @var null|array<string, array{
     *     file: string,
     *     src: string,
     *     name?: string,
     *     isDynamicEntry?: bool,
     *     imports?: string[],
     *     css?: string[]
     * }>
     */
    private array|null $manifest = null;

    private function initManifest(): void
    {
        if ($this->manifest !== null) {
            return;
        }

        if (!$this->environment->isProduction()) {
            $this->manifest = [];
        } else {
            $this->manifest = Json::loadFromFile(
                $this->environment->getBaseDirectory() . self::MANIFEST_PATH
            );
        }
    }

    public function getScriptAndDependencies(
        string $componentPath
    ): ScriptDependencies {
        if (!$this->environment->isProduction()) {
            return new ScriptDependencies(
                js: $this->assetUrl($componentPath)
            );
        }

        $this->initManifest();

        if (!isset($this->manifest[$componentPath])) {
            throw new InvalidArgumentException(
                sprintf('Vue component "%s" not found in manifest.', $componentPath)
            );
        }

        $deps = new ScriptDependencies(
            js: $this->assetUrl($this->manifest[$componentPath]['file'])
        );
        $visitedNodes = [];

        $this->fetchDependencyTree(
            $componentPath,
            $deps,
            $visitedNodes
        );

        return $deps;
    }

    private function fetchDependencyTree(
        string $component,
        ScriptDependencies $dependencies,
        array &$visitedNodes
    ): void {
        if (!isset($this->manifest[$component]) || isset($visitedNodes[$component])) {
            return;
        }

        $visitedNodes[$component] = true;

        $componentInfo = $this->manifest[$component];

        $fileUrl = $this->assetUrl($componentInfo['file']);
        if ($fileUrl !== $dependencies->js) {
            $dependencies->prefetch[] = $fileUrl;
        }

        if (isset($componentInfo['css'])) {
            foreach ($componentInfo['css'] as $css) {
                $dependencies->css[] = $this->assetUrl($css);
            }
        }

        if (isset($componentInfo['imports'])) {
            foreach ($componentInfo['imports'] as $import) {
                $this->fetchDependencyTree(
                    $import,
                    $dependencies,
                    $visitedNodes
                );
            }
        }
    }

    public function getImagePath(
        string $originalPath
    ): string {
        if (!$this->environment->isProduction()) {
            return self::ASSET_BASE_URI . '/' . $originalPath;
        }

        $this->initManifest();

        if (!isset($this->manifest[$originalPath])) {
            throw new InvalidArgumentException(
                sprintf('Image "%s" not found in manifest.', $originalPath)
            );
        }

        return $this->assetUrl($this->manifest[$originalPath]['file']);
    }

    private function assetUrl(
        string $url
    ): string {
        return sprintf(
            '%s/%s',
            self::ASSET_BASE_URI,
            $url
        );
    }
}
