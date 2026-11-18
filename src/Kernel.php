<?php

namespace Mosparo;

use Mosparo\Security\Oidc\Factory\OidcFactory;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\SecurityBundle\DependencyInjection\SecurityExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    const MAJOR_VERSION = '1.6';

    const VERSION = '1.6.0';

    use MicroKernelTrait;

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        // Register our OIDC factory
        $extension = $container->getExtension('security');
        assert($extension instanceof SecurityExtension);
        $extension->addAuthenticatorFactory(new OidcFactory());
    }
}
