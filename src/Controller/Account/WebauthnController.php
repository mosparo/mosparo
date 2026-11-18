<?php

namespace Mosparo\Controller\Account;

use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Entity\WebauthnCredential;
use Mosparo\Enum\AuthSource;
use Mosparo\Helper\ConfigHelper;
use Mosparo\Repository\WebauthnCredentialRepository;
use Mosparo\Repository\WebauthnUserEntityRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Webauthn\Bundle\Repository\PublicKeyCredentialSourceRepositoryInterface;
use Webauthn\Bundle\Repository\PublicKeyCredentialUserEntityRepositoryInterface;

#[Route('/account/webauthn')]
class WebauthnController extends AbstractController
{
    protected PublicKeyCredentialUserEntityRepositoryInterface $keyCredentialUserEntityRepository;

    protected PublicKeyCredentialSourceRepositoryInterface $keyCredentialSourceRepository;

    protected ConfigHelper $configHelper;

    protected TranslatorInterface $translator;

    public function __construct(
        PublicKeyCredentialUserEntityRepositoryInterface $keyCredentialUserEntityRepository,
        PublicKeyCredentialSourceRepositoryInterface $keyCredentialSourceRepository,
        ConfigHelper $configHelper,
        TranslatorInterface $translator
    ) {
        $this->keyCredentialUserEntityRepository = $keyCredentialUserEntityRepository;
        $this->keyCredentialSourceRepository = $keyCredentialSourceRepository;
        $this->configHelper = $configHelper;
        $this->translator = $translator;
    }


    #[Route('/list', name: 'account_webauthn_list')]
    public function list(): Response
    {
        /** @var \Mosparo\Entity\User $user */
        $user = $this->getUser();

        if ($user->getAuthSource() !== AuthSource::LOCAL) {
            return $this->redirectToRoute('account_overview');
        }

        $credentials = [];
        if ($user->getUserHandle()) {
            $userEntity = $this->keyCredentialUserEntityRepository->findOneByUserHandle($user->getUserHandle());
            if ($userEntity === null) {
                throw new AccessDeniedHttpException();
            }

            $credentials = $this->keyCredentialSourceRepository->findAllForUserEntity($userEntity);
        }

        return $this->render('account/webauthn/list.html.twig', [
            'credentials' => $credentials,
            'isWebauthnAvailable' => ($this->configHelper->getEnvironmentConfigValue('mosparo_webauthn_rp_id') !== false),
        ]);
    }


    #[Route('/{id}/delete', name: 'account_webauthn_key_delete')]
    public function delete(
        Request $request,
        WebauthnCredential $credential,
        EntityManagerInterface $entityManager,
        WebauthnUserEntityRepository $webauthnUserEntityRepository,
        WebauthnCredentialRepository $webauthnCredentialRepository
    ): Response {
        $userHandle = $this->getUser()->getUserHandle();
        if ($userHandle === null || !hash_equals($userHandle, $credential->userHandle)) {
            throw $this->createNotFoundException();
        }

        $userEntity = $webauthnUserEntityRepository->findOneByUserHandle($this->getUser()->getUserHandle());
        if ($userEntity === null) {
            throw new AccessDeniedHttpException();
        }

        $credentials = $webauthnCredentialRepository->findAllForUserEntity($userEntity);
        $count = count($credentials);

        if ($count === 0) {
            return $this->redirectToRoute('account_webauthn_list');
        }

        if ($request->request->has('delete-token')) {
            $submittedToken = $request->request->get('delete-token');

            if ($this->isCsrfTokenValid('delete-key', $submittedToken)) {
                $entityManager->remove($credential);

                $forceNewPassword = false;
                if ($count === 1) {
                    $this->getUser()->setAuthMethod('password');
                    $forceNewPassword = true;
                }

                $entityManager->flush();

                $session = $request->getSession();
                $session->getFlashBag()->add(
                    'success',
                    $this->translator->trans(
                        'account.webauthn.manage.message.keySuccessfullyDeleted',
                        ['%keyName%' => $credential->getName()],
                        'mosparo'
                    )
                );

                if ($forceNewPassword) {
                    return $this->redirectToRoute('account_change_password');
                }

                return $this->redirectToRoute('account_webauthn_list');
            }
        }

        $environmentConfig = $this->configHelper->readEnvironmentConfig();
        return $this->render('account/webauthn/delete.html.twig', [
            'credential' => $credential,
            'hasOnlyOnce' => ($count === 1),
            'enforcePasskey' => ($environmentConfig['authentication_enforce_passkey_authentication'] ?? false),
        ]);
    }
}
