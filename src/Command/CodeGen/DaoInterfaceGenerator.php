<?php
declare(strict_types=1);

namespace LinkCloud\Fast\Hyperf\Command\CodeGen;

class DaoInterfaceGenerator extends BaseGenerator
{
    public function generate(string $path, string $model): string
    {
        $modelName =  class_basename($model);
        $class = $this->project->namespace($path) . $modelName . 'DaoInterface';
        $path = $this->basePath . '/' . $this->project->path($class);
        if (!file_exists($path)) {
            $this->mkdir($path);
            file_put_contents($path, $this->buildClass($class));
        }
        return $class;
    }

    /**
     * Build the class with the given name.
     */
    protected function buildClass(string $className): string
    {
        $stub = file_get_contents(__DIR__ . '/stubs/DaoInterface.stub');
        $this->replaceNamespace($stub, $className)
            ->replaceClass($stub, $className);
        return $stub;
    }
}