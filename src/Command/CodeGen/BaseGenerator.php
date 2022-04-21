<?php
declare(strict_types=1);

namespace LinkCloud\Fast\Hyperf\Command\CodeGen;

use Hyperf\Database\Model\Model;
use Hyperf\Utils\CodeGen\Project;
use LinkCloud\Fast\Hyperf\Command\Option\GenerateOption;
use Yiisoft\VarDumper\VarDumper;

class BaseGenerator
{
    protected GenerateOption $option;

    protected Project $project;

    protected string $basePath;

    public function __construct(GenerateOption $option)
    {
        $this->option = $option;
        $this->project = new Project();
        $this->basePath = $option->basePath;
    }

    public function mkdir(string $path): void
    {
        $dir = dirname($path);
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
    }

    /**
     * Replace the namespace for the given stub.
     */
    protected function replace(string &$stub, string $name, string $value): self
    {
        $stub = str_replace(
            [$name],
            [$value],
            $stub
        );
        return $this;
    }

    /**
     * Replace the namespace for the given stub.
     */
    protected function replaceNamespace(string &$stub, string $name): self
    {
        $stub = str_replace(
            ['%NAMESPACE%'],
            [$this->getNamespace($name)],
            $stub
        );
        return $this;
    }

    /**
     * Replace the class name for the given stub.
     */
    protected function replaceClass(string &$stub, string $name): self
    {
        $class = str_replace($this->getNamespace($name) . '\\', '', $name);

        $stub = str_replace('%CLASS%', $class, $stub);

        return $this;
    }

    /**
     * Get the full namespace for a given class, without the class name.
     */
    protected function getNamespace(string $name): string
    {
        return trim(implode('\\', array_slice(explode('\\', $name), 0, -1)), '\\');
    }

    protected function replaceUses(string &$stub, array $uses): self
    {
        $str = '';
        foreach ($uses as $use) {
            $str .= "use $use;\n";
        }
        $str = substr($str, 0 , -1);
        $stub = str_replace(
            ['%USES%'],
            [$str],
            $stub
        );

        return $this;
    }

    public function printConfig(string $filename, array $config)
    {
        file_put_contents($filename, "<?php
declare(strict_types=1);

return " . VarDumper::create($config)->export() . ';');
    }

    protected function getColumns($className, $columns, $forceCasts): array
    {
        /** @var Model $model */
        $model = new $className();
        $dates = $model->getDates();
        $casts = [];
        if (!$forceCasts) {
            $casts = $model->getCasts();
        }

        foreach ($dates as $date) {
            if (!isset($casts[$date])) {
                $casts[$date] = 'datetime';
            }
        }

        foreach ($columns as $key => $value) {
            $columns[$key]['cast'] = $casts[$value['column_name']] ?? null;
        }

        return $columns;
    }
}