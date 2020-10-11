<?php

declare(strict_types=1);

namespace App\Part\Entity;

use App\Customer\Entity\OperandId;
use App\Manufacturer\Entity\ManufacturerView;
use App\Part\Enum\Unit;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Money\Money;
use function sprintf;

/**
 * @ORM\Entity(readOnly=true)
 * @ORM\Table(name="part_view")
 */
class PartView
{
    /**
     * Наценка на запчасти.
     */
    private const MARKUP = 1.15;

    /**
     * @ORM\Id()
     * @ORM\Column(type="part_id")
     */
    public PartId $id;

    /**
     * @ORM\Embedded(class=ManufacturerView::class)
     */
    public ManufacturerView $manufacturer;

    /**
     * @ORM\Column
     */
    public string $name;

    /**
     * @ORM\Column(type="part_number")
     */
    public PartNumber $number;

    /**
     * @ORM\Column(type="boolean")
     */
    public bool $isUniversal;

    /**
     * @ORM\Column(type="unit_enum")
     */
    public Unit $unit;

    /**
     * @ORM\Column(type="integer")
     */
    public int $quantity;

    /**
     * @ORM\Column(type="integer")
     */
    public int $ordered;

    /**
     * @ORM\Column(type="money")
     */
    public Money $price;

    /**
     * @ORM\Column(type="money")
     */
    public Money $discount;

    /**
     * @ORM\Column(type="money")
     */
    public Money $income;

    /**
     * @var PartId[]
     *
     * @ORM\Column(type="part_ids")
     */
    public array $analogs;

    /**
     * @ORM\Column(type="integer")
     */
    public int $orderFromQuantity;

    /**
     * @ORM\Column(type="integer")
     */
    public int $orderUpToQuantity;

    /**
     * @ORM\Column(type="integer")
     */
    public int $suppliesQuantity;

    /**
     * @ORM\Column()
     */
    private string $search;

    /**
     * @ORM\Column()
     */
    private string $cases;

    /**
     * @ORM\Column(type="json")
     */
    private array $supplies;

    public function __construct(
        PartId $id,
        ManufacturerView $manufacturer,
        string $name,
        PartNumber $number,
        bool $isUniversal,
        Unit $unit,
        int $quantity,
        int $ordered,
        Money $price,
        Money $discount,
        Money $income,
        array $analogs,
        int $orderFromQuantity,
        int $orderUpToQuantity,
        int $suppliesQuantity,
        string $search,
        string $cases,
        array $supplies
    ) {
        $this->id = $id;
        $this->manufacturer = $manufacturer;
        $this->name = $name;
        $this->number = $number;
        $this->isUniversal = $isUniversal;
        $this->unit = $unit;
        $this->quantity = $quantity;
        $this->ordered = $ordered;
        $this->price = $price;
        $this->discount = $discount;
        $this->income = $income;
        $this->analogs = $analogs;
        $this->orderFromQuantity = $orderFromQuantity;
        $this->orderUpToQuantity = $orderUpToQuantity;
        $this->suppliesQuantity = $suppliesQuantity;
        $this->search = $search;
        $this->cases = $cases;
        $this->supplies = $supplies;
    }

    public function toId(): PartId
    {
        return $this->id;
    }

    public function display(): string
    {
        return sprintf(
            '%s - %s (%s)',
            $this->manufacturer->name,
            $this->name,
            (string) $this->number,
        );
    }

    public function displayWithStock(): string
    {
        return sprintf(
            '%s - %s (%s) (Склад: %s %s., Резерв: %s %s.)',
            $this->manufacturer->name,
            $this->name,
            (string) $this->number,
            $this->quantity / 100,
            $this->unit->toShortLabel(),
            $this->ordered / 100,
            $this->unit->toShortLabel(),
        );
    }

    public function sellPrice(): Money
    {
        return $this->price->subtract($this->discount);
    }

    public function suggestPrice(): Money
    {
        $suggestPrice = $this->price;
        $incomePriceWithMarkup = $this->income->multiply(self::MARKUP);

        if ($incomePriceWithMarkup->greaterThan($suggestPrice)) {
            $suggestPrice = $incomePriceWithMarkup;
        }

        return $suggestPrice;
    }

    /**
     * @return SupplyView[]
     */
    public function supplies(): array
    {
        $supplies = [];

        foreach ($this->supplies as $supply) {
            $supplies[] = new SupplyView(
                $this->id,
                OperandId::fromString($supply['supplier_id']),
                $supply['quantity'],
                new DateTimeImmutable($supply['updatedAt']),
            );
        }

        return $supplies;
    }

    public function hasExpiredSupplies(): bool
    {
        foreach ($this->supplies as $supply) {
            if (new DateTimeImmutable($supply['updatedAt']) < new DateTimeImmutable('-1 week')) {
                return true;
            }
        }

        return false;
    }

    public function hasKeepingStock(): bool
    {
        return 0 !== $this->orderFromQuantity || 0 !== $this->orderUpToQuantity;
    }

