<?php

namespace App\Security\Voter;

use App\Entity\Customer;
use App\Entity\Offer;
use App\Entity\Task;
use App\Entity\TaskDraw;
use App\Entity\TaskImage;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Grants access to a record when the current user created it, or when the user
 * is an administrator.
 *
 * Index actions filter collections at the repository level; this voter guards
 * the single-record actions (show/edit/delete), which would otherwise be
 * reachable by ID by any authenticated user.
 */
class EntityOwnerVoter extends Voter
{
    public const VIEW = 'VIEW';
    public const EDIT = 'EDIT';
    public const DELETE = 'DELETE';

    private const ATTRIBUTES = [self::VIEW, self::EDIT, self::DELETE];

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!\in_array($attribute, self::ATTRIBUTES, true)) {
            return false;
        }

        return $subject instanceof Customer
            || $subject instanceof Offer
            || $subject instanceof Task
            || $subject instanceof TaskDraw
            || $subject instanceof TaskImage;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        if (\in_array('ROLE_ADMIN', $token->getRoleNames(), true)) {
            return true;
        }

        $owner = $this->resolveOwner($subject);

        return null !== $owner && $owner->getId() === $user->getId();
    }

    /**
     * Draws and images have no owner of their own — they inherit it from the
     * offer or task they belong to.
     */
    private function resolveOwner(object $subject): ?User
    {
        return match (true) {
            $subject instanceof Customer,
            $subject instanceof Offer,
            $subject instanceof Task => $subject->getCreatedBy(),

            $subject instanceof TaskDraw,
            $subject instanceof TaskImage => $subject->getOffer()?->getCreatedBy()
                ?? $subject->getTask()?->getCreatedBy(),

            default => null,
        };
    }
}
