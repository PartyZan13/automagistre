<?php

declare(strict_types=1);

namespace App\Income\View;

use App\Doctrine\ORM\Type\Identifier;
use App\Doctrine\Registry;
use App\Income\Entity\IncomePart;
use App\Income\Entity\IncomePartId;
use App\Infrastructure\Identifier\IdentifierFormatter;
use App\Infrastructure\Identifier\IdentifierFormatterInterface;

final class IncomePartFormatter implements IdentifierFormatterInterface
{
    private Registry $registry;

    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public static function support(): string
    {
        return IncomePartId::class;
    }

    /**
     * {@inheritdoc}
     */
    public function format(IdentifierFormatter $formatter, Identifier $identifier, string $format = null): string
    {
        /** @var IncomePart $incomePart */
        $incomePart = $this->registry->findBy(IncomePart::class, ['uuid' => $identifier]);

        return $formatter->format($incomePart->getIncome()->toId());
    }
}