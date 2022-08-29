<?php
declare(strict_types=1);

namespace LinkCloud\Fast\Hyperf\Command\CodeGen;

class ServiceGenerator extends BaseGenerator
{
    public function generate(string $path, string $model, string $daoInterface): string
    {
        $modelName = class_basename($model);
        $class = $this->project->namespace($path) . $modelName . 'Service';
        $daoName = lcfirst($modelName) . 'Dao';
        $path = $this->basePath . '/' . $this->project->path($class);
        if (!file_exists($path)) {
            $this->mkdir($path);
            file_put_contents($path, $this->buildClass($class, [$daoInterface], class_basename($daoInterface), $daoName));
        }
        return $class;
    }

    /**
     * Build the class with the given name.
     */
    protected function buildClass(string $className, array $uses, string $daoInterface, string $dao): string
    {
        $stub = file_get_contents(__DIR__ . '/stubs/Service.stub');
        $this->replaceNamespace($stub, $className)
            ->replaceClass($stub, $className)
            ->replaceUses($stub, $uses)
            ->replace($stub, '%DAO_INTERFACE%', $daoInterface)
            ->replace($stub, '%DAO_NAME%', $dao);
        return $stub;
    }
}