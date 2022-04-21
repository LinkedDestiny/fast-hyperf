# Fast Hyperf

基于Hyperf框架二次封装的快速开发脚手架

```
说明：
本项目封装沿用了Java风格的对象风格，不喜者，请绕路
```

## 安装方式

普通安装
```
composer requrie link-cloud/fast-hyperf
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