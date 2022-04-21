<?php
declare(strict_types=1);

namespace LinkCloud\Fast\Hyperf\ApiDocs\Scanner\Property;

use LinkCloud\Fast\Hyperf\Constants\PropertyScope;

class Property
{
    public bool $isSimpleType;

    public string $type;

    public ?string $className;

    public PropertyScope $scope;
}
