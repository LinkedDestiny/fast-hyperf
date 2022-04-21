<?php
declare(strict_types=1);

namespace LinkCloud\Fast\Hyperf\Annotations\Api\Property;

use Attribute;
use Hyperf\Di\Annotation\AbstractAnnotation;
use LinkCloud\Fast\Hyperf\Constants\PropertyScope;

#[Attribute(Attribute::TARGET_PROPERTY)]
class ApiProperty extends AbstractAnnotation
{
    public string $name = '';

    public mixed $example = null;

    public bool $hidden = false;

    public PropertyScope $scope;

    public function __construct(string $name, $example = null, bool $hidden = false)
    {
        parent::__construct();
        $this->name = $name;
        $this->example = $example;
        $this->hidden = $hidden;
        $this->scope = PropertyScope::BODY();
    }
}