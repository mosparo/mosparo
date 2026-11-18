<?php

namespace Mosparo\Security\Oidc\Factory;

use Drenso\OidcBundle\DependencyInjection\DrensoOidcExtension;
use Drenso\OidcBundle\EventListener\OidcEndSessionSubscriber;
use Mosparo\Security\Oidc\OidcManager;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AbstractFactory;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AuthenticatorFactoryInterface;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class OidcFactory extends AbstractFactory implements AuthenticatorFactoryInterface
{

    public function __construct()
    {
        // Remove unused options
        unset($this->options['use_forward']);
        unset($this->options['require_previous_session']);

        // Set extra options
        $this->addOption('user_identifier_property', 'sub');
        $this->addOption('enable_remember_me', false);
        $this->addOption('enable_end_session_listener', false);
        $this->addOption('use_logout_target_path', true);
        $this->addOption('enable_retrieve_user_info', true);
    }

	/**
	 * @inheritDoc
	 */
	public function getPriority(): int
	{
		return -10;
	}

	/**
	 * @inheritDoc
	 */
	public function getKey(): string
	{
        return 'mosparooidc';
	}

	/**
	 * @inheritDoc
	 */
	public function createAuthenticator(ContainerBuilder $container, string $firewallName, array $config, string $userProviderId): string|array
	{
        $clientKeys = OidcManager::getClientKeys();
        $clientServices = [];
        foreach ($clientKeys as $clientKey) {
            $authenticatorId = sprintf('%s.%s.%s', DrensoOidcExtension::AUTHENTICATOR_ID, $firewallName, $clientKey);
            $clientReference = new Reference(sprintf('%s.%s', DrensoOidcExtension::CLIENT_ID, $clientKey));

            $container
                ->setDefinition($authenticatorId, new ChildDefinition(DrensoOidcExtension::AUTHENTICATOR_ID))
                ->addArgument(new Reference('security.http_utils'))
                ->addArgument($clientReference)
                ->addArgument(new Reference(sprintf('%s.%s', DrensoOidcExtension::SESSION_STORAGE_ID, $clientKey)))
                ->addArgument(new Reference($userProviderId))
                ->addArgument(new Reference($this->createAuthenticationSuccessHandler($container, $firewallName, $config)))
                ->addArgument(new Reference($this->createAuthenticationFailureHandler($container, $firewallName, $config)))
                ->addArgument($config['check_path'])
                ->addArgument($config['login_path'])
                ->addArgument($config['user_identifier_property'])
                ->addArgument($config['enable_remember_me'])
                ->addArgument('%' . sprintf('env(not:default:oidc_default_enable_retrieve_user_info:%s_enable_retrieve_user_info)', $clientKey) . '%')
                ->addArgument('%' . sprintf('env(bool:default:oidc_default_enable_retrieve_user_info:%s_enable_retrieve_user_info)', $clientKey) . '%')
                ->addArgument('%' . sprintf('env(not:default:oidc_default_enable_retrieve_user_info:%s_enable_retrieve_user_info)', $clientKey) . '%')
            ;

            $clientServices[$clientKey] = new Reference($authenticatorId);

            $logoutListenerId = sprintf('security.logout.listener.default.%s', $firewallName);

            // Check if "logout" config is specified in the firewall and "enable_end_session_listener" is set to true
            if ($config['enable_end_session_listener'] && $container->hasDefinition($logoutListenerId)) {
                $endSessionListenerId = sprintf('%s.%s', DrensoOidcExtension::END_SESSION_LISTENER_ID, $firewallName);

                /** If "use_logout_target_path" is true (default) pass the target path to the {@see OidcEndSessionSubscriber} */
                $logoutTargetPath = $config['use_logout_target_path']
                    ? $container->getDefinition($logoutListenerId)->getArgument(1)
                    : null;

                $container
                    ->setDefinition($endSessionListenerId, new Definition(OidcEndSessionSubscriber::class))
                    ->addArgument($clientReference)
                    ->addArgument(new Reference('security.http_utils'))
                    ->addArgument($logoutTargetPath) // Set the configured logout target path (null or string)
                    ->addTag('kernel.event_subscriber', [
                        'dispatcher' => sprintf('security.event_dispatcher.%s', $firewallName),
                    ])
                ;
            }
        }

        $key = 'mosparo.security.oidc.authenticator';
        $childKey = sprintf('%s.%s', $key, 'default');

        $container
            ->setDefinition($childKey, new ChildDefinition($key))
            ->addArgument(new Reference('mosparo.security.oidc.manager'))
            ->addArgument(new Reference('router'))
            ->addArgument(new Reference('logger'))
            ->addArgument($clientServices)
        ;

        return $childKey;
	}
}
