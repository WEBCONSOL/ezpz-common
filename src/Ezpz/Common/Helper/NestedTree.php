<?php

namespace Ezpz\Common\Helper;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use WC\Utilities\Logger;
use WC\Utilities\PathUtil;

class NestedTree
{
    private $em = null;
    private $table = '';
    private $referenceId = 0;
    private $orderingPosition = '';
    private $parentId = 0;
    private $editId = 0;
    private $parentLft = 0;
    private $parentRgt = 0;
    private $nodeLft = 0;
    private $nodeRgt = 0;
    private $nodePath = '';

    private $exists = false;

    private $updateStatementList = '';
    private $insertFieldList = '';
    private $insertValueList = '';

    private $updateChildren = false;
    private $newNodeLevel = 0;
    private $newNodePath = '';
    private $pathChecksum = '';
    private $newNodeAlias = '';

    private $error = false;
    private $msg = '';
    private $limit = '';

    public function __construct(EntityManager $em, string $table)
    {
        $this->em = $em;
        $this->table = $table;
    }

    public function setLimit(string $limit) {$this->limit = $limit;}

    /**
     * @param int $id
     *
     * @return bool
     */
    public function delete(int $id): bool {

        $queries = array();
        //$queries[] = 'LOCK TABLE ' . $this->table . ' WRITE;';
        $queries[] = 'SELECT @myLeft := lft, @myRight := rgt, @myWidth := rgt - lft + 1 FROM ' . $this->table . ' WHERE id = ' . $id . ';';
        $queries[] = 'DELETE FROM ' . $this->table . ' WHERE lft BETWEEN @myLeft AND @myRight;';
        $queries[] = 'UPDATE ' . $this->table . ' SET rgt = rgt - @myWidth WHERE rgt > @myRight;';
        $queries[] = 'UPDATE ' . $this->table . ' SET lft = lft - @myWidth WHERE lft > @myRight;';
        //$queries[] = 'UNLOCK TABLES;';
        try {
            $this->em->getConnection()->executeQuery(implode("\n", $queries));
            return true;
        }
        catch (DBALException $e) {
            Logger::error($e->getMessage());
            return false;
        }
    }

    /**
     * @param int    $parentId
     * @param int    $editId
     * @param string $title
     * @param int    $referenceId
     * @param string $orderingPosition
     * @param array  $updateQuery
     * @param array  $insertFields
     * @param array  $insertValues
     */
    public function store(int $parentId, int $editId, string $title, int $referenceId, string $orderingPosition, array $updateQuery, array $insertFields, array $insertValues) {

        $this->parentId = $parentId;
        $this->editId = $editId;
        $this->referenceId = $referenceId;
        $this->orderingPosition = $orderingPosition?$orderingPosition:'after';
        $this->updateStatementList = $updateQuery;
        $this->insertFieldList = $insertFields;
        $this->insertValueList = $insertValues;
        $this->storeSetAlias($title);
        $this->storePreparePropertiesByParent();
        $this->checkExistence();

        if (!$this->exists) {

            $this->insertFieldList[] = 'lft';
            $this->insertValueList[] = '@myPosition+1';

            $this->insertFieldList[] = 'rgt';
            $this->insertValueList[] = '@myPosition+2';

            $this->insertFieldList[] = 'level';
            $this->insertValueList[] = $this->newNodeLevel;

            $this->insertFieldList[] = 'path';
            $this->insertValueList[] = $this->em->getConnection()->quote($this->newNodePath);
            $this->insertFieldList[] = 'path_checksum';
            $this->insertValueList[] = $this->em->getConnection()->quote($this->pathChecksum);

            $this->insertFieldList[] = 'alias';
            $this->insertValueList[] = $this->em->getConnection()->quote($this->newNodeAlias);

            $this->updateStatementList[] = 'level='.$this->em->getConnection()->quote($this->newNodeLevel);
            $this->updateStatementList[] = 'path='.$this->em->getConnection()->quote($this->newNodePath);
            $this->updateStatementList[] = 'path_checksum='.$this->em->getConnection()->quote($this->pathChecksum);
            $this->updateStatementList[] = 'alias='.$this->em->getConnection()->quote($this->newNodeAlias);

            $queries = $this->storeQueries();

            if (sizeof($queries) > 0) {
                try {
                    $this->em->getConnection()->executeQuery(implode("\n", $queries));
                }
                catch (DBALException $e) {
                    Logger::error($e->getMessage());
                }
            }
            if ($this->updateChildren) {
                $this->storeUpdateMovedNodeChildren();
            }
            $this->msg = 'Successfully process.';

            if (!$this->editId) {
                $this->editId = $this->em->getConnection()->lastInsertId();
            }
        }
        else {
            $this->error = true;
            $this->msg = 'Item already exists.';
        }
    }

