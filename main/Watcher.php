<?php namespace WitPact\Utility;

use Composer\Script\Event;
use Composer\Util\Filesystem;

/**
 * 观察器
 * 
 * 监听文件的改动并同步。
 */
class Watcher {
    private const MAP = [
        'witpact-plugin' => 'type:wordpress-plugin',
        'witpact-theme' => 'type:wordpress-theme',
        'project' => 'type:wordpress-theme',
    ];

    /**
     * 监视目录并及时同步。
     * 
     */
    public static function watch(Event $event) {
        $io = $event->getIO();
        $composer = $event->getComposer();
        $package = $composer->getPackage();
        $extra = $package->getExtra();
        $source = realpath($extra['witpact-dist-dir']);

        // 获取命令参数并设置安装
        $options = self::parseOptions($event->getArguments());
        $interval = $options['interval'] ?? 1;
        $target = $options['target'] ?? self::getTarget($package);
        if (!is_dir($target)) mkdir($target, 0777, true);
        $target = realpath($target);
        $filesystem = new Filesystem;
        $filesystem->copy($source, $target);

        // 获取初始状态并监听
        $io->write('source: '.$source);
        $io->write('target: '.$target);
        $start = strlen($source);
        $old = self::signFolder($source);
        while (true) {
            $now = self::signFolder($source);

            // 比对并同步
            foreach (array_diff_assoc($now, $old) as $path => $time) {
                $aim = $target.substr($path, $start);
                $io->write(date('[Y-m-d h:i:s]', $time).$path);
                $io->write('=> '.$aim);
                $filesystem->copy($path, $aim);
            }
            $old = $now;
            sleep($interval);
        }
    }

    /**
     * 把目录里的文件全部标识出来。
     * 
     * @param string $directory: 目录路径
     * @return array: 一个扁平化的路径数组
     */
    public static function signFolder($directory) {
        $result = [];
        $handle = opendir($directory);
        while (false != ($name = readdir($handle))) {
            if ('.' == $name or '..' == $name) continue;
            $path = $directory.DIRECTORY_SEPARATOR.$name;
            if (is_dir($path)) $result += self::signFolder($path);
            else $result[$path] = filemtime($path);
        }
        return $result;
    }

    /**
     * 解析命令行参数。
     * 
     * @param string $argument 命令行参数。
     * @return array: 命令行数组。
     */
    public static function parseOptions($argument) {
        $result = [];
        foreach ($argument as $option) {
            $i = explode('=', $option);
            $result[ltrim($i[0], '-')] = $i[1] ?? null;
        }
        return $result;
    }

    /**
     * 得到目标路径。
     * 
     * @param $package: 包接口。
     */
    public static function getTarget($package) {
        $extra = $package->getExtra();
        $type = $package->getType();
        $name = $extra['witpact-alias'] ?? $package->getName();
        foreach ($extra['installer-paths'] as $path => $names) {
            if (in_array(self::MAP[$type], $names)) {
                return str_replace('{$name}', $name, $path);
            }
        }
        return false;
    }
}