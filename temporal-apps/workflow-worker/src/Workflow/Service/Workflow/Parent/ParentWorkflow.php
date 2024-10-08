<?php

/**
 * This file is part of Temporal package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Workflow\Service\Workflow\Parent;

use App\Workflow\Service\Workflow\Child\ChildWorkflowFacade;

/**
 * Demonstrates a child workflow. Requires a local instance of the Temporal server to be running.
 */
class ParentWorkflow implements ParentWorkflowInterface
{
    public function greet(string $name)
    {
        // This is a non blocking call that returns immediately.
        // Use yield ChildWorkflowFacade::greet(name) to call synchronously.
        $childGreet = ChildWorkflowFacade::greet($name);

        return "Hello $name from parent; " . yield $childGreet;
    }
}
