<?php
namespace PVL\Service;

class YouTube extends \Google_Service_YouTube
{
    /**
     * Internal recursive function for getting YT playlist items.
     * Solves the problem of Google's API splitting playlist results into pages.
     *
     * @param $playlist_id
     * @param null $page_token
     * @return array All items from all pages.
     */
    public function getPlaylistItems($playlist_id, $page_token = null)
    {
        $data = $this->playlistItems->listPlaylistItems('id,contentDetails', array(
            'playlistId'    => $playlist_id,
            'maxResults'    => 50,
            'pageToken'     => $page_token,
        ));

        if ($data)
        {
            $video_ids = array();
            foreach((array)$data['items'] as $item)
                $video_ids[] = $item['contentDetails']['videoId'];

            $videos = $this->videos->listVideos('id,snippet,status', array(
                'id'        => implode(',', $video_ids),
            ));

            $items = (array)$videos['items'];

            if ($data['nextPageToken'])
                $items = array_merge($items, $this->getPlaylistItems($playlist_id, $data['nextPageToken']));

            return $items;
        }
        else
        {
            return array();
        }
    }

    /**
     * Return the appropriate thumbnail to use, given a collection of options.
     *
     * @param $thumbnails
     * @return null
     */
    public static function getThumbnail($thumbnails, $size = 'medium')
    {
        switch($size)
        {
            case 'large':
                if ($thumbnails['maxres'])
                    return $thumbnails['maxres']['url'];
                elseif ($thumbnails['standard'])
                    return $thumbnails['standard']['url'];
                elseif ($thumbnails['high'])
                    return $thumbnails['high']['url'];
            break;

            case 'small':
            case 'medium':
            default:
                if ($thumbnails['medium'])
                    return $thumbnails['medium']['url'];
                elseif ($thumbnails['maxres'])
                    return $thumbnails['maxres']['url'];
            break;
        }

        return NULL;
    }
}