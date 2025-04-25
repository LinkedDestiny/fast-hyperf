<?php
declare(strict_types=1);

namespace LinkCloud\Fast\Hyperf\Framework;

use Hyperf\Snowflake\IdGenerator\SnowflakeIdGenerator;
use RuntimeException;
use Throwable;

class UuidGenerator
{
    public function generate(): string
    {
        try {
            return strval(di()->get(SnowflakeIdGenerator::class)->generate());
        } catch (Throwable $e) {
            throw new RuntimeException('ID生成器错误');
        }
    }
}