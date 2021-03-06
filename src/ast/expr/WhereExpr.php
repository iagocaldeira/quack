<?php
/**
 * Quack Compiler and toolkit
 * Copyright (C) 2016 Marcelo Camargo <marcelocamargo@linuxmail.org> and
 * CONTRIBUTORS.
 *
 * This file is part of Quack.
 *
 * Quack is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Quack is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Quack.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace QuackCompiler\Ast\Expr;

use \QuackCompiler\Parser\Parser;

use \QuackCompiler\Scope\Kind;
use \QuackCompiler\Scope\ScopeError;

class WhereExpr extends Expr
{
    public $expr;
    public $clauses;
    private $scoperef;

    public function __construct(Expr $expr, $clauses)
    {
        $this->expr = $expr;
        $this->clauses = $clauses;
    }

    public function format(Parser $parser)
    {
        $first = true;
        $size = sizeof($this->clauses);
        $processed = 0;

        $source = $this->expr->format($parser);
        $source .= PHP_EOL;

        $parser->openScope();

        $source .= $parser->indent();
        $source .= 'where ';

        foreach ($this->clauses as $clause) {
            $key = &$clause[0];
            $value = &$clause[1];

            $processed++;

            if (!$first) {
                $source .= $parser->indent();
                $source .= '    ; ';
            } else {
                $first = false;
            }

            $source .= $key;
            $source .= ' :- ';
            $source .= $value->format($parser);

            if ($processed < $size) {
                $source .= PHP_EOL;
            }
        }

        $parser->closeScope();

        return $this->parenthesize($source);
    }

    public function injectScope(&$parent_scope)
    {
        $this->createScopeWithParent($parent_scope);
        $this->scoperef = $this->scope;

        // Bind where-symbols
        foreach ($this->clauses as $clause) {
            $key = &$clause[0];
            $value = &$clause[1];

            if ($this->scope->hasLocal($key)) {
                throw new ScopeError([
                    'message' => "Duplicated declaration of `{$key}' on where-clause"
                ]);
            }

            $value->injectScope($this->scope);
            $this->scope->insert($key, Kind::K_VARIABLE | Kind::K_VIRTUAL | Kind::K_INITIALIZED);
        }

        $this->expr->injectScope($this->scope);
    }

    public function getType()
    {
        // Infer type for each declaration in this scope
        foreach ($this->clauses as $clause) {
            $this->scoperef->setMeta('type', $clause[0], $clause[1]->getType());
        }
        // Retain type based on previous inference.
        return $this->expr->getType();
    }
}
