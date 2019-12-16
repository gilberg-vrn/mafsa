<?php

namespace mafsa;

/**
 * Class mafsa
 *
 * @author Dmitrii Emelyanov <gilberg.vrn@gmail.com>
 * @date   10/17/19 2:32 PM
 */
class mafsa
{

    public static function new(): BuildTree
    {
        $t = new BuildTree();
        $t->register = [];
        $t->Root = new BuildTreeNode();
        $t->Root->Edges = [];
        
        return $t;
    }

    public static function load($filename): MinTree
    {
        $f = fopen($filename, 'r+');

        return  (new Decoder)->ReadFrom($f);
    }
}