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
    protected $htmlReport = 'codereview.html';

    public function __construct($mdRuleset, $csRuleset, $xslReport, $cssReport)
    {
        $this->mdRuleset = $mdRuleset;
        $this->csRuleset = $csRuleset;
        $this->xslReport = $xslReport;
        $this->cssReport = $cssReport;
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
        //echo "$cmdMD\n";
        passthru($cmdMD);
    }

    protected function phpcs($files)
    {
        $cmdCS = 'phpcs --standard=' . escapeshellarg($this->csRuleset) . ' --report-xml=' . escapeshellarg($this->xmlReport) . ' ' . implode(' ', $files);
        echo "Running CodeSniffer...\n";
        //echo "$cmdCS\n";
        passthru($cmdCS);
    }

    protected function generateReport()
    {
        // Open XML result
        $xml = new DOMDocument;
        $xml->load($this->xmlReport);

        $xsl = new DOMDocument;
        $xsl->load($this->xslReport);

        // Config xslt processor
        $proc = new XSLTProcessor;
        $proc->importStyleSheet($xsl);

        $html = $proc->transformToXML($xml);
        file_put_contents($this->htmlReport, $html);

        copy($this->cssReport, dirname($this->htmlReport).'/codereview.css');
        
        exec('xdg-open '.$this->htmlReport);
    }
    
    public function run(){
        $files = $this->loadFiles();
        $this->phpcs($files);
        $this->generateReport();
    }
}
