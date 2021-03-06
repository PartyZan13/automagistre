<?php

declare(strict_types=1);

namespace App\Car\Form\DTO;

use App\Car\Entity\CarId;
use App\Vehicle\Entity\Embedded\Equipment;
use App\Vehicle\Entity\VehicleId;
use App\Vehicle\Enum\BodyType;
use Symfony\Component\Validator\Constraints as Assert;

final class CarUpdate
{
    public CarId $carId;

    /**
     * @var Equipment
     *
     * @Assert\Valid
     */
    public $equipment;

    /**
     * @var VehicleId
     *
     * @Assert\NotBlank
     */
    public $vehicleId;

    /**
     * @var string|null
     *
     * @Assert\Length(max="17")
     */
    public $identifier;

    /**
     * @var int|null
     */
    public $year;

    /**
     * @var BodyType
     *
     * @Assert\Valid
     */
    public $caseType;

    /**
     * @var string|null
     */
    public $description;

    /**
     * @var string|null
     */
    public $gosnomer;

    public function __construct(
        CarId $carId,
        VehicleId $vehicleId,
        Equipment $equipment = null,
        string $identifier = null,
        ?int $year = null,
        BodyType $caseType = null,
        string $description = null,
        string $gosnomer = null
    ) {
        $this->carId = $carId;
        $this->vehicleId = $vehicleId;
        $this->equipment = $equipment ?? new Equipment();
        $this->identifier = $identifier;
        $this->year = $year;
        $this->caseType = $caseType ?? BodyType::unknown();
        $this->description = $description;
        $this->gosnomer = $gosnomer;
    }
}
