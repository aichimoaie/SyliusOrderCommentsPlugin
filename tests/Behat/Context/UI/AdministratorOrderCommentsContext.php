<?php

declare(strict_types=1);

namespace Tests\Brille24\OrderCommentsPlugin\Behat\Context\UI;

use Behat\Behat\Context\Context;
use Sylius\Behat\Page\Admin\Order\ShowPageInterface;
use Sylius\Behat\Service\SharedStorageInterface;
use Sylius\Component\Core\Model\AdminUserInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Tests\Brille24\OrderCommentsPlugin\Behat\Element\OrderCommentsElementInterface;
use Tests\Brille24\OrderCommentsPlugin\Behat\Element\OrderCommentFormElementInterface;
use Webmozart\Assert\Assert;

final class AdministratorOrderCommentsContext implements Context
{
    /** @var SharedStorageInterface */
    private $sharedStorage;

    /** @var ShowPageInterface */
    private $orderPage;

    /** @var OrderCommentsElementInterface */
    private $orderCommentsElement;

    /** @var OrderCommentFormElementInterface */
    private $orderCommentFormElement;

    public function __construct(
        SharedStorageInterface $sharedStorage,
        ShowPageInterface $orderPage,
        OrderCommentsElementInterface $orderCommentsElement,
        OrderCommentFormElementInterface $orderCommentFormElement
    ) {
        $this->sharedStorage = $sharedStorage;
        $this->orderPage = $orderPage;
        $this->orderCommentsElement = $orderCommentsElement;
        $this->orderCommentFormElement = $orderCommentFormElement;
    }

    /**
     * @When I comment the order :order with :message with the notify customer checkbox enabled
     * @Given I have commented the order :order with :message with the notify customer checkbox enabled
     */
    public function iCommentTheOrderWithMessageAndCheckboxEnabled(OrderInterface $order, string $message): void
    {
        $this->orderPage->open(['id' => $order->getId()]);

        $this->orderCommentFormElement->enableCustomerNotified();
        $this->orderCommentFormElement->specifyMessage($message);
        $this->orderCommentFormElement->comment();
    }

    /**
     * @When I comment the order :order with :message with the notify customer checkbox disabled
     * @Given I have commented the order :order with :message with the notify customer checkbox disabled
     */
    public function iCommentTheOrderWithMessageAndCheckboxDisabled(OrderInterface $order, string $message): void
    {
        $this->orderPage->open(['id' => $order->getId()]);

        $this->orderCommentFormElement->disableCustomerNotified();
        $this->orderCommentFormElement->specifyMessage($message);
        $this->orderCommentFormElement->comment();
    }

    /**
     * @When I try to comment the order :order with an empty message
     */
    public function aCustomerTryToCommentsTheOrderWithEmptyMessage(OrderInterface $order): void
    {
        $this->orderPage->open(['id' => $order->getId()]);
        $this->orderCommentFormElement->enableCustomerNotified();
        $this->orderCommentFormElement->specifyMessage('');
        $this->orderCommentFormElement->comment();
    }

    /**
     * @Then this order should have a comment with :message from this administrator
     * @Then the first comment from the top should have the :message message
     */
    public function thisOrderShouldHaveACommentWithFromThisAdministrator(string $message): void
    {
        /** @var AdminUserInterface $user */
        $user = $this->sharedStorage->get('administrator');

        $comment = $this->orderCommentsElement->getFirstComment();

        Assert::notNull($comment);
        Assert::same($comment->find('css', '.text')->getText(), $message);
        Assert::same($comment->find('css', '.author')->getText(), $user->getEmail());
    }

    /**
     * @Then I should be notified that comment is invalid
     */
    public function thisOrderShouldNotHaveEmptyCommentFromThisCustomer(): void
    {
        $order = $this->sharedStorage->get('order');

        Assert::true($this->orderPage->isOpen(['id' => $order->getId()]));
    }

    /**
     * @Then the order :order should not have any comments
     * @Then /^(this order) should not have any comments$/
     */
    public function theOrderShouldNotHaveAnyComments(OrderInterface $order): void
    {
        $this->orderPage->open(['id' => $order->getId()]);

        Assert::same($this->orderCommentsElement->countComments(), 0, 'This order should not have any comment, but %s found.');
    }
}
