<?php

namespace mafsa;

/**
 * Class BuildTreeNode
 *
 * @package mafsa
 * @author  Dmitrii Emelyanov <gilberg.vrn@gmail.com>
 * @date    10/17/19 6:04 PM
 */
class BuildTreeNode
{
    /** @var BuildTreeNode[] */
    public $Edges;

    /** @var int */
    public $id;

    /** @var string */
    public $char;

    /** @var string */
    public $lastChildKey;

    /** @var bool */
    public $final;

    /** @var int */
    public $bytePos;

    public function __construct($Edges = [], $char = null, $id = 0)
    {
        $this->Edges = $Edges;
        $this->char = $char;
        $this->id = $id;
    }

    public function hasChildren()
    {
        return count($this->Edges) > 0;
    }

    public function hash()
    {
        $hash = $this->char;
        if ($this->final) {
            $hash .= '1';
        } else {
            $hash .= '0';
        }

        $tmp = []; //make([]string, 0, len(tn.Edges))
        foreach ($this->Edges as $char => $child) {
            $tmp[] = $char . $child->id;
        }
        sort($tmp);
        $hash .= join("_", $tmp);

        return $hash;
    }
}