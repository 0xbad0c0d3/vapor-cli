<?php

namespace Laravel\VaporCli;

use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class CustomContainerBuilder
{
    /**
     * Build a docker image.
     *
     * @param  string  $path
     * @param  string  $project
     * @param  string  $environment
     * @param  string  $builder
     * @return void
     */
    public static function build(string $path, string $project, string $environment, string $builder)
    {
        Process::fromShellCommandline(
            sprintf('%s build %s.Dockerfile %s .',
                $builder,
                $environment,
                Str::slug($project).':'.$environment
            ),
            $path
        )->setTimeout(null)->mustRun(function ($type, $line) {
            Helpers::write($line);
        });
    }

    /**
     * Publish a docker image.
     *
     * @param  string  $path
     * @param  string  $project
     * @param  string  $environment
     * @param  string  $token
     * @param  string  $repoUri
     * @param  string  $tag
     * @param  string  $builder
     * @return void
     */
    public static function publish(
        string $path,
        string $project,
        string $environment,
        string $token,
        string $repoUri,
        string $tag,
        string $builder
    ) {
        Process::fromShellCommandline(
            sprintf('%s publish %s.Dockerfile %s %s %s',
                $builder,
                $environment,
                $token,
                $path,
                $repoUri.':'.$tag
            ),
            $path
        )->setTimeout(null)->mustRun();
    }
}
