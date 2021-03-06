<?php

declare(strict_types=1);

namespace App\Calendar\Form;

use App\Calendar\Entity\OrderInfo;
use App\Car\Entity\CarId;
use App\Customer\Entity\OperandId;
use App\Employee\Entity\EmployeeId;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Assert\Expression(
 *     "this.customerId != null or this.carId != null or this.description != null or this.customer != null",
 *     message="Нужно заполнить хотя бы одно из полей"
 * )
 */
final class OrderInfoDto
{
    /**
     * @var OperandId|null
     */
    public $customerId;

    /**
     * @var CarId|null
     */
    public $carId;

    /**
     * @var string|null
     */
    public $description;

    /**
     * @var EmployeeId|null
     */
    public $workerId;

    public function __construct(
        ?OperandId $customerId = null,
        ?CarId $carId = null,
        ?string $description = null,
        ?EmployeeId $workerId = null
    ) {
        $this->customerId = $customerId;
        $this->carId = $carId;
        $this->description = $description;
        $this->workerId = $workerId;
    }

    public static function fromOrderInfo(OrderInfo $orderInfo): self
    {
        return new self(
            $orderInfo->customerId,
            $orderInfo->carId,
            $orderInfo->description,
            $orderInfo->workerId
        );
    }
}
