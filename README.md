# project46481

趣玩旅行_微信端开发_后端开发
#后台开发
- 基于thinkphp3.2.3+aceadmin v1.4开发
- 线上服务路径 /home/wwwroot/admin
- 公共配置  App/Common/conf/config.php
- 各个module下有些各自的config.php文件
- 请求日志，nginx错误日志 /home/wwwroot
- php错误日志  /usr/local/php/var/log/php-fpm.log
- 代码内部日志  App/logs/
- 操作日志  数据库  qw\_admin\_log表
- 核心库类 ThinkPHP
- 定时任务，每月核算商家的上月金额结算 */1 3 1 * * curl 'http://admin.qu666.cn/Home/Crontab/index'



#后台初始化操作流程
- 导入数据库后，先用admin账号创建一个平台账号，后续操作请都已平台账号操作，admin只用于管理这个。