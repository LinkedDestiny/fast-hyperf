<?php
declare(strict_types=1);

namespace LinkCloud\Fast\Hyperf\Framework\Entity;

use LinkCloud\Fast\Hyperf\Common\BaseObject;

class Page extends BaseObject
{
    /**
     * 页码
     * @var int
     */
    public int $page = 1;

    /**
     * 页数
     * @var int
     */
    public int $pageSize = 20;

    /**
     * 是否获取全部结果
     * @var bool
     */
    public bool $all = false;

    /**
     * 是否获取总数
     * @var bool
     */
    public bool $total = true;

    /**
     * @param bool $total
     * @return Page
     */
    public static function all(bool $total = false): Page
    {
        return new Page([
            'all' => true,
            'total' => $total
        ]);
    }
}
