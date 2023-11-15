# Fast Hyperf

基于Hyperf框架二次封装的快速开发脚手架

```
说明：
本项目封装沿用了Java风格的对象风格，不喜者，请绕路
```

## 安装方式

普通安装
```
composer require link-cloud/fast-hyperf
```

模板安装

```
composer create-project link-cloud/fast-hyperf-demo demo
```

模板创建

## 常用命令

### 快速生成业务代码

```
php bin/hyperf.php gen:code -p /app -P default -a /api/v1
```

| 参数名 | 说明                                  | 示例              |
| --- |-------------------------------------|-----------------|
| -p | 代码路径                                | -p /app         |
| -P | 数据库连接池名称                            | -P default      |
| -a | 接口路径前缀                              | -a /api/v1      |
| -t | 模块名称，如果需要分模块，设置此参数 (比如小程序接口和管理后台接口) | -t api -t admin |

`
此命令依赖 `config/autoload/generate.php` 配置文件，可参考demo项目
`

生成的代码结构说明：

| 目录名称                    | 说明                                               |
|-------------------------|--------------------------------------------------|
| Application/Controller  | 接口控制器目录                                          |
| Constants         | 静态枚举类目录，建议再创建Status,Enums,Types三个子目录             |
| Constants/Errors        | 错误码目录                                            |
| Config                  | 生成器配置目录，可在此目录配置需要生成的实体类以及接口类                     |
| Entity                  | 接口实体类目录                                          |
| Logic                   | 逻辑层目录，业务代码在此编写 ，可对外暴露提供RPC接口，实现微服务调用             |
| Model                   | 数据库实体类目录                                         |
| Repository/Dao/Contracts | DAO接口定义                                          |
| Repository/Dao/MySQL    | DAO的MySQL访问实现                                    |
| Service                 | 服务层目录，基础的单表服务，可对外暴露提供RPC接口，实现微服务调用，也可以在此层定义缓存访问等 |


### 多语言翻译文件生成

可以将错误码和枚举类的翻译文件自动生成

```
php bin/hyperf.php translate:gen
```

`
此命令依赖 `config/autoload/generate.php` 配置文件，可参考demo项目
`

## 常用组件

### 枚举类

基于 `marc-mabe/php-enum` 封装的枚举类对象，方便枚举类传递和定义，再也不用到处问代码里的1234是什么意思了
枚举类定义

```php

use LinkCloud\Fast\Hyperf\Annotations\EnumMessage;
use LinkCloud\Fast\Hyperf\Framework\Entity\ErrorCode;

/**
 * @method static BaseStatus NORMAL()
 * @method static BaseStatus FROZEN()
 */
class BaseStatus extends BaseEnum
{
    #[EnumMessage(message: '正常')]
    public const NORMAL = 1;

    #[EnumMessage(message: '冻结')]
    public const FROZEN = 2;
}
```

使用：

```php
$status = BaseStatus::NORMAL();
var_dump($status->getValue()); // 获取数字值
var_dump($status->getMessage()); // 获取关联信息

// 函数定义时可指定类型
function judge(BaseStatus $status)
{
    
}

judge(BaseStatus::NORMAL()); // 函数传参

// 类的成员变量定义
class Object
{
    protected BaseStatus $status;
}
```

### 基本对象

框架定义了`BaseObject`对象，用于方便在数组和对象之间互相转换

定义一个对象

```php
use LinkCloud\Fast\Hyperf\Common\BaseObject;
use LinkCloud\Fast\Hyperf\Annotations\ArrayType;

class User extends BaseObject
{
    public string $userId;
    
    public string $userName;
    
    public UserStatus $status;
    
    #[ArrayType(valueType: UserBalance::class)]
    public array $balances;
}

class UserBalance extends BaseObject
{
    public string $amount;
}

// 从数组转换过来
$user = new User([
    'user_id' => 1,
    'user_name' => 'account',
    'status' => 1,
    'balances' => [
        ['amount' => '100'],
        ['amount' => '200'],
    ],
]);

var_dump($user->userId);
var_dump($user->balances);

// 转回数组
var_dump($user->toArray());
```

`ArrayType` 是一个新的注解，当需要声明一个指定类型的数组时，可以使用此注解定义

```php
    #[ArrayType(valueType: UserBalance::class)]
    public array $balances;
```


### Swagger接口文档

服务启动后，可直接访问 `http://127.0.0.1:9501/swagger` 查看swagger文档页面。
可通过配置文件关闭此功能
