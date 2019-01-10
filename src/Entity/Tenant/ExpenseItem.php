<?php

declare(strict_types=1);

namespace App\Entity\Tenant;

use App\Doctrine\ORM\Mapping\Traits\CreatedAt;
use App\Doctrine\ORM\Mapping\Traits\CreatedByRelation;
use App\Doctrine\ORM\Mapping\Traits\Identity;
use App\Entity\Landlord\User;
use Doctrine\ORM\Mapping as ORM;
use Money\Money;

/**
 * @ORM\Entity
 */
class ExpenseItem
{
    use Identity;
    use CreatedAt;
    use CreatedByRelation;

    /**
     * @var Expense
     *
     * @ORM\ManyToOne(targetEntity="Expense")
     */
    private $expense;

    /**
     * @var Money
     *
     * @ORM\Embedded(class="Money\Money")
     */
    private $amount;

    /**
     * @var string|null
     *
     * @ORM\Column(nullable=true)
     */
    private $description;

    public function __construct(Expense $expense, Money $amount, User $user, string $description = null)
    {
        $this->expense = $expense;
        $this->amount = $amount;
        $this->description = $description;
        $this->setCreatedBy($user);
    }

    public function getExpense(): Expense
    {
        return $this->expense;
    }

    public function getAmount(): Money
    {
        return $this->amount;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
}