    /**
     * @param int $nodeId
     * @param int $ignoreBranch
     * @param int $depth
     *
     * @return array
     */
    public function getBranch(int $nodeId, int $ignoreBranch=0, int $depth=1): array {
        try {
            return $this->em->getConnection()->executeQuery($this->branchQuery($nodeId, $ignoreBranch, $depth))->fetchAll(\PDO::FETCH_ASSOC);
        }
        catch (DBALException $e) {
            Logger::error($e->getMessage());
            return [];
        }
    }

    /**
     * @param int $nodeId
     * @param int $ignoreBranch
     * @param int $depth
     *
     * @return string
     */
    public function branchQuery(int $nodeId, int $ignoreBranch=0, int $depth=1): string {

        $query = 'SELECT node.*, (COUNT(parent.name) - (sub_tree.depth + 1)) AS depth
        FROM '.$this->table.' AS node,
                '.$this->table.' AS parent,
                '.$this->table.' AS sub_parent,
                (
                        SELECT node.*, (COUNT(parent.name) - 1) AS depth
                        FROM '.$this->table.' AS node,
                                '.$this->table.' AS parent
                        WHERE node.lft BETWEEN parent.lft AND parent.rgt
                                AND node.id = '.$nodeId.'
                        GROUP BY node.name
                        ORDER BY node.lft
                ) AS sub_tree
        WHERE node.lft BETWEEN parent.lft AND parent.rgt
                AND node.lft BETWEEN sub_parent.lft AND sub_parent.rgt
                AND sub_parent.id = sub_tree.id'.($ignoreBranch>0?' AND node.id!='.$ignoreBranch:'').'
        GROUP BY node.id
        '.($depth?'HAVING depth <= '.$depth:'').'
        ORDER BY node.lft'.($this->limit?' ' . $this->limit:'');

        return $query;
    }

    /**
     * @param int $ignoreBranch
     *
     * @return array
     */
    public function getTree(int $ignoreBranch=0): array {
        try {
            return $this->em->getConnection()->executeQuery($this->treeQuery($ignoreBranch))->fetchAll(\PDO::FETCH_ASSOC);
        }
        catch (DBALException $e) {
            Logger::error($e->getMessage());
            return [];
        }
    }

    /**
     * @param int $ignoreBranch
     *
     * @return string
     */
    public function treeQuery(int $ignoreBranch=0): string {

        if ($ignoreBranch) {
            $children = $this->getBranch($ignoreBranch, 0, 0);
            if ($children) {
                $arr = array();
                foreach ($children as $child) {
                    $arr[] = $child['id'];
                }
                $ignoreBranch = implode(',', $arr);
            }
        }

        $query = 'SELECT node.*,(COUNT(parent.id) - 1) AS depth 
            FROM '.$this->table.' AS node,'.$this->table.' AS parent 
            WHERE (node.lft BETWEEN parent.lft AND parent.rgt) 
            AND (node.rgt BETWEEN parent.lft AND parent.rgt) 
            '.($ignoreBranch?' AND node.id NOT IN('.$ignoreBranch.')':'').'
            GROUP BY node.id 
            ORDER BY node.lft'.($this->limit?' ' . $this->limit:'');

        return $query;
    }

    /**
     * @return bool
     */
    public function getError(): bool { return $this->error; }

    /**
     * @return string $msg
     */
    public function getMessage(): string { return $this->msg; }

    /**
     * @return int $editId
     */
    public function getEditId(): int {return $this->editId;}

    private function checkExistence() {
        try {
            $query = 'SELECT id FROM ' . $this->table . ' WHERE path_checksum="' . $this->pathChecksum . '" AND id!=' . ($this->editId ? $this->editId : 0);
            $row = $this->em->getConnection()->executeQuery($query)->fetch(\PDO::FETCH_ASSOC);
            $this->exists = !empty($row) && isset($row['id']);
        }
        catch (DBALException $e) {
            Logger::error($e->getMessage());
        }
    }

