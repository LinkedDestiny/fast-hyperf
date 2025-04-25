<?php

declare(strict_types=1);

namespace LinkCloud\Fast\Hyperf\Command;

use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use LinkCloud\Fast\Hyperf\Command\CodeGen\ControllerGenerator;
use LinkCloud\Fast\Hyperf\Command\CodeGen\DaoGenerator;
use LinkCloud\Fast\Hyperf\Command\CodeGen\DaoInterfaceGenerator;
use LinkCloud\Fast\Hyperf\Command\CodeGen\EntityGenerator;
use LinkCloud\Fast\Hyperf\Command\CodeGen\ErrorGenerator;
use LinkCloud\Fast\Hyperf\Command\CodeGen\LogicGenerator;
use LinkCloud\Fast\Hyperf\Command\CodeGen\ModelGenerator;
use LinkCloud\Fast\Hyperf\Command\CodeGen\ServiceGenerator;
use LinkCloud\Fast\Hyperf\Command\Option\GenerateOption;
use Symfony\Component\Console\Input\InputOption;
use function Hyperf\Config\config;
use function Hyperf\Support\class_basename;

#[Command]
class GenerateCommand extends HyperfCommand
{

    /**
     * 执行的命令行
     */
    protected ?string $name = 'gen:code';

    protected ?string $signature = 'gen:code';

    public function handle(): void
    {
        try {
            $this->line('代码自动生成工具启动');

            $path = $this->input->getOption('path');
            $pool = $this->input->getOption('pool');
            $table = $this->input->getOption('table');
            $url = $this->input->getOption('url');

            $option = new GenerateOption();
            $option->terminal = $this->input->getOption('terminal');
            if (empty($option->terminal)) {
                $option->terminal = config('generate.terminal', []);
            }
            $option->errorPath = config('generate.error_path', $path . '/Constants/Errors/');
            $option->basePath = config('generate.base_path');

            $daoInterfacePath = $path . '/Repository/Dao/Contracts';
            $daoPath = $path . '/Repository/Dao/MySQL';
            $servicePath = $path . '/Service/';
            $entityPath = $path . '/Entity/';
            $errorPath = $option->errorPath;
            $logicPath = $path . '/Logic/';
            $controllerPath = $path . '/Application/Controller/';
            $configPath = $option->basePath . $path . '/Config/';


            // 生成Model
            $models = (new ModelGenerator($option))->generate($pool, $table);

            $daoInterfaceGenerator = new DaoInterfaceGenerator($option);
            $daoGenerator = new DaoGenerator($option);
            $errGenerator = new ErrorGenerator($option);
            $serviceGenerator = new ServiceGenerator($option);
            $entityGenerator = new EntityGenerator($option);
            $logicGenerator = new LogicGenerator($option);
            $controllerGenerator = new ControllerGenerator($option);

            foreach ($models as $modelClass => $model) {
                $name = $model['comment'];
                $columns = $model['columns'];
                $primaryKey = $model['primary_key'];
                if (str_ends_with($name, '表')) {
                    $name = substr($name, 0, -3);
                }

                $configFile = $configPath . class_basename($modelClass) . '.php';
                $config = [];
                if (file_exists($configFile)) {
                    $config = require $configFile;
                }

                $daoInterface = $daoInterfaceGenerator->generate($daoInterfacePath, $modelClass);
                $daoGenerator->generate($daoPath, $modelClass, $daoInterface);
                $error = $errGenerator->generate($errorPath, $modelClass, $name);
                $service = $serviceGenerator->generate($servicePath, $modelClass, $daoInterface);

                $entities = $entityGenerator->generate($entityPath, $config, $modelClass, $columns);
                $logic = $logicGenerator->generate($logicPath, $config, $modelClass, $service, $primaryKey, $entities, $error);
                $controllerGenerator->generate($controllerPath, $config, $modelClass, $name, $url, $logic, $entities);

                if (!file_exists($configFile)) {
                    $daoInterfaceGenerator->mkdir($configFile);
                }
                $daoInterfaceGenerator->printConfig($configFile, $config);
            }
        } catch (\Throwable $e) {
            var_dump(format_throwable($e));
        }
    }

    public function configure(): void
    {
        parent::configure();

        $this->addUsage('-p /app/Order -P order -t t_order');
        $this->setDescription('代码生成工具');

        $this->addOption('path', 'p', InputOption::VALUE_REQUIRED, '路径');
        $this->addOption('pool', 'P', InputOption::VALUE_REQUIRED, '数据连接池', 'default');
        $this->addOption('table', 'T', InputOption::VALUE_OPTIONAL, '表');
        $this->addOption('url', 'a', InputOption::VALUE_REQUIRED, '请求url前缀', '/api');
        $this->addOption('terminal', 't', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, '终端', []);
    }


}