<?php

declare(strict_types=1);

namespace Ship\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ship\Contracts\NeedsAppUrl;
use Ship\Services\ServiceRegistry;

final class ServiceEnvironmentContractTest extends TestCase
{
    public function test_shared_services_expose_the_canonical_connection_variables(): void
    {
        $path = dirname(__DIR__, 2) . '/schema/service-environment.json';
        $contract = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        $registry = new ServiceRegistry(ServiceRegistry::defaults());

        foreach ($contract as $provider => $keys) {
            $service = $registry->get($provider === 'selenium' ? 'dusk' : $provider);
            $variables = array_keys($service->environmentVariables());
            if ($service instanceof NeedsAppUrl && isset($service->appUrlVariables()['app'])) {
                $variables[] = $service->appUrlVariables()['app'];
            }
            self::assertSame([], array_values(array_diff($keys, $variables)), $provider);
        }
    }
}
