<?php
declare(strict_types=1);

namespace LinkCloud\Fast\Hyperf\Command\CodeGen;

class LogicGenerator extends BaseGenerator
{
    public function generate(string $path, array $config, string $model, string $serviceClass, string $primaryKey, array $entities, string $error): array
    {
        if (!empty($this->option->terminal)) {
            $output = [];
            foreach ($this->option->terminal as $terminal) {
                if (isset($config[$terminal]['controller']) && $config[$terminal]['controller'] == 0) {
                    continue;
                }
                $output[$terminal] = $this->build($path . ucfirst($terminal), $model, $serviceClass, $primaryKey, $entities[$terminal]['response']['item'], $error);
            }
            return $output;
        } else {
            if (isset($config['controller']) && $config['controller'] == 0) {
                return [];
            }
            return [$this->build($path, $model, $serviceClass, $primaryKey, $entities['response']['item'], $error)];
        }
    }

    protected function build(string $path, string $model, string $serviceClass, string $primaryKey, string $itemClass, string $error): string
    {
        $modelName = class_basename($model);
        $class = $this->project->namespace($path) . $modelName . 'Logic';
        $serviceName = lcfirst(class_basename($serviceClass));
        $path = $this->basePath . '/' . $this->project->path($class);
        if (!file_exists($path)) {
            $this->mkdir($path);
            file_put_contents($path, $this->buildClass($class, [$serviceClass, $itemClass, $error], class_basename($serviceClass), $serviceName, $primaryKey,
                class_basename($itemClass), class_basename($error)));
        }
        return $class;
    }

    /**
     * Build the class with the given name.
     */
    protected function buildClass(string $className, array $uses, string $serviceClass, string $serviceName, string $primaryKey, string $item, string $error): string
    {
        $stub = file_get_contents(__DIR__ . '/stubs/Logic.stub');
        $this->replaceNamespace($stub, $className)
            ->replaceClass($stub, $className)
            ->replaceUses($stub, $uses)
            ->replace($stub, '%SERVICE_CLASS%', $serviceClass)
            ->replace($stub, '%SERVICE_NAME%', $serviceName)
            ->replace($stub, '%PRIMARY_KEY%', $primaryKey)
            ->replace($stub, '%ITEM%', $item)
            ->replace($stub, '%ERROR%', $error);
        return $stub;
    }
}