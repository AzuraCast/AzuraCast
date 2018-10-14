<?php
namespace App\Controller\Stations\Files;

use App\Cache;
use App\Http\Router;
use Doctrine\ORM\EntityManager;
use App\Entity;

abstract class FilesControllerAbstract
{
    /** @var EntityManager */
    protected $em;

    /** @var Router */
    protected $router;

    /** @var string */
    protected $csrf_namespace = 'stations_files';

    /** @var Cache */
    protected $cache;

    /** @var array */
    protected $form_config;

    /** @var Entity\Repository\StationMediaRepository */
    protected $media_repo;

    /** @var Entity\Repository\StationPlaylistMediaRepository */
    protected $playlists_media_repo;

    /**
     * @param EntityManager $em
     * @param Router $router
     * @param Cache $cache
     * @param array $form_config
     * @see \App\Provider\StationsProvider
     */
    public function __construct(EntityManager $em, Router $router, Cache $cache, array $form_config)
    {
        $this->em = $em;
        $this->router = $router;
        $this->cache = $cache;
        $this->form_config = $form_config;

        $this->media_repo = $this->em->getRepository(Entity\StationMedia::class);
        $this->playlists_media_repo = $this->em->getRepository(Entity\StationPlaylistMedia::class);
    }

    protected function _filterPath($base_path, $path)
    {
        $path = str_replace(['../', './'], ['', ''], $path);
        $path = trim($path, '/');

        $dir_path = $base_path.DIRECTORY_SEPARATOR.dirname($path);
        $full_path = $base_path.DIRECTORY_SEPARATOR.$path;

        if ($real_path = realpath($dir_path)) {
            if (substr($full_path, 0, strlen($base_path)) !== $base_path) {
                throw new \Exception('New location not inside station media directory.');
            }
        } else {
            throw new \Exception('Parent directory could not be resolved.');
        }

        return [$path, $full_path];
    }
}
