<?php
declare(strict_types=1);

namespace LinkCloud\Fast\Hyperf\Annotations\Api;

use Attribute;
use Hyperf\Di\Annotation\AbstractMultipleAnnotation;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class ApiResponse extends AbstractMultipleAnnotation
{
    public ?string $code;

    public ?string $description;

    public ?string $className;

    public ?string $type;

    public function __construct(string|null $code = null, string|null $description = null, string|null $className = null, string|null $type = null)
    {
        $this->code = $code;
        $this->description = $description;
        $this->className = $className;
        $this->type = $type;
    }
}
