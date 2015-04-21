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
        $data = $this->playlistItems->listPlaylistItems('id,snippet,status,contentDetails', array(
            'playlistId'    => $playlist_id,
            'maxResults'    => 50,
            'pageToken'     => $page_token,
        ));

        if ($data)
        {
            $items = (array)$data['items'];

            if ($data['nextPageToken'])
                $items = array_merge($items, $this->getPlaylistItems($playlist_id, $data['nextPageToken']));

            return $items;
        }
        else
        {
            return array();
        }
    }
}