<?php
declare(strict_types=1);

namespace LinkCloud\Fast\Hyperf\Command\CodeGen\Visitor;

use LinkCloud\Fast\Hyperf\Annotations\Api\Property\ApiAttribute;
use LinkCloud\Fast\Hyperf\Annotations\Api\Property\ApiProperty;
use LinkCloud\Fast\Hyperf\Annotations\ArrayType;
use LinkCloud\Fast\Hyperf\Annotations\EnumView;
use LinkCloud\Fast\Hyperf\Framework\Entity\BaseRequest;
use LinkCloud\Fast\Hyperf\Framework\Entity\BaseResponse;
use LinkCloud\Fast\Hyperf\Helpers\StringHelper;
use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeVisitorAbstract;

class EntityVisitor extends NodeVisitorAbstract
{
    protected string $class;

    protected array $columns;

    protected array $uses;

    public function __construct(string $class, array $columns)
    {
        $this->class = $class;
        $this->columns = $columns;
    }

    public function beforeTraverse(array $nodes)
    {
        $this->uses = [];
        foreach ($this->columns as $column) {
            if (class_exists($column['data_type']) && !in_array($column['data_type'], $this->uses)) {
                $this->uses[] = $column['data_type'];
            }
            if (!empty($column['attributes'])) {
                foreach ($column['attributes'] as $class => $params) {
                    if (!in_array($class, $this->uses)) {
                        $this->uses[] = $class;
                    }
                    if ($class == ArrayType::class
                        && !in_array($params[0], $this->uses)) {
                        $this->uses[] = $params[0];
                    }
                }
            }
            if (!in_array(ApiProperty::class, $this->uses)) {
                $this->uses[] = ApiProperty::class;
            }
        }
        return parent::beforeTraverse($nodes);
    }

    public function leaveNode(Node $node): Node
    {
        switch ($node) {
            case $node instanceof Node\Stmt\Namespace_:
                foreach ($node->stmts as $key => $stmt) {
                    if (get_class($stmt) == Node\Stmt\Use_::class) {
                        if ($stmt->uses[0]->name == BaseRequest::class || $stmt->uses[0]->name == BaseResponse::class) {
                            continue;
                        }
                        unset($node->stmts[$key]);
                    }
                }
                $stmts = [];
                foreach ($this->uses as $class) {
                    $use = new Node\Stmt\Use_([new Node\Stmt\UseUse(new Node\Name($class))]);
                    $stmts[] = $use;
                }
                $node->stmts = array_merge($stmts, $node->stmts);
                return $node;
            case $node instanceof Node\Stmt\Class_:
            case $node instanceof Node\Stmt\Trait_:
                $node->setAttribute('comments', [new Comment(PHP_EOL)]);
                $node->stmts = $this->addProperties();
                return $node;
        }
        return $node;
    }

    protected function addProperties(): array
    {
        $properties = [];
        foreach ($this->columns as $column) {
            if (in_array($column['column_name'], ['id', 'enable'])) {
                continue;
            }
            $property = new Node\Stmt\Property(
                Class_::MODIFIER_PUBLIC,
                [new Node\Stmt\PropertyProperty(StringHelper::camelize($column['column_name']))],
                ['comments' => [new Comment(PHP_EOL)]],
                $this->formatType($column['data_type'])
            );

            $autoFill = true;
            if (!empty($column['attributes'])) {
                foreach ($column['attributes'] as $class => $params) {
                    switch ($class) {
                        case ApiAttribute::class:
                        {
                            $property->attrGroups[] = $this->generateApiAttributeAttribute($column['column_comment']);
                            $autoFill = false;
                            break;
                        }
                        case EnumView::class:
                        {
                            $property->attrGroups[] = $this->generateEnumViewAttribute();
                            break;
                        }
                        case ArrayType::class:
                        {
                            $property->attrGroups[] = $this->generateArrayTypeAttribute(...$params);
                            break;
                        }
                    }
                }
            }
            if ($autoFill) {
                $property->attrGroups[] = $this->generateApiPropertyAttribute($column['column_comment']);
            }
            $properties[] = $property;
        }
        return $properties;
    }

    protected function generateApiPropertyAttribute(string $name): Node\AttributeGroup
    {
        $attrName = new Node\Name(class_basename(ApiProperty::class));
        $args = [];
        $args[] = new Node\Arg(new Node\Scalar\String_($name), false, false, []);
        return new Node\AttributeGroup([new Attribute($attrName, $args)]);
    }

    protected function generateApiAttributeAttribute(string $name): Node\AttributeGroup
    {
        $attrName = new Node\Name(class_basename(ApiAttribute::class));
        $args = [];
        $args[] = new Node\Arg(new Node\Scalar\String_($name), false, false, []);
        return new Node\AttributeGroup([new Attribute($attrName, $args)]);
    }

    protected function generateEnumViewAttribute(): Node\AttributeGroup
    {
        $attrName = new Node\Name(class_basename(EnumView::class));
        $args = [];
        return new Node\AttributeGroup([new Attribute($attrName, $args)]);
    }

    protected function generateArrayTypeAttribute(string $valueType): Node\AttributeGroup
    {
        $attrName = new Node\Name(class_basename(ArrayType::class));
        $args = [];
        $args[] = new Node\Arg(new Node\Scalar\String_($valueType), false, false, [],
            new Node\Identifier('value_type'));
        return new Node\AttributeGroup([new Attribute($attrName, $args)]);
    }

    public function formatType(string $type): string
    {
        if (class_exists($type)) {
            return class_basename($type);
        } else {
            return match ($type) {
                'tinyint', 'smallint', 'mediumint', 'int' => 'int',
                'bool', 'boolean' => 'bool',
                'array', 'json' => 'array',
                default => 'string',
            };
        }
    }
}