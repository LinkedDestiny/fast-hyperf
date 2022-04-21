<?php
declare(strict_types=1);

namespace LinkCloud\Fast\Hyperf\Framework\Entity\Request;

use LinkCloud\Fast\Hyperf\Annotations\Api\Property\ApiProperty;
use LinkCloud\Fast\Hyperf\Constants\SortType;
use LinkCloud\Fast\Hyperf\Framework\Entity\BaseRequest;

class BaseSort extends BaseRequest
{
    #[ApiProperty('创建时间排序')]
    public SortType $createAt;

    public function __construct(array $data = [])
    {
        $this->createAt = SortType::DESC();
        parent::__construct($data);
    }
}