<?php

namespace Mosparo\Security\Voter;

use DateInterval;
use DateTime;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class RecentlyAuthenticatedVoter extends Voter
{
    const RECENTLY_AUTHENTICATED = 'RECENTLY_AUTHENTICATED';

    const SESSION_KEY = 'recentlyAuthenticatedTtimestamp';

    protected RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * @inheritDoc
     */
    protected function supports(string $attribute, mixed $subject): bool
    {
        if ($attribute !== self::RECENTLY_AUTHENTICATED) {
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $request = $this->requestStack->getMainRequest();
        if (!$request) {
            return false;
        }

        $session = $request->getSession();

        $authenticationTimestamp = $session->get(self::SESSION_KEY);
        if (!($authenticationTimestamp instanceof DateTime)) {
            return false;
        }

        $diff = (new DateTime())->getTimestamp() - $authenticationTimestamp->getTimestamp();
        if ($diff > 300) {
            return false;
        }

        return true;
    }
}
