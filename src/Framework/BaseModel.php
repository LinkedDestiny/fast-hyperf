<?php
declare(strict_types=1);

namespace LinkCloud\Fast\Hyperf\Framework;

use Hyperf\Database\Model\Builder;
use Hyperf\Database\Query\Expression;
use Hyperf\DbConnection\Model\Model;
use Hyperf\Utils\Arr;
use LinkCloud\Fast\Hyperf\Constants\SoftDeleted;

/**
 * @property int enable
 */
class BaseModel extends Model
{
    const CREATED_AT = 'create_at';

    const UPDATED_AT = 'update_at';

    protected ?string $dateFormat = 'U';

    /**
     * @param array $condition
     * @param array $field
     * @param bool $forUpdate
     * @return static|null
     */
    public static function findOne(array $condition, array $field = ['*'], bool $forUpdate = false): ?static
    {
        $query = static::buildByCondition($condition);
        if ($forUpdate) {
            $query->lockForUpdate();
        }
        return $query->first($field);
    }

    /**
     * @param array $condition
     * @param array $data
     * @return int
     */
    public static function updateCondition(array $condition, array $data): int
    {
        $query = static::buildByCondition($condition);
        return $query->update($data);
    }

    /**
     * @param array $condition
     * @return int
     */
    public static function countCondition(array $condition): int
    {
        $query = static::buildByCondition($condition);
        return $query->count();
    }

    /**
     * @param array $condition
     * @param bool $forceDelete
     * @return int
     */
    public static function removeCondition(array $condition, bool $forceDelete = false): int
    {
        $query = static::buildByCondition($condition);
        if ($forceDelete) {
            return $query->delete();
        } else {
            return $query->update([
                'enable' => SoftDeleted::DISABLE
            ]);
        }
    }

    public static function betweenTime(Builder $model, string $field, array $createTime)
    {
        $model->where(function (Builder $builder) use ($field, $createTime) {
            if ($createTime['start'] > 0) {
                $builder->where($field, '>=', $createTime['start']);
            }
            if ($createTime['end'] > 0) {
                $builder->where($field, '<', $createTime['end']);
            }
        });
    }

    /**
     * @param array $condition
     * @return Builder
     */
    public static function buildByCondition(array $condition): Builder
    {
        $query = static::buildQuery();
        foreach ($condition as $key => $value) {
            if (is_int($key)) {
                $query->where($condition);
                break;
            }
            if (is_array($value)) {
                $query->whereIn($key, $value);
            } else {
                $query->where($key, $value);
            }
        }
        return $query;
    }

    /**
     * insert or update a record
     * @param array $values
     * @param array $value
     * @return bool
     */
    public static function insertOrUpdate(array $values, array $value): bool
    {
        $query = static::buildQuery();
        $builder = $query->getQuery();   // 查询构造器
        $connection = $query->getConnection();   // 数据库连接
        $grammar = $builder->getGrammar();  // 语法器
        // 编译插入语句
        $insert = $grammar->compileInsert($builder, $values);
        // 编译重复后更新列语句。
        $update = collect($values)->map(function ($value, $key) use ($grammar) {
            return $grammar->wrap($key) . ' = ' . $grammar->parameter($value);
        })->implode(', ');
        // 构造查询语句
        $query = $insert . ' on duplicate key update ' . $update;
        // 组装sql绑定参数
        $bindings = array_merge_recursive($values, [$value]);
        $bindings = array_values(array_filter(Arr::flatten($bindings, 1), function ($binding) {
            return !$binding instanceof Expression;
        }));
        // 执行数据库查询
        return $connection->insert($query, $bindings);
    }

    /**
     * @param mixed $value
     * @return string
     */
    protected function asJson(array $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @param mixed $value
     * @return string
     */
    public function fromDateTime(mixed $value): string
    {
        return strval($this->asTimestamp($value));
    }

    public static function buildQuery(): Builder
    {
        $model = new static();
        return $model->newQuery();
    }
}
