## 工作中用到的Elasticsearch案例

目前已实现在php下根据请求的语句进行分词并根据分词结果在索引中检索数据。

使用composer安装verdor目录代码,包含Elasticsearch PHP官方代码库，以及Log4php代码
直接使用如下命令安装

```
composer install
```

#### 准备工作
[安装java环境](readme/3.md)，安装[Elasticsearch](readme/2.md)，[安装head数据展示插件](http://mobz.github.io/elasticsearch-head/)，[安装ik中文分词插件](readme/1.md)。


#### 安装完成之后
跟目录下的几个php文件都是测试文件，通过访问[/api/?a=get&keywords=整骨专家](/api/?a=get&keywords=整骨专家)测试结果是否正常。