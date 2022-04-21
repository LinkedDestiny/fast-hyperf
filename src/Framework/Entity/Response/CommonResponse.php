<?php
declare(strict_types=1);

namespace LinkCloud\Fast\Hyperf\Framework\Entity\Response;

use LinkCloud\Fast\Hyperf\Annotations\Api\Property\ApiProperty;
use LinkCloud\Fast\Hyperf\Common\BaseObject;

class CommonResponse extends BaseObject
{
    #[ApiProperty('状态码')]
    public int $code = 0;

    #[ApiProperty('信息')]
    public string $msg = "";

    #[ApiProperty('响应数据')]
    public mixed $data;
}
