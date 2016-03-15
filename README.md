# LaravelORM
把强大的Laravel ORM抽离出来，可以在任何地方方便快速使用Laravel ORM以及DB门面类和Schema门面类
需要提前安装好composer，并且设定好环境变量。

使用方法：

1、修改config文件夹中的db配置文件，也可以另外自己设定路径。

2、把Jiyi文件夹放到你项目的APPPATH中去，然后把composer.json放到根目录，执行composer update。

3、在项目的入口文件处（一般是index.php）加入require '../vendor/autoload.php';如果是其他框架，要放到其他框架autoload之前，防止影响框架核心类，比如CI框架应该是这样的顺序。
require BASEPATH.'../vendor/autoload.php';
require_once BASEPATH.'core/CodeIgniter.php';


然后写好自己的Model就可以使用强大的LaravelORM了，同时支持多个DB切换，具体看BaseModel里面封装的方法。使用之前要先BaseModel::init(); 初始化,如果想全局，就把BaseModel::init();放到初始化文件中.


举例：（具体的到BaseModel中看注解即可）
BaseModel::init();
Page::find(1);//需要有page数据表，并且id为主键

//切换国家
BaseModel::init();
BaseModel::setDatabase($ccode);//$ccode为当前国家的代号

BaseModel::hasTable($spec_tbname);
BaseModel::hasColumns($table, $column);



