<?php
declare(strict_types=1);

namespace LinkCloud\Fast\Hyperf\Framework\Exception;

use Exception;
use Hyperf\Contract\TranslatorInterface;
use Hyperf\Utils\ApplicationContext;
use LinkCloud\Fast\Hyperf\Framework\Entity\ErrorCode;
use Throwable;

class BusinessException extends Exception
{
    public function __construct(ErrorCode $code, string $message = null, array $replaces = [])
    {
        if (empty($message)) {
            $message = $code->getMessage();
        }
        try {
            $translator = ApplicationContext::getContainer()->get(TranslatorInterface::class);
            $key = sprintf('errors.%s.%s' , get_class($code), $message);
            $result = $translator-> trans($key, $replaces);
            parent::__construct($key === $result ? $message : $result, $code->getValue());
        } catch (Throwable $e) {
            parent::__construct($message, $code->getValue());
        }
    }
}
