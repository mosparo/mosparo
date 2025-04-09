<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Mosparo\Doctrine\DatabaseConfigurationBuilder;

return static function (ContainerConfigurator $container) {
    if (($_ENV['DATABASE_MYSQL_SSL'] ?? false)) {
        $container
            ->parameters()
            ->set('dbal_options', DatabaseConfigurationBuilder::getDatabaseConfiguration());
        return;
    }

    // Define the parameter with an empty array. Otherwise, the parameter is not defined at all.
    $container
        ->parameters()
        ->set('dbal_options', []);
};