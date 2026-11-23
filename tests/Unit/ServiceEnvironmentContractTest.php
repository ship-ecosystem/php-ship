<?php

declare(strict_types=1);

namespace Ship\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ship\Config\ShipConfig;
use Ship\Contracts\NeedsAppUrl;
use Ship\Contracts\ShipEnvironment;
use Ship\Docker\ComposeFileBuilder;
use Ship\Services\ServiceRegistry;
use Symfony\Component\Yaml\Yaml;

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

    public function test_database_admin_passwords_do_not_pass_from_the_env_file_to_the_app(): void
    {
        $builder = new ComposeFileBuilder(new ServiceRegistry(ServiceRegistry::defaults()));
        $config = new ShipConfig(
            phpVersion: '8.5',
            services: ['database' => 'mysql'],
            additionalServices: [['group' => 'database', 'service' => 'mongodb', 'name' => 'events']],
            processes: ['worker' => 'php worker.php'],
        );
        $services = Yaml::parse($builder->build($config, ShipEnvironment::Production))['services'];

        foreach (['app', 'worker'] as $name) {
            self::assertSame('', $services[$name]['environment']['DB_ROOT_PASSWORD']);
            self::assertSame('', $services[$name]['environment']['EVENTS_MONGODB_ROOT_PASSWORD']);
        }
        self::assertStringContainsString('${DB_ROOT_PASSWORD:?', $services['mysql']['environment']['MYSQL_ROOT_PASSWORD']);
        self::assertStringContainsString('${EVENTS_MONGODB_ROOT_PASSWORD:?', $services['mongodb-events']['environment']['MONGO_INITDB_ROOT_PASSWORD']);
    }
}
