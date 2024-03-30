<?php

namespace Mosparo\Controller\Administration;

use Mosparo\Helper\ConfigHelper;
use Mosparo\Helper\InterfaceHelper;
use Mosparo\Helper\LocaleHelper;
use Mosparo\Helper\SecurityHelper;
use Mosparo\Util\IpUtil;
use Mosparo\Util\ProviderUtil;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/administration/security')]
class SecurityController extends AbstractController
{
    protected ConfigHelper $configHelper;

    protected TranslatorInterface $translator;

    protected LocaleHelper $localeHelper;

    protected InterfaceHelper $interfaceHelper;

    protected SecurityHelper $securityHelper;

    protected string $trustedProxies;

    protected string $clientIpAddress = '';

    public function __construct(
        ConfigHelper $configHelper,
        TranslatorInterface $translator,
        LocaleHelper $localeHelper,
        InterfaceHelper $interfaceHelper,
        SecurityHelper $securityHelper,
        string $trustedProxies
    ) {
        $this->configHelper = $configHelper;
        $this->translator = $translator;
        $this->localeHelper = $localeHelper;
        $this->interfaceHelper = $interfaceHelper;
        $this->securityHelper = $securityHelper;
        $this->trustedProxies = $trustedProxies;
    }

    #[Route('/', name: 'administration_security')]
    public function security(Request $request): Response
    {
        $this->clientIpAddress = $request->getClientIp();
        $modifyTrustedSettingsAllowed = $this->determineIfModifyingTrustedSettingsIsAllowed();

        $environmentConfig = $this->configHelper->readEnvironmentConfig();
        $config = [
            // Login throttling
            'loginThrottlingUiLimit' => $environmentConfig['login_throttling_ui_limit'] ?? 5,
            'loginThrottlingIpLimit' => $environmentConfig['login_throttling_ip_limit'] ?? 25,
            'loginThrottlingInterval' => $environmentConfig['login_throttling_interval_numeric'] ?? 5,

            // Reverse proxy
            'trustedProxies' => $this->prepareTrustedProxies($environmentConfig['trusted_proxies'] ?? ''),
            'trustedProxiesIncludeRemoteAddr' => $environmentConfig['trusted_proxies_include_remote_addr'] ?? false,
            'replaceForwardedForHeader' => $environmentConfig['replace_forwarded_for_header'] ?? '',
            'replaceForwardedProtoHeader' => $environmentConfig['replace_forwarded_proto_header'] ?? '',

            // Backend access
            'backendAccessIpAllowList' => implode("\n", IpUtil::convertToArray($environmentConfig['backend_access_ip_allow_list'] ?? '')),

            // API access
            'apiAccessIpAllowList' => implode("\n", IpUtil::convertToArray($environmentConfig['api_access_ip_allow_list'] ?? '')),

            // Cron job access
            'webCronJobAccessIpAllowList' => implode("\n", IpUtil::convertToArray($environmentConfig['web_cron_job_access_ip_allow_list'] ?? '')),
        ];

        $isWebCronJobActive = $this->configHelper->getEnvironmentConfigValue('webCronJobActive');
        $form = $this->createFormBuilder($config, ['translation_domain' => 'mosparo'])
            // Login throttling
            ->add('loginThrottlingUiLimit', NumberType::class, [
                'label' => 'administration.security.loginThrottling.form.uiLimit',
                'help' => 'administration.security.loginThrottling.form.uiLimitHelp',
                'attr' => ['class' => 'text-end'],
            ])
            ->add('loginThrottlingIpLimit', NumberType::class, [
                'label' => 'administration.security.loginThrottling.form.ipLimit',
                'help' => 'administration.security.loginThrottling.form.ipLimitHelp',
                'attr' => ['class' => 'text-end'],
            ])
            ->add('loginThrottlingInterval', NumberType::class, [
                'label' => 'administration.security.loginThrottling.form.interval',
                'help' => 'administration.security.loginThrottling.form.intervalHelp',
                'attr' => ['class' => 'text-end'],
            ])

            // Reverse proxy
            ->add('trustedProxies', CollectionType::class, [
                'label' => 'administration.security.reverseProxy.form.trustedProxies',
                'disabled' => !$modifyTrustedSettingsAllowed,
                'allow_add' => true,
                'allow_delete' => true,
                'delete_empty' => true,
                'help' => 'administration.security.reverseProxy.form.trustedProxiesHelp',
                'entry_type' => TextType::class,
                'required' => false,
            ])
            ->add('trustedProxiesIncludeRemoteAddr', CheckboxType::class, [
                'label' => 'administration.security.reverseProxy.form.trustedProxiesIncludeRemoteAddr',
                'help' => 'administration.security.reverseProxy.form.trustedProxiesIncludeRemoteAddrHelp',
                'disabled' => !$modifyTrustedSettingsAllowed,
                'required' => false,
            ])
            ->add('replaceForwardedForHeader', TextType::class, [
                'label' => 'administration.security.reverseProxy.form.replaceForwardedForHeader',
                'help' => 'administration.security.reverseProxy.form.replaceForwardedForHeaderHelp',
                'disabled' => !$modifyTrustedSettingsAllowed,
                'required' => false,
            ])
            ->add('replaceForwardedProtoHeader', TextType::class, [
                'label' => 'administration.security.reverseProxy.form.replaceForwardedProtoHeader',
                'help' => 'administration.security.reverseProxy.form.replaceForwardedProtoHeaderHelp',
                'disabled' => !$modifyTrustedSettingsAllowed,
                'required' => false,
            ])

            // Backend Access
            ->add('backendAccessIpAllowList', TextareaType::class, [
                'label' => 'administration.security.ipAllowList.form.ipAllowList',
                'required' => false,
                'help' => 'administration.security.ipAllowList.form.ipAllowListHelp',
                'attr' => ['class' => 'ip-address-field'],
                'constraints' => [
                    new Callback([$this, 'validateIpAllowListField']),
                    new Callback([$this, 'checkIpAllowListFieldForOwnIp']),
                ],
            ])

            // API Access
            ->add('apiAccessIpAllowList', TextareaType::class, [
                'label' => 'administration.security.ipAllowList.form.ipAllowList',
                'required' => false,
                'help' => 'administration.security.ipAllowList.form.ipAllowListHelp',
                'attr' => ['class' => 'ip-address-field'],
                'constraints' => [
                    new Callback([$this, 'validateIpAllowListField']),
                ],
            ])

            // Cron Job Access
            ->add('webCronJobAccessIpAllowList', TextareaType::class, [
                'label' => 'administration.security.ipAllowList.form.ipAllowList',
                'required' => false,
                'help' => 'administration.security.ipAllowList.form.ipAllowListHelp',
                'attr' => [
                    'class' => 'ip-address-field',
                    'disabled' => !$isWebCronJobActive,
                ],
                'constraints' => [
                    new Callback([$this, 'validateIpAllowListField']),
                ],
            ])

            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $configValues = [
                'login_throttling_ui_limit' => $form->get('loginThrottlingUiLimit')->getData(),
                'login_throttling_ip_limit' => $form->get('loginThrottlingIpLimit')->getData(),
                'login_throttling_interval' => sprintf('%d minutes', $form->get('loginThrottlingInterval')->getData()),
                'login_throttling_interval_numeric' => $form->get('loginThrottlingInterval')->getData(),

                'backend_access_ip_allow_list' => implode(',', IpUtil::convertToArray($form->get('backendAccessIpAllowList')->getData())),
                'api_access_ip_allow_list' => implode(',', IpUtil::convertToArray($form->get('apiAccessIpAllowList')->getData())),
            ];

            if ($modifyTrustedSettingsAllowed) {
                $configValues = array_merge($configValues, [
                    'trusted_proxies' => $this->buildTrustedProxiesString($form->get('trustedProxies')->getData(), $form->get('trustedProxiesIncludeRemoteAddr')->getData()),
                    'trusted_proxies_include_remote_addr' => $form->get('trustedProxiesIncludeRemoteAddr')->getData(),
                    'replace_forwarded_for_header' => $form->get('replaceForwardedForHeader')->getData(),
                    'replace_forwarded_proto_header' => $form->get('replaceForwardedProtoHeader')->getData(),
                ]);
            }

            if ($isWebCronJobActive) {
                $configValues['web_cron_job_access_ip_allow_list'] = implode(',', IpUtil::convertToArray($form->get('webCronJobAccessIpAllowList')->getData()));
            }

            $this->configHelper->writeEnvironmentConfig($configValues);

            $session = $request->getSession();
            $session->getFlashBag()->add(
                'success',
                $this->translator->trans(
                    'administration.security.message.savedSuccessfully',
                    [],
                    'mosparo'
                )
            );

            return $this->redirectToRoute('administration_security');
        }

