<?php

namespace Mosparo\Controller\Administration;

use Mosparo\Helper\ConfigHelper;
use Mosparo\Helper\InterfaceHelper;
use Mosparo\Helper\LocaleHelper;
use Mosparo\Util\ProviderUtil;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/administration/security")
 */
class SecurityController extends AbstractController
{
    protected ConfigHelper $configHelper;

    protected TranslatorInterface $translator;

    protected LocaleHelper $localeHelper;

    protected InterfaceHelper $interfaceHelper;

    protected string $trustedProxies;

    public function __construct(ConfigHelper $configHelper, TranslatorInterface $translator, LocaleHelper $localeHelper, InterfaceHelper $interfaceHelper, string $trustedProxies)
    {
        $this->configHelper = $configHelper;
        $this->translator = $translator;
        $this->localeHelper = $localeHelper;
        $this->interfaceHelper = $interfaceHelper;
        $this->trustedProxies = $trustedProxies;
    }

    /**
     * @Route("/", name="administration_security")
     */
    public function security(Request $request): Response
    {
        $modifyTrustedSettingsAllowed = $this->determineIfModifyingTrustedSettingsIsAllowed();

        $environmentConfig = $this->configHelper->readEnvironmentConfig();
        $config = [
            'trustedProxies' => $this->prepareTrustedProxies($environmentConfig['trusted_proxies'] ?? ''),
            'trustedProxiesIncludeRemoteAddr' => $environmentConfig['trusted_proxies_include_remote_addr'] ?? false,
            'replaceForwardedForHeader' => $environmentConfig['replace_forwarded_for_header'] ?? '',
            'replaceForwardedProtoHeader' => $environmentConfig['replace_forwarded_proto_header'] ?? '',
        ];

        $form = $this->createFormBuilder($config, ['translation_domain' => 'mosparo'])
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
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($modifyTrustedSettingsAllowed) {
                $configValues = [
                    'trusted_proxies' => $this->buildTrustedProxiesString($form->get('trustedProxies')->getData(), $form->get('trustedProxiesIncludeRemoteAddr')->getData()),
                    'trusted_proxies_include_remote_addr' => $form->get('trustedProxiesIncludeRemoteAddr')->getData(),
                    'replace_forwarded_for_header' => $form->get('replaceForwardedForHeader')->getData(),
                    'replace_forwarded_proto_header' => $form->get('replaceForwardedProtoHeader')->getData(),
                ];

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
            }

            return $this->redirectToRoute('administration_security');
        }

        return $this->render('administration/security/security.html.twig', [
            'form' => $form->createView(),
            'providers' => ProviderUtil::getReverseProxyProviders(),
            'modifyTrustedSettingsAllowed' => $modifyTrustedSettingsAllowed
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
}