    public static function sql(): string
    {
        return <<<'SQL'
            CREATE VIEW part_view AS
            SELECT part.id                                                                                          AS id,
                   part.name                                                                                        AS name,
                   part.number                                                                                      AS number,
                   part.universal                                                                                   AS is_universal,
                   part.unit                                                                                        AS unit,
                   COALESCE(stock.quantity, 0)                                                                      AS quantity,
                   COALESCE(ordered.quantity, 0)                                                                    AS ordered,
                   COALESCE(crosses.parts, '[]'::JSON)                                                              AS analogs,
                   m.name                                                                                           AS manufacturer_name,
                   m.id                                                                                             AS manufacturer_id,
                   m.localized_name                                                                                 AS manufacturer_localized_name,
                   pc.cases                                                                                         AS cases,
                   UPPER(concat_ws(' ', part.name, m.name, m.localized_name, pc.cases))                             AS search,
                   COALESCE(price.price_currency_code, 'RUB') || ' ' || COALESCE(price.price_amount, 0)             AS price,
                   COALESCE(discount.discount_currency_code, 'RUB') || ' ' || COALESCE(discount.discount_amount, 0) AS discount,
                   COALESCE(income.price_currency_code, 'RUB') || ' ' || COALESCE(income.price_amount, 0)           AS income,
                   COALESCE(part_required.order_from_quantity, 0)                                                   AS order_from_quantity,
                   COALESCE(part_required.order_up_to_quantity, 0)                                                  AS order_up_to_quantity,
                   COALESCE(supply.json, '[]'::JSON)                                                                AS supplies,
                   COALESCE(supply.quantity, 0)                                                                     AS supplies_quantity
            FROM part
                     JOIN manufacturer m ON part.manufacturer_id = m.id
                     LEFT JOIN (SELECT part_case.part_id, array_to_string(array_agg(vm.case_name), ' ') AS cases
                                FROM part_case
                                         LEFT JOIN vehicle_model vm ON vm.id = part_case.vehicle_id
                                GROUP BY part_case.part_id) pc ON pc.part_id = part.id
                     LEFT JOIN (SELECT row_number() OVER (PARTITION BY pra.part_id ORDER BY pra.id DESC) AS rownum,
                                       pra.*
                                FROM part_required_availability pra) part_required
                               ON part_required.part_id = part.id AND part_required.rownum = 1
                     LEFT JOIN (SELECT row_number() OVER (PARTITION BY pp.part_id ORDER BY pp.id DESC) AS rownum,
                                       pp.*
                                FROM part_price pp) price ON price.part_id = part.id AND price.rownum = 1
                     LEFT JOIN (SELECT row_number() OVER (PARTITION BY pd.part_id ORDER BY pd.id DESC) AS rownum,
                                       pd.*
                                FROM part_discount pd) discount ON discount.part_id = part.id AND discount.rownum = 1
                     LEFT JOIN (SELECT row_number() OVER (PARTITION BY income_part.part_id ORDER BY income_part.id DESC) AS rownum,
                                       income_part.*
                                FROM income_part) income ON income.part_id = part.id AND income.rownum = 1
                     LEFT JOIN (SELECT motion.part_id, SUM(motion.quantity) AS quantity FROM motion GROUP BY motion.part_id) stock
                               ON stock.part_id = part.id
                     LEFT JOIN (SELECT order_item_part.part_id, SUM(order_item_part.quantity) AS quantity
                                FROM order_item_part
                                         JOIN order_item ON order_item.id = order_item_part.id
                                         JOIN order_close ON order_item.order_id = order_close.order_id
                                GROUP BY order_item_part.part_id) AS ordered
                               ON ordered.part_id = part.id
                     LEFT JOIN (SELECT json_agg(
                                               json_build_object(
                                                   'supplier_id', sub.supplier_id, 
                                                   'quantity', sub.quantity,
                                                   'updatedAt', sub.updated_at
                                                   )
                                           )             AS json,
                                       sub.part_id,
                                       SUM(sub.quantity) AS quantity
                                FROM (SELECT part_supply.part_id,
                                             part_supply.supplier_id,
                                             SUM(part_supply.quantity) AS quantity,
                                             MAX(created_by.created_at) AS updated_at
                                      FROM part_supply
                                      LEFT JOIN created_by ON created_by.id = part_supply.id
                                      GROUP BY part_supply.part_id, part_supply.supplier_id
                                      HAVING SUM(part_supply.quantity) <> 0
                                     ) sub
                                GROUP BY sub.part_id) supply
                               ON supply.part_id = part.id
                     LEFT JOIN (SELECT pcp.part_id, json_agg(pcp2.part_id) FILTER ( WHERE pcp2.part_id IS NOT NULL ) AS parts
                                FROM part_cross_part pcp
                                         JOIN part_cross pc ON pcp.part_cross_id = pc.id
                                         LEFT JOIN part_cross_part pcp2
                                                   ON pcp2.part_cross_id = pc.id AND pcp2.part_id <> pcp.part_id
                                GROUP BY pcp.part_id) crosses ON crosses.part_id = part.id
            SQL;
    }
}
