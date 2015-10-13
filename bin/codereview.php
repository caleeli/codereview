#!/usr/local/bin/php
<?php
ob_start();
system('git status -s');
$status = ob_get_contents();
$list = explode("\n", $status);
ob_end_clean();
$files = [];
foreach ($list as $line) {
    $type = substr($line, 0, 2);
    $file = substr($line, 3);
    $extension = substr($file, -3);
    if (($type === ' M' || $type === ' A') && ($extension === 'php')) {
        $files[] = $file;
    }
}
$cmdMD = 'phpmd ' . implode(',', $files) . ' xml phpmdruleset.xml';
//echo "$cmdMD\n";
//passthru($cmdMD);

$cmdCS = 'phpcs --standard=cs_ruleset.xml --report-xml=codereview.xml ' . implode(' ', $files);
echo "Running CodeSniffer...\n";
//echo "$cmdCS\n";
passthru($cmdCS);


// Carga el fichero XML origen
$xml = new DOMDocument;
$xml->load('codereview.xml');

$xsl = new DOMDocument;
$xsl->load('cs_report.xsl');

// Configura el procesador
$proc = new XSLTProcessor;
$proc->importStyleSheet($xsl); // adjunta las reglas XSL

$html = $proc->transformToXML($xml);
file_put_contents('codereview.html', $html);
