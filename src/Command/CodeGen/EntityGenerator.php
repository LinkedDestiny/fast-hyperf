<?php
declare(strict_types=1);

namespace LinkCloud\Fast\Hyperf\Command\CodeGen;

use LinkCloud\Fast\Hyperf\Command\CodeGen\Visitor\EntityVisitor;
use LinkCloud\Fast\Hyperf\Command\Option\GenerateOption;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use PhpParser\PrettyPrinterAbstract;
use ReflectionClass;

class EntityGenerator extends BaseGenerator
{
    protected Parser $astParser;

    protected PrettyPrinterAbstract $printer;

    public function __construct(GenerateOption $option)
    {
        parent::__construct($option);
        $this->astParser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $this->printer = new Standard();
    }

    public function generate(string $path, array &$config, string $model, array $columns): array
    {
        $modelName = class_basename($model);

        $output = [];
        if (!empty($this->option->terminal)) {
            foreach ($this->option->terminal as $terminal) {
                if (isset($config[$terminal]['controller']) && $config[$terminal]['controller'] == 0) {
                    continue;
                }

                if (!isset($config[$terminal]['entity'])) {
                    $config[$terminal]['entity'] = [];
                }

                if (!isset($output[$terminal]['traits'])) {
                    $output[$terminal]['traits'] = [];
                }

                if (!isset($output[$terminal]['response'])) {
                    $output[$terminal]['response'] = [];
                }

                if (!isset($output[$terminal]['request'])) {
                    $output[$terminal]['request'] = [];
                }

                $terminalPath = ucfirst($terminal);
                $traits = $this->generateTraits($path . "Traits/$terminalPath",  $config[$terminal]['entity'], $modelName, $columns);
                $request = $this->generateRequest($path . "Request/$terminalPath", $config[$terminal]['entity'], $modelName, $columns,
                    $traits);
                $response = $this->generateResponse($path . "Response/$terminalPath", $config[$terminal]['entity'], $modelName, $columns);

                $output[$terminal]['traits'] = array_merge($output[$terminal]['traits'], $traits);
                $output[$terminal]['request'] = array_merge($output[$terminal]['request'], $request);
                $output[$terminal]['response'] = array_merge($output[$terminal]['response'], $response);
            }
        } else {
            if (isset($config['controller']) && $config['controller'] == 0) {
                return [];
            }
            if (!isset($config['entity'])) {
                $config['entity'] = [];
            }
            $output['traits'] = $this->generateTraits($path . 'Traits/', $config['entity'], $modelName, $columns);
            $output['request'] = $this->generateRequest($path. 'Request/', $config['entity'], $modelName, $columns,
                $output['traits']);
            $output['response'] = $this->generateResponse($path. 'Response/', $config['entity'], $modelName, $columns);
        }
        return $output;
    }

    protected function generateTraits(string $path, array &$config, string $modelName, array $columns): array
    {
        if (!isset($config['traits'])) {
            $config['traits'] = 1;
        }
        if (empty($config['traits'])) {
            return [];
        }
        return $this->_generateTraits($path, $modelName, $columns);
    }

    protected function _generateTraits(string $path, string $modelName, array $columns): array
    {
        $primaryColumn = null;
        foreach ($columns as $column) {
            if ($column['column_key'] === 'PRI') {
                $primaryColumn = $column;
                break;
            }
        }
        $columns = [];
        $columns[] = $primaryColumn;

        $identifierClassName = $this->project->namespace($path) . $modelName . 'Identifier';
        $identifierFile = $this->basePath . '/' . $this->project->path($identifierClassName);
        if (!file_exists($identifierFile)) {
            $this->mkdir($identifierFile);
            file_put_contents($identifierFile,
                $this->buildClass('entity/traits/identifier.stub', $identifierClassName, []));
        }

        $reflectData = new ReflectionClass($identifierClassName);
        if (empty($reflectData->getProperties())) {
            $stms = $this->astParser->parse(file_get_contents($identifierFile));
            $traverser = new NodeTraverser();
            $traverser->addVisitor(make(EntityVisitor::class, [
                'class'   => $identifierClassName,
                'columns' => $columns,
            ]));
            $stms = $traverser->traverse($stms);
            $code = $this->printer->prettyPrintFile($stms);
            file_put_contents($identifierFile, $code);
        }
        return [
            'identifier' => $identifierClassName
        ];
    }

