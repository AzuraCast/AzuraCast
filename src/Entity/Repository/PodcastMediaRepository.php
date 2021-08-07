<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Doctrine\Repository;
use App\Entity\PodcastEpisode;
use App\Entity\PodcastMedia;
use App\Environment;
use App\Exception\InvalidPodcastMediaFileException;
use App\Media\MetadataManager;
use Azura\Files\ExtendedFilesystemInterface;
use Intervention\Image\ImageManager;
use League\Flysystem\UnableToDeleteFile;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Serializer;

class PodcastMediaRepository extends Repository
{
    public function __construct(
        ReloadableEntityManagerInterface $em,
        Serializer $serializer,
        Environment $environment,
        LoggerInterface $logger,
        protected MetadataManager $metadataManager,
        protected ImageManager $imageManager,
        protected PodcastEpisodeRepository $episodeRepo,
    ) {
        parent::__construct($em, $serializer, $environment, $logger);
    }

    public function upload(
        PodcastEpisode $episode,
        string $originalPath,
        string $uploadPath,
        ?ExtendedFilesystemInterface $fs = null
    ): void {
        $podcast = $episode->getPodcast();
        $storageLocation = $podcast->getStorageLocation();

        $fs ??= $storageLocation->getFilesystem();

        // Do an early metadata check of the new media to avoid replacing a valid file with an invalid one.
        $metadata = $this->metadataManager->read($uploadPath);

        if (!in_array($metadata->getMimeType(), ['audio/x-m4a', 'audio/mpeg'])) {
            throw new InvalidPodcastMediaFileException(
                'Invalid Podcast Media mime type: ' . $metadata->getMimeType()
            );
        }

        $existingMedia = $episode->getMedia();
        if ($existingMedia instanceof PodcastMedia) {
            $this->delete($existingMedia, $fs);
            $episode->setMedia(null);
        }

        $ext = pathinfo($originalPath, PATHINFO_EXTENSION);
        $path = $podcast->getId() . '/' . $episode->getId() . '.' . $ext;

        $podcastMedia = new PodcastMedia($storageLocation);
        $podcastMedia->setPath($path);
        $podcastMedia->setOriginalName(basename($originalPath));

        // Load metadata from local file while it's available.
        $podcastMedia->setLength($metadata->getDuration());
        $podcastMedia->setMimeType($metadata->getMimeType());

        // Upload local file remotely.
        $fs->uploadAndDeleteOriginal($uploadPath, $path);

        $podcastMedia->setEpisode($episode);
        $this->em->persist($podcastMedia);

        $episode->setMedia($podcastMedia);

        $artwork = $metadata->getArtwork();
        if (!empty($artwork) && 0 === $episode->getArtUpdatedAt()) {
            $this->episodeRepo->writeEpisodeArt(
                $episode,
                $artwork
            );
        }

        $this->em->persist($episode);
        $this->em->flush();
    }

    public function delete(
        PodcastMedia $media,
        ?ExtendedFilesystemInterface $fs = null
    ): void {
        $fs ??= $media->getStorageLocation()->getFilesystem();

        try {
            $fs->delete($media->getPath());
        } catch (UnableToDeleteFile) {
        }

        $this->em->remove($media);
        $this->em->flush();
    }
}
