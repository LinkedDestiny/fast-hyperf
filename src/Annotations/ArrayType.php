<?php
declare(strict_types=1);

namespace LinkCloud\Fast\Hyperf\Annotations;

use Attribute;

#[Attribute]
class ArrayType
{
    /**
     * key的类型
     * @var string
     */
    public string $keyType;

    /**
     * 值的类型
     * @var string
     */
    public string $valueType;

    public function __construct(string $valueType, string $keyType = 'int')
    {
        $this->valueType = $valueType;
        $this->keyType = $keyType;
    }
}