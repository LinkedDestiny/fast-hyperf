<?php
declare(strict_types=1);

namespace LinkCloud\Fast\Hyperf\Common;

use Hyperf\Contract\TranslatorInterface;
use Hyperf\Utils\ApplicationContext;
use LinkCloud\Fast\Hyperf\Annotations\EnumMessage;
use MabeEnum\Enum;
use MabeEnum\EnumSerializableTrait;
use ReflectionClass;
use ReflectionException;
use Serializable;
use Throwable;

class BaseEnum extends Enum implements Serializable
{
    use EnumSerializableTrait;

    /**
     * Get the name of the enumerator
     * @return string
     */
    public function getMessage(): string
    {
        $class = get_called_class();
        try {
            $reflection = new ReflectionClass($class);
            $reflection = $reflection->getReflectionConstant($this->getName());
            $attributes = $reflection->getAttributes(EnumMessage::class);
            if (empty($attributes)) {
                return $this->getName();
            }
            try {
                $translator = ApplicationContext::getContainer()->get(TranslatorInterface::class);
                $key = sprintf('enums.%s.%s' , $class, $attributes[0]->newInstance()->message);
                $result = $translator->trans($key);
                return $key === $result ? $attributes[0]->newInstance()->message : $result;
            } catch (Throwable $e) {
                return $attributes[0]->newInstance()->message;
            }
        } catch (ReflectionException $e) {
            return '';
        }
    }
}