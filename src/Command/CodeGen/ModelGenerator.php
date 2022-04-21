<?php

declare(strict_types=1);

namespace LinkCloud\Fast\Hyperf\Command\CodeGen;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Database\Commands\Ast\ModelRewriteConnectionVisitor;
use Hyperf\Database\Commands\Ast\ModelUpdateVisitor;
use Hyperf\Database\Commands\ModelData;
use Hyperf\Database\Commands\ModelOption;
use Hyperf\Database\ConnectionResolverInterface;
use Hyperf\Database\Schema\Builder;
use Hyperf\Utils\CodeGen\Project;
use Hyperf\Utils\Str;
use LinkCloud\Fast\Hyperf\Command\Option\GenerateOption;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use PhpParser\PrettyPrinterAbstract;

class ModelGenerator extends BaseGenerator
{
    protected ConnectionResolverInterface $resolver;

    protected ConfigInterface $config;

    protected Parser $astParser;

    protected PrettyPrinterAbstract $printer;

    public function __construct(GenerateOption $option)
    {
        parent::__construct($option);
        $this->resolver = di()->get(ConnectionResolverInterface::class);
        $this->config = di()->get(ConfigInterface::class);
        $this->astParser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $this->printer = new Standard();
    }

    public function generate(?string $pool, ?string $table): array
    {
        $option = new ModelOption();
        $option->setPool($pool)
            ->setPath($this->getOption('commands.gen:model.path', $pool, 'app/Model'))
            ->setPrefix($this->getOption('prefix', $pool, ''))
            ->setInheritance($this->getOption('commands.gen:model.inheritance', $pool, 'Model'))
            ->setUses($this->getOption('commands.gen:model.uses', $pool, 'Hyperf\DbConnection\Model\Model'))
            ->setForceCasts($this->getOption('commands.gen:model.force_casts', $pool, false))
            ->setRefreshFillable($this->getOption('commands.gen:model.refresh_fillable', $pool, false))
            ->setTableMapping($this->getOption('commands.gen:model.table_mapping', $pool, []))
            ->setIgnoreTables($this->getOption('commands.gen:model.contain_tables', $pool, []))
            ->setWithComments($this->getOption('commands.gen:model.with_comments', $pool, false))
            ->setWithIde($this->getOption('commands.gen:model.with_ide', $pool, false))
            ->setVisitors($this->getOption('commands.gen:model.visitors', $pool, []))
            ->setPropertyCase($this->getOption('commands.gen:model.property_case', $pool));

        if ($table) {
            list($tableClass, $value) = $this->createModel($table, $option);
            return [
                $tableClass => $value
            ];
        } else {
            return $this->createModels($option);
        }
    }

    protected function createModels(ModelOption $option): array
    {
        $builder = $this->getSchemaBuilder($option->getPool());
        $tables = [];

        foreach ($builder->getAllTables() as $row) {
            $row = (array)$row;
            $table = reset($row);
            if ($this->isContainTable($table, $option)) {
                $tables[] = $table;
            }
        }

        $result = [];
        foreach ($tables as $table) {
            list($tableClass, $value) = $this->createModel($table, $option);
            $result[$tableClass] = $value;
        }
        return $result;
    }

    protected function createModel(string $table, ModelOption $option): array
    {
        $builder = $this->getSchemaBuilder($option->getPool());
        $table = Str::replaceFirst($option->getPrefix(), '', $table);
        $columns = $this->formatColumns($builder->getColumnTypeListing($table));

        $project = new Project();
        $class = $option->getTableMapping()[$table] ?? Str::studly(Str::singular($table));
        $class = $project->namespace($option->getPath()) . $class;
        $path = $this->basePath . '/' . $project->path($class);

        if (!file_exists($path)) {
            $this->mkdir($path);
            file_put_contents($path,
                $this->buildClass($table, $class, $this->getPrimaryKey($columns), $option));
        }

        $columns = $this->getColumns($class, $columns, $option->isForceCasts());

        $stms = $this->astParser->parse(file_get_contents($path));
        $traverser = new NodeTraverser();
        $traverser->addVisitor(make(ModelUpdateVisitor::class, [
            'class'   => $class,
            'columns' => $columns,
            'option'  => $option,
        ]));
        $traverser->addVisitor(make(ModelRewriteConnectionVisitor::class, [$class, $option->getPool()]));
        $data = make(ModelData::class, ['class' => $class, 'columns' => $columns]);
        foreach ($option->getVisitors() as $visitorClass) {
            $traverser->addVisitor(make($visitorClass, [$option, $data]));
        }
        $stms = $traverser->traverse($stms);
        $code = $this->printer->prettyPrintFile($stms);

        file_put_contents($path, $code);

        $comment = $builder->getConnection()->select(sprintf("select TABLE_COMMENT from information_schema.tables where table_name = '%s' and table_schema = '%s';",
            $builder->getConnection()->getTablePrefix() . $table, $builder->getConnection()->getDatabaseName()));

        return [
            $class,
            [
                'columns'     => $columns,
                'comment'     => $comment[0]->TABLE_COMMENT,
                'primary_key' => $this->getPrimaryKey($columns)
            ]
        ];
    }

    protected function getOption(string $key, string $pool = 'default', $default = null)
    {
        return $this->config->get("databases.$pool.$key", $default);
    }

    protected function getSchemaBuilder(string $poolName): Builder
    {
        $connection = $this->resolver->connection($poolName);
        return $connection->getSchemaBuilder();
    }

    /**
     * Format column's key to lower case.
     */
    protected function formatColumns(array $columns): array
    {
        return array_map(function ($item) {
            return array_change_key_case($item, CASE_LOWER);
        }, $columns);
    }

    protected function getPrimaryKey(array $columns): string
    {
        $primaryKey = 'id';
        foreach ($columns as $column) {
            if ($column['column_key'] === 'PRI') {
                $primaryKey = $column['column_name'];
                break;
            }
        }
        return $primaryKey;
    }

    /**
     * Build the class with the given name.
     */
    protected function buildClass(string $table, string $name, string $primaryKey, ModelOption $option): string
    {
        $stub = file_get_contents(__DIR__ . '/stubs/Model.stub');

        return $this->replaceNamespace($stub, $name)
            ->replaceInheritance($stub, $option->getInheritance())
            ->replaceConnection($stub, $option->getPool())
            ->replaceUses($stub, [$option->getUses()])
            ->replaceClass($stub, $name)
            ->replacePrimaryKey($stub, $primaryKey)
            ->replaceTable($stub, $table);
    }

    protected function isContainTable(string $table, ModelOption $option): bool
    {
        if (empty($option->getIgnoreTables())) {
            return true;
        }

        if (in_array($table, $option->getIgnoreTables())) {
            return true;
        }

        return $table === $this->config->get('databases.migrations', 'migrations');
    }

    protected function replaceConnection(string &$stub, string $connection): self
    {
        $stub = str_replace(
            ['%CONNECTION%'],
            [$connection],
            $stub
        );

        return $this;
    }

    protected function replaceInheritance(string &$stub, string $inheritance): self
    {
        $stub = str_replace(
            ['%INHERITANCE%'],
            [$inheritance],
            $stub
        );

        return $this;
    }


    /**
     * Replace the table name for the given stub.
     */
    protected function replaceTable(string $stub, string $table): string
    {
        return str_replace('%TABLE%', $table, $stub);
    }

    protected function replacePrimaryKey(string &$stub, string $primaryKey): self
    {
        $stub = str_replace(['%PRIMARY_KEY%'], [$primaryKey], $stub);

        return $this;
    }
}