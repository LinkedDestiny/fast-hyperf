<?php
declare(strict_types=1);

namespace LinkCloud\Fast\Hyperf\Constants;

use LinkCloud\Fast\Hyperf\Annotations\EnumMessage;
use LinkCloud\Fast\Hyperf\Common\BaseEnum;

/**
 * 软删除状态
 */
class SoftDeleted extends BaseEnum
{
    #[EnumMessage(message: '正常')]
    public const ENABLE = 1;

    #[EnumMessage(message: '删除')]
    public const DISABLE = 0;
}