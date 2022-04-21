<?php
declare(strict_types=1);

namespace LinkCloud\Fast\Hyperf\Command\CodeGen;

class ControllerGenerator extends BaseGenerator
{
    public function generate(string $path, array &$config, string $model, string $name, string $uri, array $logicClass, array $entities)
    {
        if (!empty($this->option->terminal)) {
            foreach ($this->option->terminal as $terminal) {
                if (!isset($config[$terminal]['controller'])) {
                    $config[$terminal]['controller'] = 1;
                }
                if (isset($config[$terminal]['controller']) && $config[$terminal]['controller'] == 0) {
                    continue;
                }
                $this->build($path . ucfirst($terminal), $model, $name, $uri . '/' . lcfirst($terminal) . '/' . lcfirst(class_basename($model)), $logicClass[$terminal], $entities[$terminal]);
            }
        } else {
            if (!isset($config['controller'])) {
                $config['controller'] = 1;
            }
            if (isset($config['controller']) && $config['controller'] == 0) {
                return;
            }

            $this->build($path, $model, $name, $uri . '/' . lcfirst(class_basename($model)), $logicClass[0], $entities);
        }
    }

    protected function build(string $path, string $model, string $name, string $uri, string $logicClass, array $entities): string
    {
        $modelName = class_basename($model);
        $class = $this->project->namespace($path) . $modelName . 'Controller';
        $path = $this->basePath . '/' . $this->project->path($class);
        if (!file_exists($path)) {
            $this->mkdir($path);
            unset($entities['response']['item']);
            file_put_contents($path, $this->buildClass($class, [$logicClass, ...array_values($entities['request']), ...array_values($entities['response'])], $name, $uri, class_basename($logicClass), $entities));
        }
        return $class;
    }

    /**
     * Build the class with the given name.
     */
    protected function buildClass(string $className, array $uses, string $name, string $uri, string $logicClass, array $entities): string
    {
        $logicName = lcfirst($logicClass);

        $stub = file_get_contents(__DIR__ . '/stubs/Controller.stub');
        $this->replaceNamespace($stub, $className)
            ->replaceClass($stub, $className)
            ->replaceUses($stub, $uses)
            ->replace($stub, '%LOGIC_CLASS%', $logicClass)
            ->replace($stub, '%LOGIC_NAME%', $logicName)
            ->replace($stub, '%NAME%', $name)
            ->replace($stub, '%API_URI%', $uri)
            ->replace($stub, '%DESCRIPTION%', $name . '管理')
            ->replace($stub, '%LIST_REQUEST%', class_basename($entities['request']['list']))
            ->replace($stub, '%LIST_RESPONSE%', class_basename($entities['response']['list']))
            ->replace($stub, '%CREATE_REQUEST%', class_basename($entities['request']['create']))
            ->replace($stub, '%MODIFY_REQUEST%', class_basename($entities['request']['modify']))
            ->replace($stub, '%REMOVE_REQUEST%', class_basename($entities['request']['remove']))
            ->replace($stub, '%DETAIL_REQUEST%', class_basename($entities['request']['detail']))
            ->replace($stub, '%DETAIL_RESPONSE%', class_basename($entities['response']['detail']));
        return $stub;
    }
}