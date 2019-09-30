<?php

namespace app\common\utils\payment\kernel\supports;

class Helpers
{
    /**
     * Create a directory.
     *
     * @param string $dir
     * @param int $mode
     *
     * @return bool
     */
    public static function mkdirs($dir, $mode = 0777)
    {
        if (is_dir($dir) || @mkdir($dir, $mode)) {
            return true;
        }

        if (!self::mkdirs(dirname($dir), $mode)) {
            return false;
        }

        return @mkdir($dir, $mode);
    }

    /**
     * Generate log file.
     *
     * @param string $path
     * @param string $level
     *
     * @return string $file
     */
    public static function generateLogFile($path, $level)
    {
        self::mkdirs($path);
        $file = $path . $level . '_' . date('Y-m-d') . '.log';

        return $file;
    }
}
