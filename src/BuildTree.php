<?php

namespace mafsa;

/**
 * Class BuildTree
 *
 * @package mafsa
 * @author  Dmitrii Emelyanov <gilberg.vrn@gmail.com>
 * @date    10/17/19 6:03 PM
 */
class BuildTree
{
    /** @var BuildTreeNode */
    public $Root;

    /** @var int */
    public $idCounter;

    /** @var int */
    public $nodeCount;

    /** @var BuildTreeNode[] */
    public $register;

    /** @var string */
    public $previousWord;

    // Insert adds val to the tree and performs optimizations to minimize
// the number of nodes in the tree. The inserted val must be
// lexicographically equal to or higher than the last inserted val.
    public function Insert(string $val)
    {
        if ($val < $this->previousWord) {
            throw new \Exception("Insertions must be performed in lexicographical order");
        }

        $word = $val;

        // Establish prefix shared between this and the last word
        $commonPrefixLen = 0;
        $lim = mb_strlen($word);
        if (mb_strlen($this->previousWord) < $lim) {
            $lim = mb_strlen($this->previousWord);
        }
        for ($i = 0; $i < $lim; $i++) {
            if (mb_substr($word, $i, 1) != mb_substr($this->previousWord, $i, 1)) {
                break;
            }
            $commonPrefixLen++;
        }
        $this->previousWord = $word;
        $commonPrefix = mb_substr($word, 0, $commonPrefixLen);

        // Traverse the tree up to the differing part (suffix)
        $lastState = $this->Traverse($commonPrefix);

        // Perform optimization steps
        if ($lastState->hasChildren()) {
            $this->replaceOrRegister($lastState);
        }

        // Add the differing part (suffix) to the tree
        $currentSuffix = mb_substr($word, $commonPrefixLen);
        $this->addSuffix($lastState, $currentSuffix);
    }

// Finish completes the optimizations on a tree. You must call Finish
// at least once, like immediately after all entries have been inserted.
    public function Finish()
    {
        $this->replaceOrRegister($this->Root);
    }

// addSuffix adds a sequence of characters to the tree starting at lastState.
    public function addSuffix(BuildTreeNode $lastState, $suffix)
    {
        $node = $lastState;
        $suffixLen = mb_strlen($suffix);
        for ($i = 0; $i < $suffixLen; $i++) {
            $char = mb_substr($suffix, $i, 1);
            $newNode = new BuildTreeNode([], $char, $this->idCounter);
            $node->Edges[$char] = $newNode;
            $node->lastChildKey = $char;
            $node = $newNode;
            $this->idCounter++;
            $this->nodeCount++;
        }
        $node->final = true;
    }

// replaceOrRegister minimizes the number of nodes in the tree
// starting with leaf nodes below state.
    public function replaceOrRegister(BuildTreeNode $state)
    {
        $child = $state->Edges[$state->lastChildKey];

        if ($child->hasChildren()) {
            $this->replaceOrRegister($child);
        }
        // If there exists a state q in the tree such that
        // it is in the register and equivalent
        // to (duplicate of) the child:
        // 	1) Set the state's lastChildKey to q
        //	2) delete child
        // Otherwise, add child to the register.
        // (Deleting the child is implicitly garbage-collected.)
        $childHash = $child->hash();
        if (isset($this->register[$childHash])) {
            $equiv = $this->register[$childHash];
            $state->Edges[$equiv->char] = $equiv;
            $this->nodeCount--;
        } else {
            $this->register[$childHash] = $child;
        }
    }

// Save encodes the MA-FSA into a binary format and writes it to a file.
// The tree can later be restored by calling the package's Load function.
    public function Save(string $filename)
    {
        $file = fopen($filename, 'w+');
//	defer file.Close()

        $data = $this->MarshalBinary();

        fwrite($file, $data);
        fclose($file);
    }

// String returns a roughly human-readable string representing
// the basic structure of the tree. For debugging only. Do not
// use with very large trees.
    public function String()
    {
        $str = "";
        return $this->recursiveString($this->Root, $str, 0);
    }

// recursiveString travels every node starting at node and builds the
// string representation of the tree, returning it. The level is how many
// nodes deep into the tree this iteration is starting at.
    public function recursiveString(BuildTreeNode $node, string &$str = '', int $level = 0)
    {
        $keys = array_keys($node->Edges);
        sort($keys);
        foreach ($keys as $char) {
            $child = $node->Edges[$char];
            $str .= sprintf("%s%s\n", str_repeat(" ", $level), $child->char);
            $str = $this->recursiveString($child, $str, $level + 1);
        }
        return $str;
    }

// Contains returns true if word is found in the tree, false otherwise.
    public function Contains(string $word)
    {
        $result = $this->Traverse($word);
        return $result != null && $result->final;
    }

// Traverse follows nodes down the tree according to word and returns the
// ending node if there was one or nil if there wasn't one. Note that
// this method alone does not indicate membership; some node may still be
// reached even if the word is not in the structure.
    /**
     * Traverse follows nodes down the tree according to word and returns the
     * ending node if there was one or nil if there wasn't one. Note that
     * this method alone does not indicate membership; some node may still be
     * reached even if the word is not in the structure.
     *
     * @param $word
     *
     * @return BuildTreeNode|null
     */
    public function Traverse($word)
    {
        $node = $this->Root;
        $wordLen = mb_strlen($word);
        for ($i = 0; $i < $wordLen; $i++) {
            $c = mb_substr($word, $i, 1);
            if (isset($node->Edges[$c])) {
                $node = $node->Edges[$c];
            } else {
                return null;
            }
        }
        return $node;
    }

// MarshalBinary encodes t into a binary format. It implements the functionality
// described by the encoding.BinaryMarhsaler interface. The associated
// encoding.BinaryUnmarshaler type is MinTree.
    public function MarshalBinary()
    {
        return (new Encoder())->Encode($this);
    }
}