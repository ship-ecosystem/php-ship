<?php

declare(strict_types=1);

namespace Ship\Services;

final class ServiceImage
{
    /**
     * A project-owned image inherits the upstream entry point, volumes and ports.
     *
     * @return array{build: array{context: string, dockerfile_inline: string}}
     */
    public static function patched(string $base, string $upgrade, ?string $runtimeUser = null): array
    {
        $dockerfile = "FROM {$base}\n";
        if ($runtimeUser !== null) {
            $dockerfile .= "USER root\n";
        }
        $dockerfile .= "RUN {$upgrade}\n";
        if ($runtimeUser !== null) {
            $dockerfile .= "USER {$runtimeUser}\n";
        }

        return ['build' => [
            'context' => '.',
            'dockerfile_inline' => $dockerfile,
        ]];
    }
}
