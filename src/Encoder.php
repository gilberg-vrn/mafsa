<?php

namespace mafsa;

use IntlChar;

/**
 * Class Encoder
 *
 * @package mafsa
 * @author  Dmitrii Emelyanov <gilberg.vrn@gmail.com>
 * @date    10/17/19 6:58 PM
 */
class Encoder
{

    /** @var BuildTreeNode[] */
    public $queue;

    /** @var int */
    public $counter;

    public $charBytes = 1;

// Encode serializes a BuildTree t into a byte slice.
    public function Encode(BuildTree $t)
    {
        $this->queue = [];
        $this->counter = count($t->Root->Edges) + 1;

        // First "word" (fixed-length entry) is a null entry
        // that specifies the file format:
        // First byte indicates the flag scheme (basically a file format verison number)
        // Second byte is word length in bytes (at least 4)
        // Third byte is char length in bytes
        // Fourth byte is pointer length in bytes
        //   Note: Word length (the first byte)
        //   must be exactly Second byte + 1 (flags) + Fourth byte
        // Any leftover bytes in this first word are zero
        $data = pack('C*', 0x01, 0x06, 0x01, 0x04);
        for ($i = strlen($data); $i < ord($data[1]); $i++) {
            $data .= "\0";
        }

        $data = $this->encodeEdges($t->Root, $data);

        while (count($this->queue) > 0) {
            // Pop first item off the queue
            $top = array_shift($this->queue);

            // Recursively marshal child nodes
            $data = $this->encodeEdges($top, $data);
//            die();
        }

        return $data;
    }

// WriteTo encodes and saves the BuildTree to a io.Writer.
    public function WriteTo($wr, BuildTree $t)
    {
        $bs = $this->Encode($t);

        fwrite($wr, $bs);
    }

// encodeEdges encodes the edges going out of node into bytes which are appended
// to data. The modified byte slice is returned.
    public function encodeEdges(BuildTreeNode $node, &$data)
    {
        // We want deterministic output for testing purposes,
        // so we need to order the keys of the edges map.
        $edgeKeys = $this->sortEdgeKeys($node);
        for ($i = 0; $i < count($edgeKeys); $i++) {
            $child = $node->Edges[$edgeKeys[$i]];
            $word = pack($this->getCharPackTemplate(), IntlChar::ord($edgeKeys[$i]));

            $flags = 0;
            if ($child->final) {
                $flags |= 0x01; // end of word
            }
            if ($i == count($edgeKeys) - 1) {
                $flags |= 0x02; // end of node (last child outgoing from this node)
            }

            $word .= pack('C', $flags);

            // If bytePos is 0, we haven't encoded this edge yet
            if ($child->bytePos == 0) {
                if (count($child->Edges) > 0) {
                    $child->bytePos = $this->counter;
                    $this->counter += count($child->Edges);
                }
                $this->queue[] = $child;
            }

            $pointer = $child->bytePos;
            $pointerBytes = 0;
            switch (ord($data[3])) {
                case 2:
                    $pointerBytes = pack('n', $pointer);
                    break;
                case 4:
                    $pointerBytes = pack('N', $pointer);
                    break;
                case 8:
                    $pointerBytes = pack('J', $pointer);
                    break;
            }

            $word .= $pointerBytes;

            $data .= $word;
        }

        return $data;
    }

// sortEdgeKeys returns a sorted list of the keys
// of the map containing outgoing edges.
    public function sortEdgeKeys(BuildTreeNode $node)
    {
        $keys = array_keys($node->Edges);
        sort($keys);

        return $keys;
    }

    protected function getCharPackTemplate()
    {
        switch ($this->charBytes) {
            case 1:
                return 'C';
            case 2:
                return 'n';
            case 4:
                return 'N';
        }
    }

//type runeSlice []rune
//
//func (s runeSlice) Len() int           { return len(s) }
//func (s runeSlice) Less(i, j int) bool { return s[i] < s[j] }
//func (s runeSlice) Swap(i, j int)      { s[i], s[j] = s[j], s[i] }
}