    protected function generateRequest(
        string $path,
        array &$config,
        string $modelName,
        array $columns,
        array $traits
    ): array {
        if (!isset($config['request'])) {
            $config['request'] = ['data', 'condition', 'search', 'list_search', 'create', 'modify', 'remove', 'detail', 'list'];
        }
        if (empty($config['request'])) {
            return [];
        }
        return $this->_generateRequest($path, $config['request'], $modelName, $columns, $traits);
    }

    protected function _generateRequest(
        string $path,
        array $config,
        string $modelName,
        array $columns,
        array $traits
    ): array {
        // 生成Data对象
        $dataClassName = $this->project->namespace($path) . $modelName . 'Data';
        $dataFile = $this->basePath . '/' . $this->project->path($dataClassName);
        $create = $this->project->namespace($path) . $modelName . 'CreateRequest';
        $modify = $this->project->namespace($path) . $modelName . 'ModifyRequest';
        $remove = $this->project->namespace($path) . $modelName . 'RemoveRequest';
        $detail = $this->project->namespace($path) . $modelName . 'DetailRequest';
        $list = $this->project->namespace($path) . $modelName . 'ListRequest';

        if (in_array('data', $config)) {
            if (!file_exists($dataFile)) {
                $this->mkdir($dataFile);
                file_put_contents($dataFile, $this->buildClass('entity/request/request.stub', $dataClassName, []));
            }

            $reflectData = new ReflectionClass($dataClassName);
            if (count($reflectData->getProperties()) == 1) {
                $stms = $this->astParser->parse(file_get_contents($dataFile));
                $traverser = new NodeTraverser();
                $traverser->addVisitor(make(EntityVisitor::class, [
                    'class'   => $dataClassName,
                    'columns' => $columns,
                ]));
                $stms = $traverser->traverse($stms);
                $code = $this->printer->prettyPrintFile($stms);
                file_put_contents($dataFile, $code);
            }
        }

        // 生成参数对象
        $conditionClassName = $this->project->namespace($path) . $modelName . 'Condition';
        if (in_array('condition', $config)) {
            $this->build($conditionClassName, [], 'entity/request/request.stub');
        }

        $searchClassName = $this->project->namespace($path) . $modelName . 'Search';
        if (in_array('search', $config)) {
            $this->build($searchClassName, [
                'uses'     => [$traits['identifier']],
                'replaces' => [
                    '%IDENTIFIER%' => class_basename($traits['identifier'])
                ]
            ], 'entity/request/search.stub');
        }

        $listSearchClassName = $this->project->namespace($path) . $modelName . 'ListSearch';
        if (in_array('list_search', $config)) {
            $this->build($listSearchClassName, [], 'entity/request/request.stub');
        }

        if (in_array('create', $config)) {
            $this->build($create, [
                'uses'     => [$conditionClassName, $dataClassName],
                'replaces' => [
                    '%CONDITION_CLASS%' => class_basename($conditionClassName),
                    '%DATA_CLASS%'      => class_basename($dataClassName),
                ]
            ], 'entity/request/create.stub');
        }

        if (in_array('modify', $config)) {
            $this->build($modify, [
                'uses'     => [$conditionClassName, $dataClassName, $searchClassName],
                'replaces' => [
                    '%CONDITION_CLASS%' => class_basename($conditionClassName),
                    '%SEARCH_CLASS%'    => class_basename($searchClassName),
                    '%DATA_CLASS%'      => class_basename($dataClassName),
                ]
            ],
                'entity/request/modify.stub');
        }

        if (in_array('remove', $config)) {
            $this->build($remove, [
                'uses'     => [$conditionClassName, $searchClassName],
                'replaces' => [
                    '%CONDITION_CLASS%' => class_basename($conditionClassName),
                    '%SEARCH_CLASS%'    => class_basename($searchClassName),
                ]
            ], 'entity/request/remove.stub');
        }

        if (in_array('detail', $config)) {
            $this->build($detail, [
                'uses'     => [$conditionClassName, $searchClassName],
                'replaces' => [
                    '%CONDITION_CLASS%' => class_basename($conditionClassName),
                    '%SEARCH_CLASS%'    => class_basename($searchClassName),
                ]
            ], 'entity/request/detail.stub');
        }

        if (in_array('list', $config)) {
            $this->build($list, [
                'uses'     => [$conditionClassName, $listSearchClassName],
                'replaces' => [
                    '%CONDITION_CLASS%'   => class_basename($conditionClassName),
                    '%LIST_SEARCH_CLASS%' => class_basename($listSearchClassName),
                ]
            ], 'entity/request/list.stub');
        }

        return [
            'create' => $create,
            'modify' => $modify,
            'remove' => $remove,
            'detail' => $detail,
            'list'   => $list
        ];
    }

