<?php

namespace Src\Helpers;

if (!defined('ABSPATH')) {
    exit; // Silence is golden
}

class LeadhubErrorLog
{
    public static function leadhub_error_log($message)
    {
        $backtrace = debug_backtrace();
        $caller = $backtrace[0];
        $file = $caller['file'];
        $line = $caller['line'];
        $function = $caller['function'];
        error_log("{$file}:{$line} ({$function}) - {$message}");
    }
}
