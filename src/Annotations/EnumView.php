<?php
declare(strict_types=1);

namespace LinkCloud\Fast\Hyperf\Annotations;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class EnumView
{
    public const ENUM_MESSAGE = 1;
    public const ENUM_VALUE = 2;

    public int $flags;

    public function __construct(int $flags = self::ENUM_MESSAGE | self::ENUM_VALUE)
    {
        $this->flags = $flags;
    }
}