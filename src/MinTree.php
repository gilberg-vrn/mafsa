<?php

namespace mafsa;

/**
 * Class MinTree
 *
 * @package mafsa
 * @author  Dmitrii Emelyanov <gilberg.vrn@gmail.com>
 * @date    10/17/19 2:47 PM
 */
class MinTree
{

    /** @var MinTreeNode */
    public $Root;

    public static function newMinTree()
    {
        $t = new MinTree();
        $t->Root = new MinTreeNode();
        $t->Root->Edges = [];

        return $t;
    }

    public function Traverse($word)
    {
        $node = $this->Root;
        $wordLength = mb_strlen($word);
        for ($i = 0; $i < $wordLength; $i++) {
            $c = mb_substr($word, $i, 1);
            if (isset($node->Edges[$c])) {
                $node = $node->Edges[$c];
            } else {
                return false;
            }
        }

        return $node;
    }

    public function IndexedTraverse($word)
    {
        $index = 0;
        $node = $this->Root;
        $wordLength = mb_strlen($word);

        for ($i = 0; $i < $wordLength; $i++) {
            $c = mb_substr($word, $i, 1);
            if (isset($node->Edges[$c])) {
                foreach ($node->Edges as $char => $child) {
                    if ($char < $c) {
                        $index += $child->Number;

                        // If a previous sibling is also a final
                        // node, add 1, since that word appears before
                        // any word through this node. This line is
                        // a modification of the algorithm described
                        // in the paper.
                        if ($child->Final) {
                            $index++;
                        }
                    }
                }
                $node = $node->Edges[$c];
                if ($node->Final) {
                    $index++;
                }
            } else {
                return false;
            }
        }

        return $node;
    }

    public function recursiveString(MinTreeNode $node, string &$str = '', int $level = 0)
    {
        $keys = array_keys($node->Edges);
        sort($keys);

        foreach ($keys as $char) {
            $child = $node->Edges[$char];
            $str .= sprintf("%s%s %d\n", str_repeat(' ', $level), $char, $child->Number);
            $str = $this->recursiveString($child, $str, $level + 1);
        }

        return $str;
    }
}