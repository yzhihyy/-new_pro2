<?php

namespace app\common\command;

use app\common\command\tasks\LiveShowCheck;
use app\common\command\tasks\UserFollowAdd;
use app\common\utils\date\DateHelper;
use think\console\{
    Command, Input, Output
};
use think\console\input\{
    Argument, Option
};
use Workerman\Lib\Timer;
use Workerman\Worker;
use app\common\command\tasks\ThemeActivity;

class WorkerTask extends Command
{
    use ThemeActivity;
    use UserFollowAdd;
    use LiveShowCheck;
    /**
     * @var string
     */
    protected static $logPath = '/workerTask';

    /**
     * 进程数必须大于等于2
     *
     * @var int
     */
    private $workerCount = 3;

    /**
     * 配置指令
     */
    protected function configure()
    {
        $this->setName('workerTask')
        ->addArgument('action', Argument::OPTIONAL, 'start|stop|restart|reload|status')
        ->addOption('daemon', 'd', Option::VALUE_NONE, 'Run the workerman server in daemon mode.')
        ->setDescription('Workerman Server for Task');
    }

    /**
     * 执行指令
     *
     * @param Input  $input
     * @param Output $output
     *
     * @return bool
     */
    protected function execute(Input $input, Output $output)
    {
        $action = $input->getArgument('action');
        if (DIRECTORY_SEPARATOR !== '\\') {
            if (!in_array($action, ['start', 'stop', 'reload', 'restart', 'status'])) {
                $output->writeln("<error>Invalid argument action:{$action}, Expected start|stop|restart|reload|status|connections .</error>");
                return false;
            }

            global $argv;
            array_shift($argv);
        } elseif ('start' != $action) {
            $output->writeln("<error>Not Support action:{$action} on Windows.</error>");
            return false;
        }

        // 初始化任务
        $this->taskInit();
        // 运行任务
        $this->taskRun();
    }

    /**
     * Task init.
     */
    private function taskInit()
    {
        // 主题活动初始化
        $this->themeActivityInit();
    }

    /**
     * Task run.
     */
    private function taskRun()
    {
        $worker = new Worker();
        $worker->count = $this->workerCount;
        $worker::$logFile = config('app.log_server_root_path') . static::$logPath . DIRECTORY_SEPARATOR . 'workerTask.log';

        $worker->onWorkerStart = function ($worker) {
            // 第1进程用于监听主题活动
            if ($worker->id === 0) {
                $this->themeActivityListen();
            // 第2个进程用于处理主题活动
            }elseif($worker->id === 1) {
                $this->themeActivityHandle();
            // 第3个进程用于监听每分钟查看视频直播是否已超过2分钟，超过就强制结束并发推送告知客户端退出
            }elseif($worker->id === 2) {
                $second = 60;//60秒检查一次
                Timer::add($second, function(){
                    $this->liveShowHandle();
                });

            }
        };

        Worker::runAll();
    }

    /**
     * GetRedis.
     *
     * @return \Redis
     */
    protected static function getRedis()
    {
        try {
            $config = config('redis.');
            $redis = new \Redis();
            $redis->connect($config['host'], $config['port']);

            if (!empty($config['auth']) && !$redis->auth($config['auth'])) {
                throw new \Exception("redis password verification failed");
            }

            if ($redis->ping() != '+PONG') {
                throw new \Exception("redis connection is not available, ping={$redis->ping()}");
            }

            return $redis;
        } catch (\Exception $e) {
            static::generateLog("Redis异常：{$e->getMessage()}");
        }
    }

    /**
     * 生成日志
     *
     * @param string $msg
     */
    protected static function generateLog(string $msg)
    {
        generateCustomLog($msg, static::$logPath);
    }

    /**
     * 获取正数的随机数
     * @param $num
     * @return int
     */
    protected static function getRandNum($num): int
    {
        list($min, $max) = explode(',', $num, 2);
        if($max >= $min && $min > 0){
            return mt_rand($min, $max);
        }
        return 0;


    }
}