    /**
     * @param int $id
     *
     * @return array
     */
    private function getChildren(int $id): array {
        try {
            $query = 'SELECT id,path,alias,parent_id,lft,rgt,level FROM ' . $this->table . ' WHERE parent_id=' . $id . ' ORDER BY lft';
            return $this->em->getConnection()->executeQuery($query)->fetchAll(\PDO::FETCH_ASSOC);
        }
        catch (DBALException $e) {
            Logger::error($e->getMessage());
            return [];
        }
    }

    /**
     * @param int $id
     *
     * @return array
     */
    private function getNode(int $id): array
    {
        try {
            $query = 'SELECT id,path,alias,parent_id,lft,rgt,level FROM ' . $this->table . ' WHERE id='.$this->em->getConnection()->quote($id);
            $row = $this->em->getConnection()->executeQuery($query)->fetch(\PDO::FETCH_ASSOC);

            // Check for no $row returned
            if (empty($row))
            {
                return null;
            }

            // Do some simple calculations.
            $row['numChildren'] = (int) ($row['rgt'] - $row['lft'] - 1) / 2;
            $row['width'] = (int) $row['rgt'] - $row['lft'] + 1;

            return $row;
        }
        catch (DBALException $e) {
            Logger::error($e->getMessage());
            return [];
        }
    }

    /**
     * @return array|null
     */
    private function storeQueries() {

        try {
            // 1. add new node
            if (!$this->editId) {

                // 1.1. item
                if ($this->referenceId) {

                    // 1.1.1. before
                    if ($this->orderingPosition === "before") {
                        $rows = $this->getChildren($this->parentId);
                        $length = sizeof($rows);
                        if ($length) {
                            foreach ($rows as $i=>$row) {
                                if ($row['id'] == $this->referenceId) {
                                    if ($i===0) {
                                        return $this->storeAddNodeQueryAsArray('first');
                                    }
                                    else if ($i===$length - 1) {
                                        return $this->storeAddNodeQueryAsArray('last');
                                    }
                                    else {
                                        return $this->storeAddNodeQueryAsArray('after', $rows[$i-1]->id);
                                    }
                                    break;
                                }
                            }
                        }
                    }

                    // 1.1.2. after
                    else if ($this->orderingPosition === "after") {
                        return $this->storeAddNodeQueryAsArray('after', $this->referenceId);
                    }
                }

                // 1.2. first
                else if ($this->orderingPosition === 'first') {
                    $rows = $this->getChildren($this->parentId);
                    $length = sizeof($rows);
                    if ($length) {
                        return $this->storeAddNodeQueryAsArray('first');
                    }
                    else {
                        return $this->storeAddNodeQueryAsArray('last');
                    }
                }

                // 1.3. no-item or last
                else {
                    return $this->storeAddNodeQueryAsArray('last');
                }
            }

            // 2. update / move node
            else {

                $row = $this->getNode($this->editId);
                $this->nodeLft = $row['lft'];
                $this->nodeRgt = $row['rgt'];
                $this->nodePath = $row['path'];
                $this->updateChildren = (int)$this->parentId !== (int)$row['parent_id'];
                if ($this->referenceId) {
                    return $this->storeMoveNodeByReferenceNodeQueryAsArray();
                }
                else if ($this->orderingPosition === 'first') {
                    $rows = $this->getChildren($this->parentId);
                    $length = sizeof($rows);
                    if ($length) {
                        $row = $rows[0];
                        $this->referenceId = $row['id'];
                        $this->orderingPosition = 'before';
                        return $this->storeMoveNodeByReferenceNodeQueryAsArray();
                    }
                    else {
                        return $this->storeMoveNodeAsLastChildNodeQueryAsArray();
                    }
                }
                else if ($this->orderingPosition === 'last') {
                    return $this->storeMoveNodeAsLastChildNodeQueryAsArray();
                }
                else if (!$this->updateChildren && !$this->referenceId) {
                    return $this->storeUpdateNodeQueryAsArray();
                }
                else {
                    return $this->storeMoveNodeAsLastChildNodeQueryAsArray();
                }
            }
        }
        catch (DBALException $e) {
            Logger::error($e->getMessage());
        }

        return null;
    }

