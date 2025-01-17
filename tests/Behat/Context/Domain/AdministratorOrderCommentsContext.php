<?php

declare(strict_types=1);

namespace Tests\Brille24\OrderCommentsPlugin\Behat\Context\Domain;

use Behat\Behat\Context\Context;
use Sylius\Behat\Service\SharedStorageInterface;
use Sylius\Component\Core\Model\AdminUserInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Brille24\OrderCommentsPlugin\Domain\Model\Comment;
use Webmozart\Assert\Assert;

final class AdministratorOrderCommentsContext implements Context
{
    /** @var SharedStorageInterface */
    private $sharedStorage;

    /**
     * @param SharedStorageInterface $sharedStorage
     */
    public function __construct(SharedStorageInterface $sharedStorage)
    {
        $this->sharedStorage = $sharedStorage;
    }

    /**
     * @When I comment the order :order with :message with the notify customer checkbox enabled
     */
    public function iCommentTheOrderWithMessageAndCheckboxEnabled(OrderInterface $order, string $message): void
    {
        $this->commentOrder($order, $message, true);
    }

    /**
     * @When I comment the order :order with :message with the notify customer checkbox disabled
     */
    public function iCommentTheOrderWithMessageAndCheckboxDisabled(OrderInterface $order, string $message): void
    {
        $this->commentOrder($order, $message, false);
    }

    /**
     * @When I try to comment the order :order with an empty message
     */
    public function iTryToCommentTheOrderWith(OrderInterface $order): void
    {
        try {
            $this->iCommentTheOrderWithMessageAndCheckboxEnabled($order, '');
        } catch (\DomainException $exception) {
            $this->sharedStorage->set('exception', $exception);
        }
    }

    /**
     * @Then /^(this order) should have a comment with "([^"]+)" from this administrator$/
     */
    public function thisOrderShouldHaveCommentWithFromThisAdministrator(OrderInterface $order, string $message): void
    {
        /** @var AdminUserInterface $user */
        $user = $this->sharedStorage->get('administrator');
        /** @var Comment $comment */
        $comment = $this->sharedStorage->get('comment');

        if (
            $comment->message() !== $message ||
            $comment->order() !== $order ||
            $comment->authorEmail() != $user->getEmail() ||
            !$comment->createdAt() instanceof \DateTimeInterface ||
            empty($comment->recordedMessages())
        ) {
            throw new \InvalidArgumentException(
            sprintf(
                'There are no order comment with the "%s" message for the "%s" order from the "%s" customer',
                $message, $order->getNumber(), $user->getEmail()
            ));
        }
    }

    /**
     * Creates a new comment and sets it into the shared storage.
     * @param OrderInterface $order
     * @param string $message
     * @param bool $notifyCustomer
     */
    private function commentOrder(OrderInterface $order, string $message, bool $notifyCustomer): void
    {
        /** @var AdminUserInterface $user */
        $user = $this->sharedStorage->get('administrator');
        $comment = new Comment($order, $user->getEmail(), $message, $notifyCustomer);
        $comment->orderCommented();

        $this->sharedStorage->set('comment', $comment);
    }

    /**
     * @Then I should be notified that comment is invalid
     */
    public function iShouldBeNotifiedThatCommentIsInvalid(): void
    {
        Assert::isInstanceOf($this->sharedStorage->get('exception'), \DomainException::class);
    }

    /**
     * @Then this order should not have any comments
     */
    public function thisOrderShouldNotHaveAnyComments()
    {
        Assert::false($this->sharedStorage->has('comment'), 'At least one comment has been saved in shared storage, but none should');
    }
}
