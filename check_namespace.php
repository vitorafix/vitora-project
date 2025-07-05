<?php
$dir = __DIR__ . '/app';  // مسیر پروژه را تنظیم کن

$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

foreach ($files as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        // اگر فاصله یا خط خالی قبل از <?php باشد
        if (preg_match('/^\s*[^\S\r\n]*[^\?]/', $content)) {
            echo "File might have output before <?php: " . $file->getPathname() . PHP_EOL;
        }
        // چک کردن این که <?php اول خط است
        if (!preg_match('/^\s*<\?php/', $content)) {
            echo "File does not start properly with <?php: " . $file->getPathname() . PHP_EOL;
        }
    }
}