    protected function generateResponse(string $path,  array &$config, string $modelName, array $columns): array
    {
        if (!isset($config['response'])) {
            $config['response'] = ['list', 'item', 'detail'];
        }
        if (empty($config['response'])) {
            return [];
        }
        return $this->_generateResponse($path, $config['response'], $modelName, $columns);
    }

    protected function _generateResponse(string $path, array $config, string $modelName, array $columns): array
    {
        // 生成Item对象
        $itemClassName = $this->project->namespace($path) . $modelName . 'Item';
        $dataFile = $this->basePath . '/' . $this->project->path($itemClassName);

        if (in_array('item', $config)) {
            if (!file_exists($dataFile)) {
                $this->mkdir($dataFile);
                file_put_contents($dataFile, $this->buildClass('entity/response/item.stub', $itemClassName, []));
            }

            $reflectData = new ReflectionClass($itemClassName);
            if (count($reflectData->getProperties()) == 1) {
                $stms = $this->astParser->parse(file_get_contents($dataFile));
                $traverser = new NodeTraverser();
                $traverser->addVisitor(make(EntityVisitor::class, [
                    'class'   => $itemClassName,
                    'columns' => $columns,
                ]));
                $stms = $traverser->traverse($stms);
                $code = $this->printer->prettyPrintFile($stms);
                file_put_contents($dataFile, $code);
            }
        }

        $detail = $this->project->namespace($path) . $modelName . 'DetailResponse';
        if (in_array('detail', $config)) {
            $this->build($detail, [
                'replaces' => [
                    '%ITEM%' => class_basename($itemClassName),
                ]
            ], 'entity/response/detail.stub');
        }

        $list = $this->project->namespace($path) . $modelName . 'ListResponse';
        if (in_array('list', $config)) {
            $this->build($list, [
                'replaces' => [
                    '%ITEM%' => class_basename($itemClassName),
                ]
            ], 'entity/response/list.stub');
        }

        return [
            'detail' => $detail,
            'list'   => $list,
            'item'   => $itemClassName,
        ];
    }

    protected function build(string $dataClassName, array $params, string $template)
    {
        $dataFile = $this->basePath . '/' . $this->project->path($dataClassName);
        if (!file_exists($dataFile)) {
            $this->mkdir($dataFile);
            file_put_contents($dataFile, $this->buildClass($template, $dataClassName, $params));
        }
    }

    /**
     * Build the class with the given name.
     */
    protected function buildClass(string $template, string $className, array $params): string
    {
        $stub = file_get_contents(__DIR__ . '/stubs/' . $template);
        $this->replaceNamespace($stub, $className)
            ->replaceClass($stub, $className);

        if (isset($params['uses'])) {
            $this->replaceUses($stub, $params['uses']);
        }

        if (isset($params['replaces'])) {
            $replaces = $params['replaces'];
            foreach ($replaces as $name => $value) {
                $this->replace($stub, $name, $value);
            }
        }
        return $stub;
    }
}