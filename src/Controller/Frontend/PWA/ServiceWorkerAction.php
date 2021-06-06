<?php

namespace App\Controller\Frontend\PWA;

use App\Assets;
use App\Environment;
use App\Http\Response;
use App\Http\ServerRequest;
use DI\FactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Finder\Finder;

class ServiceWorkerAction
{
    public const SW_BASE = <<<'JS'
        const cacheName = 'assets'; // Change value to force update
        const cacheFiles = [];

        self.addEventListener('install', event => {
          // Kick out the old service worker
          self.skipWaiting();

          event.waitUntil(
            caches.open(cacheName).then(cache => {
              return cache.addAll(cacheFiles);
            })
          );
        });

        self.addEventListener('activate', event => {
          // Delete any non-current cache
          event.waitUntil(
            caches.keys().then(keys => {
              Promise.all(
                keys.map(key => {
                  if (![cacheName].includes(key)) {
                    return caches.delete(key);
                  }
                })
              );
            })
          );
        });

        // Offline-first, cache-first strategy
        // Kick off two asynchronous requests, one to the cache and one to the network
        // If there's a cached version available, use it, but fetch an update for next time.
        // Gets data on screen as quickly as possible, then updates once the network has returned the latest data.
        self.addEventListener('fetch', event => {
          event.respondWith(
            caches.open(cacheName).then(cache => {
              return cache.match(event.request).then(response => {
                return response || fetch(event.request).then(networkResponse => {
                  cache.put(event.request, networkResponse.clone());
                  return networkResponse;
                });
              });
            })
          );
        });
    JS;

    public function __invoke(
        ServerRequest $request,
        Response $response,
        Environment $environment,
        FactoryInterface $factory
    ): ResponseInterface {
        $assets = $factory->make(
            Assets::class,
            [
                'request' => $request,
            ]
        );

        $swContents = self::SW_BASE;

        $findString = 'const cacheFiles = [];';
        if (!str_contains($swContents, $findString)) {
            throw new \RuntimeException('Service worker template does not contain proper placeholder.');
        }

        $cacheFiles = [];

        // Cache the compiled assets that would be used on public players.
        $assets->load('minimal')
            ->load('Vue_PublicFullPlayer');

        $loadedFiles = $assets->getLoadedFiles();

        foreach ($loadedFiles as $file) {
            if (!str_starts_with($file, 'http')) {
                $cacheFiles[] = $file;
            }
        }

        // Cache images and icons
        $staticBase = $environment->getBaseDirectory() . '/web' . $environment->getAssetUrl();

        $otherStaticFiles = Finder::create()
            ->files()
            ->in($staticBase)
            ->depth('>=1')
            ->exclude(['dist', 'api']);

        foreach($otherStaticFiles as $file) {
            $cacheFiles[] = $environment->getAssetUrl().'/'.$file->getRelativePathname();
        }

        $replaceString = 'const cacheFiles = ' . json_encode($cacheFiles) . ';';

        $swContents = str_replace($findString, $replaceString, $swContents);

        return $response
            ->withHeader('Content-Type', 'text/javascript')
            ->write($swContents);
    }
}
