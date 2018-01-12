<?php
/**
 *
 * 版权所有：波波<www.bobolucy.com>
 * 作    者：波波<273719650@qq.com>
 * 日    期：2015-09-15
 * 版    本：1.0.0
 * 功能说明：配置文件。
 *
 **/
return array(
    'URL' => 'http://quwan.bobolucy.com', //网站根URL
    'COOKIE_SALT' => 'cookiejiami', //设置cookie加密密钥
    //数据库链接配置
    'DB_TYPE' => 'mysql', // 数据库类型
    'DB_HOST' => '127.0.0.1', // 服务器地址
    'DB_NAME' => 'quwan', // 数据库名
    'DB_USER' => 'quwan', // 用户名
    'DB_PWD' => 'www123456', // 密码
    'DB_PORT' => 3306, // 端口
    'DB_PREFIX' => 'qw_', // 数据库表前缀
    'DB_CHARSET' => 'utf8',      // 数据库编码默认采用utf8
    //备份配置
    'DB_PATH_NAME' => 'db',        //备份目录名称,主要是为了创建备份目录
    'DB_PATH' => './db/',     //数据库备份路径必须以 / 结尾；
    'DB_PART' => '20971520',  //该值用于限制压缩后的分卷最大长度。单位：B；建议设置20M
    'DB_COMPRESS' => '1',         //压缩备份文件需要PHP环境支持gzopen,gzwrite函数        0:不压缩 1:启用压缩
    'DB_LEVEL' => '9',         //压缩级别   1:普通   4:一般   9:最高
    'URL_MODEL'=>2,
    'UPLOAD_SITEIMG_QINIU' => array (
        'maxSize' => 5 * 1024 * 1024,//文件大小
        'rootPath' => './',
        'saveName' => array ('uniqid', ''),
        'driver' => 'Qiniu',
        'driverConfig' => array (
            'accessKey' => 'jVIkLNl8FzaeCK8H5AxPLYi49qlmc86572ITnbiM',
            'secrectKey' => 'A1JOHdGbg0IoxcoZYmoHtjfzbgwp51EDfzusMNkm',
            'domain' => 'ozg3kv9uz.bkt.clouddn.com',
            'bucket' => 'quwan',
        )
    ),
    'ORDER_CANCEL_TIME'=>7200,//订单自动取消时间
    'QcloudsmsApi'=>"1400050330",//腾讯短信api
    "QcloudsmsAppkey"=>"c40133722807cf92efd0b383472bef80",//腾讯短信appkey
        
    'DATA_CACHE_PREFIX' => 'yybredis_',//缓存前缀
    'DATA_CACHE_TYPE'=>'Redis',//默认动态缓存为Redis
    'REDIS_RW_SEPARATE' => false, //Redis读写分离 true 开启
    'REDIS_HOST'=>'127.0.0.1', //redis服务器ip，多台用逗号隔开；读写分离开启时，第一台负责写，其它[随机]负责读；
    'REDIS_PORT'=>'6371',//端口号
    'REDIS_TIMEOUT'=>'0',//超时时间
    'REDIS_PERSISTENT'=>false,//是否长连接 false=短连接
    'REDIS_AUTH'=>'',//AUTH认证密码
    
    //短信模板id
    'SENDmsg_tpl_id' => array(
        "login_id" => '64707',//后台验证码的
        "holiday_id" => '76123',//节日id
        "check_id" => '76126',//核销后商家id
        "order_hs_id" => '76129',//结款推送
    )
);