<?php

declare(strict_types=1);

namespace App\Twig\Extension;

use App\Manager\SupplierManager;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @author Konstantin Grachev <me@grachevko.ru>
 */
class IncomeExtension extends AbstractExtension
{
    /**
     * @var SupplierManager
     */
    private $supplierManager;

    public function __construct(SupplierManager $supplierManager)
    {
        $this->supplierManager = $supplierManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('supplier_unpaid_income', [$this->supplierManager, 'unpaidIncome']),
        ];
    }
}
