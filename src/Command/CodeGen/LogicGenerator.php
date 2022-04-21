<?php
declare(strict_types=1);

namespace LinkCloud\Fast\Hyperf\Command\CodeGen;

class LogicGenerator extends BaseGenerator
{
    public function generate(string $path, array $config, string $model, string $serviceClass, string $primaryKey, array $entities): array
    {
        if (!empty($this->option->terminal)) {
            $output = [];
            foreach ($this->option->terminal as $terminal) {
                if (isset($config[$terminal]['controller']) && $config[$terminal]['controller'] == 0) {
                    continue;
                }
                $output[$terminal] = $this->build($path . ucfirst($terminal), $model, $serviceClass, $primaryKey, $entities[$terminal]['response']['item']);
            }
            return $output;
        } else {
            if (isset($config['controller']) && $config['controller'] == 0) {
                return [];
            }
            return [$this->build($path, $model, $serviceClass, $primaryKey, $entities['response']['item'])];
        }
    }

    protected function build(string $path, string $model, string $serviceClass, string $primaryKey, string $itemClass): string
    {
        $modelName = class_basename($model);
        $class = $this->project->namespace($path) . $modelName . 'Logic';
        $serviceName = lcfirst(class_basename($serviceClass));
        $path = $this->basePath . '/' . $this->project->path($class);
        if (!file_exists($path)) {
            $this->mkdir($path);
            file_put_contents($path, $this->buildClass($class, [$serviceClass, $itemClass], class_basename($serviceClass), $serviceName, $primaryKey, class_basename($itemClass)));
        }
        return $class;
    }

    /**
     * Build the class with the given name.
     */
    protected function buildClass(string $className, array $uses, string $serviceClass, string $serviceName, string $primaryKey, string $item): string
    {
        $stub = file_get_contents(__DIR__ . '/stubs/Logic.stub');
        $this->replaceNamespace($stub, $className)
            ->replaceClass($stub, $className)
            ->replaceUses($stub, $uses)
            ->replace($stub, '%SERVICE_CLASS%', $serviceClass)
            ->replace($stub, '%SERVICE_NAME%', $serviceName)
            ->replace($stub, '%PRIMARY_KEY%', $primaryKey)
            ->replace($stub, '%ITEM%', $item);
        return $stub;
    }
}