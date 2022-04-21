<?php

declare(strict_types=1);

namespace LinkCloud\Fast\Hyperf\Command\Option;

class GenerateOption
{
    /**
     * 终端分组
     */
    public array $terminal = [];

    /**
     * 错误码路径
     */
    public string $errorPath = '';

    /**
     * 基础路径
     */
    public string $basePath = '';
}