    private function storeUpdateMovedNodeChildren() {

        try {
            $row = $this->getNode($this->editId);

            $query = 'UPDATE ' . $this->table . ' SET '.
                'path=CONCAT("'.$row['path'].'/",alias),path_checksum=MD5(CONCAT("'.$row['path'].'/",alias)),level='.($this->newNodeLevel+1).' '.
                'WHERE path LIKE "'.$this->nodePath.'/%" AND id!='.$this->em->getConnection()->quote($this->editId);
            $this->em->getConnection()->executeQuery($query);
        }
        catch (DBALException $e) {
            Logger::error($e->getMessage());
        }
    }

    /**
     * @param string $title
     */
    private function storeSetAlias(string $title) {
        if ($this->parentId > 0) {
            $this->newNodeAlias = PathUtil::toSlug($title);
        }
    }

    private function storePreparePropertiesByParent() {
        if ($this->parentId > 0 && $this->newNodeAlias) {
            $row = $this->getNode($this->parentId);
            if (sizeof($row) > 0) {
                $this->parentLft = $row['lft'];
                $this->parentRgt = $row['rgt'];
                $this->newNodeLevel = (int)$row['level']+1;
                $this->newNodePath = $row['path'].($row['path']?'/':'').$this->newNodeAlias;
                $this->pathChecksum = md5($this->newNodePath);
            }
        }
    }

    /**
     * @return array $queries
     */
    private function storeUpdateNodeQueryAsArray() {
        $queries = array();
        $queries[] = 'UPDATE ' . $this->table . ' SET ' . implode(',', $this->updateStatementList) . ' WHERE id='.$this->editId . ';';
        return $queries;
    }

    /**
     * @return array|null
     */
    private function storeMoveNodeByReferenceNodeQueryAsArray() {

        $newPos = 0;
        $row = $this->getNode($this->referenceId);

        if (!empty($row)) {
            if ($this->orderingPosition === "before") {
                $newPos = $row['lft'];
            } else if ($this->orderingPosition === "after") {
                $newPos = $row['rgt'] + 1;
            }

            $oldRgt = $this->nodeRgt;
            $width = $this->nodeRgt - $this->nodeLft + 1;
            $distance = $newPos - $this->nodeLft;
            $tmpPos = $this->nodeLft;
            if ($distance < 0) {
                $distance -= $width;
                $tmpPos += $width;
            }

            $queries = array();

            // Lock the table for writing.
            //$queries[] = 'LOCK TABLE ' . $this->table . ' WRITE;';

            // create new space for subtree
            $queries[] = 'UPDATE ' . $this->table . ' SET lft = lft + ' . $width . ' WHERE lft >= ' . $newPos . ';';
            $queries[] = 'UPDATE ' . $this->table . ' SET rgt = rgt + ' . $width . ' WHERE rgt >= ' . $newPos . ';';

            // move subtree into new space
            $queries[] = 'UPDATE ' . $this->table . ' SET lft = lft + ' . $distance . ', rgt = rgt + ' . $distance . ' WHERE lft >= ' . $tmpPos . ' AND rgt < ' . $tmpPos . ' + ' . $width . ';';

            // remove old space vacated by subtree
            $queries[] = 'UPDATE ' . $this->table . ' SET lft = lft - ' . $width . ' WHERE lft > ' . $oldRgt . ';';
            $queries[] = 'UPDATE ' . $this->table . ' SET rgt = rgt - ' . $width . ' WHERE rgt > ' . $oldRgt . ';';

            // update other properties
            $queries[] = 'UPDATE ' . $this->table . ' SET ' . implode(',', $this->updateStatementList) . ' WHERE id=' . $this->editId . ';';

            // unlock
            //$queries[] = 'UNLOCK TABLES;';

            return $queries;
        }

        return null;
    }

