<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\StereoTool;

use App\Controller\SingleActionInterface;
use App\Entity\Api\Status;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\StereoTool;
use App\Service\Flow;
use App\Utilities\File;
use FFI;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

final class PostAction implements SingleActionInterface
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $flowResponse = Flow::process($request, $response);
        if ($flowResponse instanceof ResponseInterface) {
            return $flowResponse;
        }

        $fsUtils = new Filesystem();

        $sourceTempPath = $flowResponse->getUploadedPath();

        $libraryPath = StereoTool::getLibraryPath();

        File::clearDirectoryContents($libraryPath);

        switch (strtolower(pathinfo($flowResponse->getClientFilename(), PATHINFO_EXTENSION))) {
            case 'zip':
                $destTempPath = sys_get_temp_dir() . '/uploads/new_stereo_tool';
                $fsUtils->remove($destTempPath);
                $fsUtils->mkdir($destTempPath);

                $process = new Process([
                    'unzip',
                    '-o',
                    $sourceTempPath,
                ]);
                $process->setWorkingDirectory($destTempPath);
                $process->setTimeout(600.0);

                $process->run();

                $flowResponse->delete();

                $pluginDirs = glob($destTempPath . '/Stereo_Tool_Generic_plugin_*') ?: [];
                if (count($pluginDirs) > 0) {
                    $pluginDir = $pluginDirs[0];
                    $versionStr = str_replace($destTempPath . '/Stereo_Tool_Generic_plugin_', '', $pluginDir);

                    $filesToCopy = glob($pluginDir . '/libStereoTool*.so') ?: [];

                    foreach ($filesToCopy as $fileToCopy) {
                        $fsUtils->rename(
                            $fileToCopy,
                            $libraryPath . '/' . basename($fileToCopy),
                            true
                        );
                    }

                    $fsUtils->dumpFile(
                        $libraryPath . '/' . StereoTool::VERSION_FILE,
                        substr($versionStr, 0, 2) . '.' . substr($versionStr, 2),
                    );
                } else {
                    throw new InvalidArgumentException('Uploaded file not recognized.');
                }

                $fsUtils->remove($destTempPath);
                break;

            case 'so':
                $binaryPath = $libraryPath . '/libStereoTool.so';
                $flowResponse->moveTo($binaryPath);

                $version = $this->getSharedLibraryVersion($binaryPath);
                if (null !== $version) {
                    $fsUtils->dumpFile(
                        $libraryPath . '/' . StereoTool::VERSION_FILE,
                        $version
                    );
                }
                break;

            default:
                $binaryPath = $libraryPath . '/stereo_tool';
                $flowResponse->moveTo($binaryPath);

                chmod($binaryPath, 0744);

                $version = $this->getLegacyVersion($binaryPath);
                if (null !== $version) {
                    $fsUtils->dumpFile(
                        $libraryPath . '/' . StereoTool::VERSION_FILE,
                        $version
                    );
                }
                break;
        }

        return $response->withJson(Status::success());
    }

    private function getLegacyVersion(string $path): ?string
    {
        $process = new Process([$path, '--help']);
        $process->setWorkingDirectory(dirname($path));
        $process->setTimeout(5.0);

        try {
            $process->run();
        } catch (RuntimeException) {
            return null;
        }

        if (!$process->isSuccessful()) {
            return null;
        }

        preg_match('/STEREO TOOL ([.\d]+) CONSOLE APPLICATION/i', $process->getErrorOutput(), $matches);
        if (!isset($matches[1])) {
            return null;
        }

        return $matches[1];
    }

    private function getSharedLibraryVersion(string $path): ?string
    {
        $ffi = FFI::cdef(
            <<<'EOH'
            extern int             stereoTool_GetSoftwareVersion  ();
            extern int             stereoTool_GetApiVersion       ();
            EOH,
            $path
        );

        /** @phpstan-ignore-next-line */
        $version = (int)call_user_func([
            $ffi,
            'stereoTool_GetSoftwareVersion',
        ]);

        if (0 === $version) {
            return null;
        }

        $majorVersion = (int)round($version / 1000, 2);
        return $majorVersion . '.' . (int)(($version - ($majorVersion * 1000)) / 10);
    }
}
