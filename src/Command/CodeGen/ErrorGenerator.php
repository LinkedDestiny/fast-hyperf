<?php
declare(strict_types=1);

namespace LinkCloud\Fast\Hyperf\Command\CodeGen;


class ErrorGenerator extends BaseGenerator
{
    public function generate(string $path, string $model, string $name): string
    {
        $modelName =  class_basename($model);
        $class = $this->project->namespace($path) . $modelName . 'Error';
        $path = $this->basePath . '/' . $this->project->path($class);
        if (!file_exists($path)) {
            $this->mkdir($path);
            file_put_contents($path, $this->buildClass($class, $name));
        }
        return $class;
    }

    /**
     * Build the class with the given name.
     */
    protected function buildClass(string $className, $name): string
    {
        $stub = file_get_contents(__DIR__ . '/stubs/Error.stub');
        $this->replaceNamespace($stub, $className)
            ->replaceClass($stub, $className)
            ->replace($stub, '%NAME%', $name);
        return $stub;
    }
}