        return $this->render('administration/security/security.html.twig', [
            'form' => $form->createView(),
            'providers' => ProviderUtil::getReverseProxyProviders(),
            'modifyTrustedSettingsAllowed' => $modifyTrustedSettingsAllowed,
            'clientIpAddress' => $this->clientIpAddress,
            'isWebCronJobActive' => $isWebCronJobActive,
        ]);
    }

    /**
     * We must ensure that the settings are only enabled if the `TRUSTED_PROXIES` setting is not overridden in the
     * .env.local file. For this reason, we ask for the resolved argument, which should be the default value
     * `127.0.0.1` or the with this interface configured value. But if the unresolved value in the $_ENV array is
     * the same as in the resolved argument, the setting is overridden in the .env.local file.
     *
     * @return bool
     */
    protected function determineIfModifyingTrustedSettingsIsAllowed(): bool
    {
        $resolvedTrustedProxies = $this->trustedProxies;
        $environmentalTrustedProxies = $_ENV['TRUSTED_PROXIES'] ?? '';

        if ($resolvedTrustedProxies === $environmentalTrustedProxies) {
            return false;
        }

        return true;
    }

    protected function prepareTrustedProxies($trustedProxiesString): array
    {
        $list = [];
        $values = explode(',', $trustedProxiesString);

        foreach ($values as $value) {
            if ($value === '127.0.0.1' || $value === 'REMOTE_ADDR') {
                continue;
            }

            $list[] = $value;
        }

        return $list;
    }

    protected function buildTrustedProxiesString(array $trustedProxies, bool $addRemoteAddr): string
    {
        $list = ['127.0.0.1'];

        foreach ($trustedProxies as $trustedProxy) {
            $list[] = $trustedProxy;
        }

        if ($addRemoteAddr) {
            $list[] = 'REMOTE_ADDR';
        }

        $list = array_unique($list);

        return implode(',', $list);
    }

    public function validateIpAllowListField($settings, ExecutionContextInterface $context)
    {
        if (!$context->getValue()) {
            return;
        }

        $items = IpUtil::convertToArray($context->getValue());
        foreach ($items as $item) {
            if (!IpUtil::isValid($item)) {
                $context
                    ->buildViolation('ipAllowList.itemInvalid', ['%item%' => $item])
                    ->atPath($context->getPropertyPath())
                    ->addViolation();
            }
        }
    }

    public function checkIpAllowListFieldForOwnIp($settings, ExecutionContextInterface $context)
    {
        // Checks if the client IP address is allowed by the allow list
        if ($this->clientIpAddress && !IpUtil::isIpAllowed($this->clientIpAddress, $context->getValue())) {
            $context
                ->buildViolation('administration.security.backendAccess.ownIpNotInAllowList')
                ->atPath($context->getPropertyPath())
                ->addViolation();
        }
    }
}
