<?php
declare(strict_types=1);

namespace LinkCloud\Fast\Hyperf\Annotations\Api;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class ApiHeader extends BaseParam
{
    protected string $in = 'header';
}
