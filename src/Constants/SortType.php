<?php
declare(strict_types=1);

namespace LinkCloud\Fast\Hyperf\Constants;

use LinkCloud\Fast\Hyperf\Annotations\EnumMessage;
use LinkCloud\Fast\Hyperf\Common\BaseEnum;

/**
 * 排序方式
 * @method static SortType ASC()
 * @method static SortType DESC()
 */
class SortType extends BaseEnum
{
	#[EnumMessage(message: "正序")]
    public const ASC = 'asc';

	#[EnumMessage(message: "倒序")]
    public const DESC = 'desc';
}