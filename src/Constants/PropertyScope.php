<?php
declare(strict_types=1);

namespace LinkCloud\Fast\Hyperf\Constants;

use LinkCloud\Fast\Hyperf\Annotations\EnumMessage;
use LinkCloud\Fast\Hyperf\Common\BaseEnum;

/**
 * 参数范围
 *
 * @method static PropertyScope BODY()
 * @method static PropertyScope HEADER()
 * @method static PropertyScope ATTRIBUTE()
 */
class PropertyScope extends BaseEnum
{
    #[EnumMessage(message: '请求体')]
    public const BODY = 'body';

    #[EnumMessage(message: '请求头')]
    public const HEADER = 'header';

    #[EnumMessage(message: '请求属性')]
    public const ATTRIBUTE = 'attribute';
}