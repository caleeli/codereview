#!/usr/local/bin/php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

use codereview\CodeReview;

$command = isset($argv[1]) ? $argv[1] : '';

if ($command === '-i') {
    passthru('phpcs -i');
    return;
}

$xmlOutput = false;
foreach ($argv as $i => $arg) {
    if ($i > 0 && substr($arg, 0, 2) !== '--') {
        $path = $arg;
        $xmlOutput = true;
    }
}
if ($xmlOutput) {
    $dirPath = is_file($path) ? dirname($path) : $path;
    chdir($dirPath);
}

$cr = new CodeReview(
    __DIR__ . '/../rules/md_ruleset.xml',
    __DIR__ . '/../rules/cs_ruleset.xml',
    __DIR__ . '/../resources/cs_report.xsl',
    __DIR__ . '/../resources/phpcs.css',
    __DIR__ . '/../resources/diff-lines.sh'
);

$cr->setSilent($xmlOutput);

$cr->run();

if ($xmlOutput) {
    readfile('codereview.xml');
    $cr->deleteFiles();
} else {
    exec('xdg-open ' . $cr->getHtmlReport());
}
