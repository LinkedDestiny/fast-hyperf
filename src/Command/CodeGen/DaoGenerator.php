<?php
declare(strict_types=1);

namespace LinkCloud\Fast\Hyperf\Command\CodeGen;

class DaoGenerator extends BaseGenerator
{
    public function generate(string $path, string $model, string $daoInterface): string
    {
        $modelName =  class_basename($model);
        $class = $this->project->namespace($path) . $modelName . 'Dao';
        $path = $this->basePath . '/' . $this->project->path($class);
        if (!file_exists($path)) {
            $this->mkdir($path);
            file_put_contents($path, $this->buildClass($class, [$model, $daoInterface], $modelName, class_basename($daoInterface)));
        }
        return $class;
    }

    /**
     * Build the class with the given name.
     */
    protected function buildClass(string $className, array $uses, string $model, string $interface): string
    {
        $stub = file_get_contents(__DIR__ . '/stubs/Dao.stub');
        $this->replaceNamespace($stub, $className)
            ->replaceClass($stub, $className)
            ->replaceUses($stub, $uses)
            ->replace($stub, '%INTERFACES%', $interface)
            ->replace($stub, '%MODEL_NAME%', $model);
        return $stub;
    }
}