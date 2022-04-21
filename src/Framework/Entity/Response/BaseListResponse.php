<?php
declare(strict_types=1);

namespace LinkCloud\Fast\Hyperf\Framework\Entity\Response;

use LinkCloud\Fast\Hyperf\Annotations\Api\Property\ApiProperty;
use LinkCloud\Fast\Hyperf\Common\BaseObject;

class BaseListResponse extends BaseObject
{
    #[ApiProperty('页码')]
    public int $page = 0;

    #[ApiProperty('分类名称')]
    public int $pageSize = 0;

    #[ApiProperty('总数')]
    public int $total = 0;
}
