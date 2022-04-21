<?php
declare(strict_types=1);

namespace LinkCloud\Fast\Hyperf\Annotations\Api\Property;

use Attribute;
use LinkCloud\Fast\Hyperf\Constants\PropertyScope;

#[Attribute(Attribute::TARGET_PROPERTY)]
class ApiAttribute extends ApiProperty
{

    public function __construct(string $name, $example = null, bool $hidden = false)
    {
        parent::__construct($name, $example, $hidden);
        $this->scope = PropertyScope::ATTRIBUTE();
    }
}
