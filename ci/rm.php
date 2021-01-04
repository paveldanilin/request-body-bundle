<?php

$src_file = $argv[1] ?? null;

$ignore_file_absence = $argv[2] ?? false;
if ('y' === $ignore_file_absence) {
    $ignore_file_absence = true;
} else {
    $ignore_file_absence = false;
}

if (empty($src_file)) {
    throw new Exception('Source file is not defined');
}

if (!file_exists($src_file) && false === $ignore_file_absence) {
    throw new \RuntimeException("File not found [$src_file]");
}

@unlink($src_file);
