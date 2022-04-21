<?php

declare(strict_types=1);

namespace LinkCloud\Fast\Hyperf\Command;

use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Hyperf\Di\ReflectionManager;
use LinkCloud\Fast\Hyperf\Annotations\EnumMessage;
use ReflectionClass;
use Yiisoft\VarDumper\VarDumper;

#[Command]
class TranslatorGenCommand extends HyperfCommand
{
    /**
     * 执行的命令行
     */
    protected ?string $name = 'translate:gen';

    public function handle()
    {
        $dirs = config('generate.transfers.dirs');
        $language = config('generate.transfers.languages', ['zh_CN', 'en']);
        $this->translate($dirs, $language);
        $this->line('完成生成');
    }

    protected function translate(array $dirs, array $languages)
    {
        foreach ($dirs as $outputPath => $path) {
            $result = ReflectionManager::getAllClasses($path);
            $output = [];
            foreach ($result as $class) {
                /** @var ReflectionClass $class */
                $constants = $class->getReflectionConstants();
                foreach ($constants as $constant) {
                    $attribute = $constant->getAttributes(EnumMessage::class);
                    $message = $attribute[0]->newInstance()->message;
                    $output[$class->getName()][$message] = $message;
                }
            }

            foreach ($languages as $language) {
                $path = sprintf($outputPath, $language);
                $dir = dirname($path);
                if (!file_exists($dir)) {
                    @mkdir($dir, 0755, true);
                }
                file_put_contents($path, "<?php
declare(strict_types=1);

return " . VarDumper::create($output)->export() . ';');
            }
        }
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('国际化转换工具');
    }

}