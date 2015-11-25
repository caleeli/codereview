#!/usr/local/bin/php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

use codereview\CodeReview;

$cr = new CodeReview(
    __DIR__ . '/../rules/md_ruleset.xml',
    __DIR__ . '/../rules/cs_ruleset.xml',
    __DIR__ . '/../resources/cs_report.xsl',
    __DIR__ . '/../resources/phpcs.css',
    __DIR__ . '/../resources/diff-lines.sh'
);
$cr->setSilent(true);

unset($argv[0]);
$cmdMD = $cr->getPhpMdLine($argv);
echo $cmdMD."\n";
passthru($cmdMD);
