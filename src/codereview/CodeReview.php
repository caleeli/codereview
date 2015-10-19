<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace codereview;

use \DOMDocument;
use \XSLTProcessor;

/**
 * Description of CodeReview
 *
 * @author David Callizaya <davidcallizaya@gmail.com>
 */
class CodeReview
{

    protected $xmlReport = 'codereview.xml';
    protected $mdRuleset;
    protected $csRuleset;
    protected $xslReport;
    protected $cssReport;
    protected $diffLines;
    protected $htmlReport = 'codereview.html';

    public function __construct($mdRuleset, $csRuleset, $xslReport, $cssReport, $diffLines)
    {
        $this->mdRuleset = $mdRuleset;
        $this->csRuleset = $csRuleset;
        $this->xslReport = $xslReport;
        $this->cssReport = $cssReport;
        $this->diffLines = $diffLines;
    }

    protected function loadFiles()
    {
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
        return $files;
    }

    protected function phpmd($files)
    {
        $cmdMD = 'phpmd ' . implode(',', $files) . ' xml ' . escapeshellarg($this->mdRuleset);
        echo "Running MessDetector...\n";
        passthru($cmdMD);
    }

    protected function phpcs($files)
    {
        $cmdCS = 'phpcs --standard=' . escapeshellarg($this->csRuleset) . ' --report-xml=' . escapeshellarg($this->xmlReport) . ' ' . implode(' ', $files);
        echo "Running CodeSniffer...\n";
        passthru($cmdCS);
    }

    protected function generateReport()
    {
        // Open XML result
        $xml = new DOMDocument;
        $xml->load($this->xmlReport);

        $modifiedLines = $this->getModifiedLines();
        $this->diffWithGit($xml, $modifiedLines);

        $xsl = new DOMDocument;
        $xsl->load($this->xslReport);

        // Config xslt processor
        $proc = new XSLTProcessor;
        $proc->importStyleSheet($xsl);

        $html = $proc->transformToXML($xml);
        file_put_contents($this->htmlReport, $html);

        copy($this->cssReport, dirname($this->htmlReport) . '/codereview.css');

        exec('xdg-open ' . $this->htmlReport);
    }

    protected function getModifiedLines()
    {
        echo "Checking changes...\n";
        $cmdDiff = 'git diff | ' . $this->diffLines;
        ob_start();
        passthru($cmdDiff);
        $cmdDiffResult = ob_get_contents();
        ob_end_clean();
        $cmdDiffResultLines = explode("\n", $cmdDiffResult);

        $modifiedLines = [];
        foreach ($cmdDiffResultLines as $line) {
            if (empty($line)) {
                continue;
            }
            list($file, $lineNumber, $content) = explode(":", $line, 3);
            if (substr($content, 0, 1) === '+') {
                $modifiedLines[$file][] = $lineNumber;
            }
        }
        return $modifiedLines;
    }

    protected function diffWithGit(DOMDocument $xml, $modifiedLines)
    {
        $diffErrors = 0;
        $diffWarnings = 0;
        $path = realpath('.');
        foreach ($xml->getElementsByTagName('file') as $file) {
            $fileRelative = substr($file->getAttribute('name'), strlen($path) + 1);
            $modifiedLinesFile = $modifiedLines[$fileRelative];
            if (isset($modifiedLinesFile)) {
                foreach ($file->childNodes as $issue) {
                    if ($issue instanceof \DOMText) {
                        continue;
                    }
                    $line = $issue->getAttribute("line");
                    if (array_search($line, $modifiedLinesFile) !== false) {
                        $issue->setAttribute('is_new', 'true');
                        if ($issue->nodeName === 'error') {
                            $diffErrors++;
                        } elseif ($issue->nodeName === 'warning') {
                            $diffWarnings++;
                        }
                    } else {
                        $issue->setAttribute('is_new', 'false');
                    }
                }
            }
            $file->setAttribute('diff_errors', $diffErrors);
            $file->setAttribute('diff_warnings', $diffWarnings);
        }
    }

    public function run()
    {
        $files = $this->loadFiles();
        $this->phpcs($files);
        $this->generateReport();
    }
}
