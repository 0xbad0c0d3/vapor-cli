<?php

namespace Laravel\VaporCli;

use Symfony\Component\Process\Process;

class Kaniko
{
    const DOCKER_CONFIG_FILE = '/kaniko/.docker/config.json';

    /**
     * @return array
     */
    private static function getCurrentDockerConfig(): array
    {
        if (file_exists(self::DOCKER_CONFIG_FILE)) {
            return (array)\json_decode(file_get_contents(self::DOCKER_CONFIG_FILE), true);
        }

        return [];
    }

    private static function updateRegistryAuthToken($repository, $token)
    {
        $current = self::getCurrentDockerConfig();
        $updated = array_replace_recursive($current, [
            'auths' => [
                $repository => [
                    'auth' => $token,
                ],
            ],
        ]);
        $written = file_put_contents(self::DOCKER_CONFIG_FILE, \json_encode($updated, JSON_PRETTY_PRINT));
        if (!$written) {
            $error = error_get_last();
            throw new \RuntimeException($error['message'] ?? 'Unknown error while writting: ' . self::DOCKER_CONFIG_FILE);
        }
    }

    public static function publish($path, $project, $environment, $token, $repoUri, $tag)
    {
        $repoHost = explode('/', $repoUri)[0];

        self::updateRegistryAuthToken($repoHost, $token);

        $kanikoExecutor = Manifest::getKanikoConfig($environment)['executor'] ?? '/kaniko/executor';

        Process::fromShellCommandline(
            sprintf($kanikoExecutor . ' -c %s -f %s.Dockerfile -d %s',
                $path,
                $environment,
                $repoUri . ':' . $tag
            ),
            $path,
            [
                'DOCKER_CONFIG' => dirname(self::DOCKER_CONFIG_FILE),
            ]
        )->setTimeout(null)->mustRun(function ($type, $line) {
            Helpers::write($line);
        });
    }
}
