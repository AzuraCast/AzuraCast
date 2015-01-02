<?php

/**
 * DoctrineExtensions Paginate
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to kontakt@beberlei.de so I can send you a copy immediately.
 */

namespace DF\Doctrine\Paginate;

use Doctrine\ORM\Query\TreeWalkerAdapter,
    Doctrine\ORM\Query\AST\SelectStatement,
    Doctrine\ORM\Query\AST\SelectExpression,
    Doctrine\ORM\Query\AST\PathExpression,
    Doctrine\ORM\Query\AST\AggregateExpression;

class CountWalker extends TreeWalkerAdapter
{

    /**
     * Walks down a SelectStatement AST node, modifying it to retrieve a COUNT
     *
     * @param SelectStatement $AST
     * @return void
     */
    public function walkSelectStatement(SelectStatement $AST)
    {
        $parent = null;
        $parentName = null;
        
        foreach ($this->_getQueryComponents() AS $dqlAlias => $qComp)
        {
            // skip mixed data in query
            if (isset($qComp['resultVariable']))
            {
                continue;
            }
            if ($qComp['parent'] === null && $qComp['nestingLevel'] == 0)
            {
                $parent = $qComp;
                $parentName = $dqlAlias;
                break;
            }
        }
        
        $pathExpression = new PathExpression(
                        PathExpression::TYPE_STATE_FIELD | PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION, $parentName,
                        $parent['metadata']->getSingleIdentifierFieldName()
        );
        $pathExpression->type = PathExpression::TYPE_STATE_FIELD;
        
        $AST->selectClause->selectExpressions = array(
            new SelectExpression(
                    new AggregateExpression('count', $pathExpression, true), null
            )
        );

        // ORDER BY is not needed, only increases query execution through unnecessary sorting.
        $AST->orderByClause = null;

        // GROUP BY will break things, we are trying to get a count of all
        $AST->groupByClause = null;
    }

}