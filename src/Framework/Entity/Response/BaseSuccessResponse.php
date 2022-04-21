<?php
declare(strict_types=1);

namespace LinkCloud\Fast\Hyperf\Framework\Entity\Response;

use LinkCloud\Fast\Hyperf\Annotations\Api\Property\ApiProperty;
use LinkCloud\Fast\Hyperf\Framework\Entity\BaseResponse;

class BaseSuccessResponse extends BaseResponse
{
    #[ApiProperty('请求结果')]
    public bool $result = true;
}