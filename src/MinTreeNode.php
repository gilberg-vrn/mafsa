<?php

namespace mafsa;

/**
 * Class MinTreeNode
 *
 * @package mafsa
 * @author  Dmitrii Emelyanov <gilberg.vrn@gmail.com>
 * @date    10/17/19 2:56 PM
 */
class MinTreeNode
{
    /** @var MinTreeNode[] */
    public $Edges;

    /** @var bool */
    public $Final;

    /** @var int */
    public $Number;

    public function __construct($Edges = [], $Final = true, $Number = 0)
    {
        $this->Edges = $Edges;
        $this->Final = $Final;
        $this->Number = $Number;
    }
}