    /**
     * @return array $queries
     */
    private function storeMoveNodeAsLastChildNodeQueryAsArray() {

        $queries = array();
        //$queries[] = 'LOCK TABLE '.$this->table.' WRITE;';

        // step 0: Initialize parameters.
        $queries[] = 'SELECT
            @node_id := '.$this->editId.',
            @node_pos_left := '.$this->nodeLft.',
            @node_pos_right := '.$this->nodeRgt.',
            @parent_id := '.$this->parentId.',
            @parent_pos_right := '.$this->parentRgt.';';

        $queries[] = 'SELECT
            @node_size := @node_pos_right - @node_pos_left + 1;';

        // step 1: temporary "remove" moving node
        $queries[] = 'UPDATE ' . $this->table . ' SET lft = 0-(lft), rgt = 0-(rgt) WHERE lft >= @node_pos_left AND rgt <= @node_pos_right;';

        // step 2: decrease left and/or right position values of currently 'lower' items (and parents)
        $queries[] = 'UPDATE ' . $this->table . ' SET lft = lft - @node_size WHERE lft > @node_pos_right;';
        $queries[] = 'UPDATE ' . $this->table . ' SET rgt = rgt - @node_size WHERE rgt > @node_pos_right;';

        // step 3: increase left and/or right position values of future 'lower' items (and parents)
        $queries[] = 'UPDATE ' . $this->table . ' SET lft = lft + @node_size WHERE lft >= IF(@parent_pos_right > @node_pos_right, @parent_pos_right - @node_size, @parent_pos_right);';
        $queries[] = 'UPDATE ' . $this->table . ' SET rgt = rgt + @node_size WHERE rgt >= IF(@parent_pos_right > @node_pos_right, @parent_pos_right - @node_size, @parent_pos_right);';

        // step 4: move node (ant it's subnodes) and update it's parent item id
        $queries[] = 'UPDATE ' . $this->table . ' SET ' .
            'lft = 0-(lft)+IF(@parent_pos_right > @node_pos_right, @parent_pos_right - @node_pos_right - 1, @parent_pos_right - @node_pos_right - 1 + @node_size),'.
            'rgt = 0-(rgt)+IF(@parent_pos_right > @node_pos_right, @parent_pos_right - @node_pos_right - 1, @parent_pos_right - @node_pos_right - 1 + @node_size) '.
            'WHERE lft <= 0-@node_pos_left AND rgt >= 0-@node_pos_right;';
        $queries[] = 'UPDATE ' . $this->table . ' SET parent_id = @parent_id WHERE id = @node_id;';

        // step 5: update other properties
        $queries[] = 'UPDATE ' . $this->table . ' SET ' . implode(',', $this->updateStatementList) . ' WHERE id='.$this->editId . ';';

        // unlock
        //$queries[] = 'UNLOCK TABLES;';

        return $queries;
    }

    /**
     * @param string $position
     * @param int    $nodeId
     *
     * @return array
     */
    private function storeAddNodeQueryAsArray(string $position, int $nodeId = 0) {

        $queries = array();

        //$queries[] = 'LOCK TABLE '.$this->table.' WRITE;';

        if ($nodeId) {
            $queries[] = 'SELECT @myPosition := rgt FROM ' . $this->table . ' WHERE id=' . $nodeId . ';';
            $queries[] = 'UPDATE '.$this->table.' SET lft = lft + 2 WHERE lft > @myPosition;';
            $queries[] = 'UPDATE '.$this->table.' SET rgt = rgt + 2 WHERE rgt > @myPosition;';
        }
        else if ($position === 'first') {
            $queries[] = 'SELECT @myPosition := lft FROM '.$this->table.' WHERE id='.$this->parentId.';';
            $queries[] = 'UPDATE '.$this->table.' SET lft = lft + 2 WHERE lft > @myPosition;';
            $queries[] = 'UPDATE '.$this->table.' SET rgt = rgt + 2 WHERE rgt > @myPosition;';
        }
        else if ($position === 'last') {
            $queries[] = 'SELECT @myPosition := (rgt-1) FROM '.$this->table.' WHERE id='.$this->parentId.';';
            $queries[] = 'UPDATE '.$this->table.' SET lft = lft + 2 WHERE lft > @myPosition;';
            $queries[] = 'UPDATE '.$this->table.' SET rgt = rgt + 2 WHERE rgt > @myPosition;';
        }

        $queries[] = 'INSERT INTO ' . $this->table . '(' . implode(',', $this->insertFieldList) . ') VALUES(' . implode(',', $this->insertValueList) . ');';

        //$queries[] = 'UNLOCK TABLES;';

        return $queries;
    }

}