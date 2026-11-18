<?php

namespace Mosparo\Helper;

use Mosparo\Entity\User;
use Mosparo\Repository\ResetPasswordRequestRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Validator\Constraints\PasswordStrength;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordToken;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

class PasswordHelper
{
    const DEFAULT_MIN_LENGTH = 12;
    const DEFAULT_MIN_STRENGTH = PasswordStrength::STRENGTH_MEDIUM;

    protected ResetPasswordHelperInterface $resetPasswordHelper;

    protected ResetPasswordRequestRepository $resetPasswordRequestRepository;

    protected TranslatorInterface $translator;

    protected MailerInterface $mailer;

    protected MailHelper $mailHelper;

    protected ConfigHelper $configHelper;

    public function __construct(
        ResetPasswordHelperInterface $resetPasswordHelper,
        ResetPasswordRequestRepository $resetPasswordRequestRepository,
        TranslatorInterface $translator,
        MailerInterface $mailer,
        MailHelper $mailHelper,
        ConfigHelper $configHelper
    ) {
        $this->resetPasswordHelper = $resetPasswordHelper;
        $this->resetPasswordRequestRepository = $resetPasswordRequestRepository;
        $this->translator = $translator;
        $this->mailer = $mailer;
        $this->mailHelper = $mailHelper;
        $this->configHelper = $configHelper;
    }

    public function sendResetPasswordEmail(User $user, $force = false): ResetPasswordToken
    {
        // If forced, remove all existing requests for this user
        if ($force) {
            $resetRequest = $this->resetPasswordRequestRepository->findOneBy([
                'user' => $user
            ]);

            if ($resetRequest !== null) {
                $this->resetPasswordRequestRepository->removeResetPasswordRequest($resetRequest);
            }
        }

        // Create new request
        try {
            $resetToken = $this->resetPasswordHelper->generateResetToken($user);
        } catch (ResetPasswordExceptionInterface $e) {
            throw $e;
        }

        // Send the email to the user
        $email = (new TemplatedEmail())
            ->to($user->getEmail())
            ->subject($this->translator->trans('password.email.subject', [], 'mosparo'))
            ->htmlTemplate('non_http/email/password/email.html.twig')
            ->context([
                'resetToken' => $resetToken,
                'cssCode' => $this->mailHelper->getEmailCssCode(),
            ]);

        try {
            $this->mailer->send($email);
        } catch (TransportException $e) {
            // Remove the reset request again since the mail wasn't sent
            $this->resetPasswordHelper->removeResetRequest($resetToken->getToken());

            throw $e;
        }

        return $resetToken;
    }

    public function getMinimumLength(): int
    {
        return max(8, (int) $this->configHelper->getEnvironmentConfigValue('authentication_password_min_length', self::DEFAULT_MIN_LENGTH));
    }

    public function getMinimumStrength(): int
    {
        return (int) $this->configHelper->getEnvironmentConfigValue('authentication_password_min_strength', self::DEFAULT_MIN_STRENGTH);
    }

    public function blockCompromisedPasswords(): bool
    {
        return (bool) $this->configHelper->getEnvironmentConfigValue('authentication_password_block_compromised', false);
    }
}
