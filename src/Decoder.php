<?php

namespace mafsa;

use IntlChar;

/**
 * Class Decoder
 *
 * @package mafsa
 * @author  Dmitrii Emelyanov <gilberg.vrn@gmail.com>
 * @date    10/17/19 2:51 PM
 */
class Decoder
{
    const END_OF_WORD = 0x01;
    const END_OF_NODE = 0x02;

    /** @var int */
    public $fileVer;

    /** @var int */
    public $wordLen;

    /** @var int */
    public $charLen;

    /** @var int */
    public $ptrLen;

    /** @var MinTreeNode[] */
    public $nodeMap;

    /** @var MinTree */
    public $tree;

    /**
     * Decoder constructor.
     */
    public function __construct()
    {
    }

    public function ReadFrom($reader)
    {
        $data = '';
        while (!feof($reader)) {
            $data .= fread($reader, 8192);
        }

        $tree = MinTree::newMinTree();
        $this->decodeMinTree($tree, $data);

        return $tree;
    }

    protected function decodeMinTree(MinTree $tree, string $data)
    {
        if (strlen($data) < 4) {
            throw new \Exception('Not enough bytes');
        }

        $header = unpack('Cver/Cwlen/Cclen/Cplen', substr($data, 0, 4));
        $this->fileVer = $header['ver'];
        $this->wordLen = $header['wlen'];
        $this->charLen = $header['clen'];
        $this->ptrLen = $header['plen'];

        $this->nodeMap = [];
        $this->tree = $tree;

        $this->decodeEdge($data, $tree->Root, $this->wordLen);

        $this->doNumbers($tree->Root);
    }

    protected function decodeEdge(string $data, MinTreeNode $parent, int $offset)
    {
        $dataLen = strlen($data);
        for ($i = $offset; $i < $dataLen; $i += $this->wordLen) {
            // Break the word apart into the pieces we need
            $charBytes = substr($data, $i, $this->charLen);
            $flags = unpack('C', substr($data, $i + $this->charLen, 1))[1];
            $ptrBytes = substr($data, $i + $this->charLen + 1, $this->ptrLen);

            $final = ($flags & self::END_OF_WORD) === self::END_OF_WORD;
            $lastChild = ($flags & self::END_OF_NODE) === self::END_OF_NODE;

            $char = $this->decodeCharacter($charBytes);
            $ptr = $this->decodePointer($ptrBytes);

            // If this word/edge points to a node we haven't
            // seen before, add it to the node map
            if (!isset($this->nodeMap[$ptr])) {
                $this->nodeMap[$ptr] = new MinTreeNode([], $final);
            }

            $parent->Edges[$char] = $this->nodeMap[$ptr]; // Add edge to node

            // If there are edges to other nodes, decode them
            if ($ptr > 0) {
                $this->decodeEdge($data, $this->nodeMap[$ptr], $ptr * $this->wordLen);
            }

            // If this word represents the last outgoing edge
            // for this node, stop iterating the file at this level
            if ($lastChild) {
                break;
            }
        }
    }

    private function decodeCharacter(string $charBytes)
    {
        switch ($this->charLen) {
            case 1:
                return IntlChar::chr(unpack('C', $charBytes)[1]);
            case 2:
                return IntlChar::chr(unpack('n', $charBytes)[1]);
            case 4:
                return IntlChar::chr(unpack('N', $charBytes)[1]);
        }

        throw new \Exception('FUCK!');
    }

    private function decodePointer(string $ptrBytes)
    {
        switch ($this->ptrLen) {
            case 2:
                return unpack('n', $ptrBytes)[1];
            case 4:
                return unpack('N', $ptrBytes)[1];
            case 8:
                return unpack('J', $ptrBytes)[1];
        }

        throw new \Exception('FUCK!');
    }

    private function doNumbers(MinTreeNode $node)
    {
        if ($node->Number > 0) {
            return;
        }

        foreach ($node->Edges as $child) {
            $this->doNumbers($child);
            if ($child->Final) {
                $node->Number++;
            }
            $node->Number += $child->Number;
        }
    }
}