<?php

/**
 * This file is part of Temporal package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Workflow\Service\Activity\MoneyTransfer;

use App\Temporal\Attribute\ActivityOptions;
use Temporal\Activity\ActivityInterface;

#[ActivityInterface(prefix: "MoneyTransfer.")]
#[ActivityOptions(idInDiContainer: "defaultActivityOptions")]
interface AccountActivityInterface
{
    /**
     * @param string $accountId
     * @param string $referenceId
     * @param int $amountCents
     *
     * @return int
     */
    public function deposit(string $accountId, string $referenceId, int $amountCents): int;

    /**
     * @param string $accountId
     * @param string $referenceId
     * @param int $amountCents
     *
     * @return int
     */
    public function withdraw(string $accountId, string $referenceId, int $amountCents): int;
}
