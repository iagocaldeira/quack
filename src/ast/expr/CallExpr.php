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

class CallExpr extends Expr
{
    public $func;
    public $arguments;
    public $is_bang;

    public function __construct($func, $arguments, $is_bang)
    {
        $this->func = $func;
        $this->arguments = $arguments;
        $this->is_bang = $is_bang;
    }

    public function format(Parser $parser)
    {
        $source = $this->func->format($parser);

        if (sizeof($this->arguments) > 0) {
            $source .= '( ';
            $source .= implode('; ', array_map(function (Expr $arg) use ($parser) {
                return $arg->format($parser);
            }, $this->arguments));
            $source .= ' )';
        } else {
            $source .= $this->is_bang
                ? '!'
                : '()';
        }

        return $this->parenthesize($source);
    }

    public function injectScope(&$parent_scope)
    {
        $this->func->injectScope($parent_scope);

        foreach ($this->arguments as $arg) {
            $arg->injectScope($parent_scope);
        }
    }
}
