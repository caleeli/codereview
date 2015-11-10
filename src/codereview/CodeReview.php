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
    protected $silent = false;

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
        if (!$this->silent) {
            echo "Running MessDetector...\n";
        }
        ob_start();
        passthru($cmdMD);
        $errorsWarnings = ob_get_contents();
        ob_end_clean();
        return $errorsWarnings;
    }

    protected function phpcs($files)
    {
        $cmdCS = 'phpcs --standard=' . escapeshellarg($this->csRuleset) .
            ' --standard=PSR2 --report-xml=' . escapeshellarg($this->xmlReport) . ' ' . implode(' ', $files);
        if (!$this->silent) {
            echo "Running CodeSniffer...\n";
        }
        ob_start();
        passthru($cmdCS);
        $errorsWarnings = ob_get_contents();
        ob_end_clean();
        return $errorsWarnings;
    }

    protected function generateReport($errorsWarnings)
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
        $proc->setParameter('', 'errorsWarnings', $errorsWarnings);

        $html = $proc->transformToXML($xml);
        file_put_contents($this->htmlReport, $html);

        copy($this->cssReport, dirname($this->htmlReport) . '/codereview.css');
    }

    protected function getModifiedLines()
    {
        if (!$this->silent) {
            echo "Checking changes...\n";
        }
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
                $this->markDiffIssues($file, $modifiedLinesFile, $diffErrors, $diffWarnings);
            }
            $file->setAttribute('diff_errors', $diffErrors);
            $file->setAttribute('diff_warnings', $diffWarnings);
        }
    }

    private function markDiffIssues($file, $modifiedLinesFile, &$diffErrors, &$diffWarnings)
    {
        foreach ($file->childNodes as $issue) {
            if ($issue instanceof \DOMText) {
                continue;
            }
            $line = $issue->getAttribute("line");
            $isNew = 'false';
            if (array_search($line, $modifiedLinesFile) !== false) {
                $isNew = 'true';
                if ($issue->nodeName === 'error') {
                    $diffErrors++;
                } elseif ($issue->nodeName === 'warning') {
                    $diffWarnings++;
                }
            }
            $issue->setAttribute('is_new', $isNew);
        }
    }

    public function run()
    {
        $files = $this->loadFiles();
        $errorsWarnings = $this->phpcs($files);
        $this->generateReport($errorsWarnings);
    }

    public function setSilent($silent)
    {
        $this->silent = $silent;
    }

    public function getHtmlReport()
    {
        return $this->htmlReport;
    }

    public function deleteFiles()
    {
        unlink($this->xmlReport);
        unlink($this->htmlReport);
        unlink(dirname($this->htmlReport) . '/codereview.css');
    }
}
