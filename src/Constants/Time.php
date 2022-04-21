<?php
declare(strict_types=1);

namespace LinkCloud\Fast\Hyperf\Constants;

use LinkCloud\Fast\Hyperf\Annotations\EnumMessage;
use LinkCloud\Fast\Hyperf\Common\BaseEnum;

class Time extends BaseEnum
{
    #[EnumMessage(message: '分钟')]
    public const MINUTE = 60;

    #[EnumMessage(message: '天')]
    public const HOUR = 3600;

    #[EnumMessage(message: '天')]
    public const DAY = 86400;

    #[EnumMessage(message: '月')]
    public const MONTH = 2592000;

    #[EnumMessage(message: '年')]
    public const YEAR = 31536000;
}