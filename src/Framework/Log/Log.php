<?php
declare(strict_types=1);

namespace LinkCloud\Fast\Hyperf\Framework\Log;

use Hyperf\Context\ApplicationContext;
use Hyperf\Logger\Logger;

/**
 * @method static Logger get($name)
 * @method static void log($level, $message, array $context = array())
 * @method static void emergency($message, array $context = array())
 * @method static void alert($message, array $context = array())
 * @method static void critical($message, array $context = array())
 * @method static void error($message, array $context = array())
 * @method static void warning($message, array $context = array())
 * @method static void notice($message, array $context = array())
 * @method static void info($message, array $context = array())
 * @method static void debug($message, array $context = array())
 */
class Log
{
    public static function __callStatic($name, $arguments)
    {
        $container = ApplicationContext::getContainer();
        $factory = $container->get(\Hyperf\Logger\LoggerFactory::class);
        if ($name === 'get') {
            return $factory->get(...$arguments);
        }
        $log = $factory->get('default');
        return $log->$name(...$arguments);
    }
}