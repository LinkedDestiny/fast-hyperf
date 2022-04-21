<?php
declare(strict_types=1);

namespace LinkCloud\Fast\Hyperf\Command\CodeGen\Visitor;

use PhpParser\Node;

class ModelUpdateVisitor extends \Hyperf\Database\Commands\Ast\ModelUpdateVisitor
{

    protected function rewriteFillable(Node\Stmt\PropertyProperty $node): Node\Stmt\PropertyProperty
    {
        $items = [];
        foreach ($this->columns as $column) {
            if (in_array($column['column_name'], [
                'id',
                'create_at',
                'update_at',
                'enable'
            ])) {
                continue;
            }
            $items[] = new Node\Expr\ArrayItem(new Node\Scalar\String_($column['column_name']));
        }

        $node->default = new Node\Expr\Array_($items, [
            'kind' => Node\Expr\Array_::KIND_SHORT,
        ]);
        return $node;
    }

    protected function formatDatabaseType(string $type): ?string
    {
        return match ($type) {
            'tinyint', 'smallint', 'mediumint', 'int', 'datetime' => 'integer',
            'bigint', 'decimal', 'float', 'double', 'real' => 'string',
            'bool', 'boolean' => 'boolean',
            default => null,
        };
    }

    protected function formatPropertyType(string $type, ?string $cast): ?string
    {
        if (! isset($cast)) {
            $cast = $this->formatDatabaseType($type) ?? 'string';
        }

        return match ($cast) {
            'integer', 'date', 'datetime' => 'int',
            'json' => 'array',
            default => $cast,
        };
    }
}