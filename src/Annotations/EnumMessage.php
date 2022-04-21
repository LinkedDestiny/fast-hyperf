<?php
declare(strict_types=1);

namespace LinkCloud\Fast\Hyperf\Annotations;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
class EnumMessage
{
    /**
     * 枚举描述
     * @var string
     */
    public string $message = '';

    public function __construct(string $message)
    {
        $this->message = $message;
    }
}