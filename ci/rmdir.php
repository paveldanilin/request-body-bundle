
<?php
function rrmdir(string $dir): void
{
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object !== '.' && $object !== '..') {
                if (is_dir($dir . DIRECTORY_SEPARATOR . $object) && !is_link($dir . DIRECTORY_SEPARATOR . $object)) {
                    rrmdir($dir . DIRECTORY_SEPARATOR . $object);
                } else {
                    @unlink($dir . DIRECTORY_SEPARATOR . $object);
                }
            }
        }
        @rmdir($dir);
    }
}
$rm_dir = $argv[1] ?? null;
if (empty($rm_dir)) {
    throw new Exception('Directory is not defined');
}
rrmdir($rm_dir);
