<?php

namespace App\Tests\Unit\Security;

use App\Entity\Customer;
use App\Entity\Offer;
use App\Entity\TaskImage;
use App\Entity\User;
use App\Security\Voter\EntityOwnerVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class EntityOwnerVoterTest extends TestCase
{
    private EntityOwnerVoter $voter;

    protected function setUp(): void
    {
        $this->voter = new EntityOwnerVoter();
    }

    public function testOwnerMayEditOwnCustomer(): void
    {
        $owner = $this->makeUser(1);
        $customer = (new Customer())->setCreatedBy($owner);

        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($this->tokenFor($owner), $customer, [EntityOwnerVoter::EDIT]),
        );
    }

    public function testStrangerMayNotEditSomeoneElsesCustomer(): void
    {
        $customer = (new Customer())->setCreatedBy($this->makeUser(1));
        $stranger = $this->makeUser(2);

        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($this->tokenFor($stranger), $customer, [EntityOwnerVoter::EDIT]),
        );
    }

    public function testAdminMayEditAnyRecord(): void
    {
        $customer = (new Customer())->setCreatedBy($this->makeUser(1));
        $admin = $this->makeUser(99);

        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($this->tokenFor($admin, ['ROLE_USER', 'ROLE_ADMIN']), $customer, [EntityOwnerVoter::EDIT]),
        );
    }

    public function testAnonymousIsDenied(): void
    {
        $customer = (new Customer())->setCreatedBy($this->makeUser(1));

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);
        $token->method('getRoleNames')->willReturn([]);

        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($token, $customer, [EntityOwnerVoter::VIEW]),
        );
    }

    public function testImageInheritsOwnershipFromItsOffer(): void
    {
        $owner = $this->makeUser(1);
        $offer = (new Offer())->setCreatedBy($owner);

        $image = new TaskImage();
        $image->setOffer($offer);

        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($this->tokenFor($owner), $image, [EntityOwnerVoter::DELETE]),
        );
        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($this->tokenFor($this->makeUser(2)), $image, [EntityOwnerVoter::DELETE]),
        );
    }

    public function testUnsupportedSubjectAbstains(): void
    {
        self::assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($this->tokenFor($this->makeUser(1)), new \stdClass(), [EntityOwnerVoter::VIEW]),
        );
    }

    public function testUnsupportedAttributeAbstains(): void
    {
        $customer = (new Customer())->setCreatedBy($this->makeUser(1));

        self::assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($this->tokenFor($this->makeUser(1)), $customer, ['PUBLISH']),
        );
    }

    private function makeUser(int $id): User
    {
        $user = new User();

        // The identifier is normally assigned by Doctrine.
        $reflection = new \ReflectionProperty(User::class, 'id');
        $reflection->setValue($user, $id);

        return $user;
    }

    /**
     * @param string[] $roles
     */
    private function tokenFor(User $user, array $roles = ['ROLE_USER']): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $token->method('getRoleNames')->willReturn($roles);

        return $token;
    }
}
