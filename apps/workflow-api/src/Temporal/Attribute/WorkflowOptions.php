<?php

namespace App\Temporal\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class WorkflowOptions
{
    /**
     * @param string $idInDiContainer
     */
    public function __construct(public string $idInDiContainer)
    {}
}
