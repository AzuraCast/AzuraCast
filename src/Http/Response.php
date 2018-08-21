<?php
namespace App\Http;

class Response extends \Slim\Http\Response
{
    /**
     * Stream the contents of a file directly through to the response.
     *
     * @param $file_path
     * @param null $file_name
     * @return self
     */
    public function renderFile($file_path, $file_name = null): self
    {
        set_time_limit(600);

        if ($file_name == null) {
            $file_name = basename($file_path);
        }

        $fh = fopen($file_path, 'rb');
        $stream = new \Slim\Http\Stream($fh);

        return $this
            ->withHeader('Pragma', 'public')
            ->withHeader('Expires', '0')
            ->withHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
            ->withHeader('Content-Type', mime_content_type($file_path))
            ->withHeader('Content-Length', filesize($file_path))
            ->withHeader('Content-Disposition', 'attachment; filename=' . $file_name)
            ->withBody($stream);
    }

    /**
     * Write a string of file data to the response as if it is a file for download.
     *
     * @param string $file_data The body of the file contents.
     * @param string $content_type The HTTP header content-type (i.e. text/csv)
     * @param null $file_name
     * @return self
     */
    public function renderStringAsFile($file_data, $content_type, $file_name = null): self
    {
        $response = $this
            ->withHeader('Pragma', 'public')
            ->withHeader('Expires', '0')
            ->withHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
            ->withHeader('Content-Type', $content_type);

        if ($file_name !== null) {
            $response = $response->withHeader('Content-Disposition', 'attachment; filename=' . $file_name);
        }

        return $response->write($file_data);
    }
}
