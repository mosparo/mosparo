<?php

declare(strict_types=1);

namespace Mosparo\Repository;

use Mosparo\Entity\User;
use Mosparo\Entity\WebauthnCredential;
use Doctrine\Persistence\ManagerRegistry;
use Mosparo\Enum\AuthSource;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;
use Webauthn\Bundle\Repository\DoctrineCredentialSourceRepository;
use Webauthn\CredentialRecord;

final class WebauthnCredentialRepository extends DoctrineCredentialSourceRepository
{
    protected RequestStack $requestStack;

    protected TranslatorInterface $translator;

    public function __construct(ManagerRegistry $registry, RequestStack $requestStack, TranslatorInterface $translator)
    {
        $this->requestStack = $requestStack;
        $this->translator = $translator;
        parent::__construct($registry, WebauthnCredential::class);
    }

    public function saveCredentialRecord(CredentialRecord $credentialRecord): void
    {
        if (!$credentialRecord instanceof WebauthnCredential) {
            $request = $this->requestStack->getCurrentRequest();
            if (!$request) {
                return;
            }

            $content = json_decode($request->getContent(), true);
            if (!empty($content['keyName'])) {
                $name = trim($content['keyName']);
            } else {
                $userAgent = $request->headers->get('User-Agent');
                $name = $userAgent;
            }

            if (!$name) {
                $name = 'Unknown Passkey';
            }

            $credentialRecord = new WebauthnCredential(
                mb_substr($name, 0, 255),
                $credentialRecord->publicKeyCredentialId,
                $credentialRecord->type,
                $credentialRecord->transports,
                $credentialRecord->attestationType,
                $credentialRecord->trustPath,
                $credentialRecord->aaguid,
                $credentialRecord->credentialPublicKey,
                $credentialRecord->userHandle,
                $credentialRecord->counter
            );

            // @TODO: Verify that there is no better option than this (event handler for example?)
            $userRepository = $this->getEntityManager()->getRepository(User::class);
            $user = $userRepository->findOneBy([
                'userHandle' => $credentialRecord->userHandle,
                'authSource' => AuthSource::LOCAL,
            ]);
            if ($user && $user->getAuthMethod() !== 'webauthn') {
                $user->setAuthMethod('webauthn');
                $user->setPassword(null);
            }

            $session = $request->getSession();
            $session->getFlashBag()->add(
                'success',
                $this->translator->trans(
                    'account.webauthn.manage.message.keySuccessfullyAdded',
                    [],
                    'mosparo'
                )
            );
        }
        parent::saveCredentialRecord($credentialRecord);
    }
}
