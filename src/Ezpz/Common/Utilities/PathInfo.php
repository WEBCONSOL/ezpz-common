<?php

namespace Ezpz\Common\Utilities;

class PathInfo
{
    private $uri;
    private $selectors=array();
    private $parts=array();
    private $nodeName='';
    private $file='';
    private $fileFullName='';
    private $ext='';
    private $selectorString='';
    private $path='';
    private $pathFullName='';
    private $minify=false;

    public function __construct($q)
    {
        $this->uri = trim($q, '\'/');
        $this->parts = new ListModel(explode('/', $this->uri));
        $this->ext = pathinfo($this->uri, PATHINFO_EXTENSION);
        if (!$this->ext || strlen($this->ext) > 4) {
            $this->ext = 'html';
        }

        // set selectors
        $parts = explode('.', $this->parts->last());
        $this->selectors = array();
        unset($parts[sizeof($parts)-1]);
        unset($parts[0]);
        if (sizeof($parts)) {
            foreach($parts as $part) {
                $this->selectors[] = $part;
            }
        }
        $this->selectors = new ListModel($this->selectors);

        $this->selectorString = implode('.', $this->selectors->getAsArray());
        $pattern = (sizeof($this->selectors)?'.':'').$this->selectorString.'.'.$this->ext;
        $this->nodeName = str_replace($pattern, '', $this->nodeName);
        $this->path = str_replace($pattern, '', $this->uri);
        $this->pathFullName = $this->ext ? str_replace('.'.$this->ext, '', $this->uri) : $this->uri;
        $this->file = $this->path.($this->ext?'.':'').$this->ext;
        $this->fileFullName = $this->path.($this->selectorString?'.':'').$this->selectorString.($this->ext?'.':'').$this->ext;

        $this->setMinify(in_array('min', $this->selectors->getAsArray()));
    }

    public function getUri(): string {return $this->uri;}
    public function getSelectors(): ListModel {return $this->selectors;}
    public function getParts(): ListModel {return $this->parts;}
    public function getNumSegments(): int {return $this->parts->count();}
    public function getNodeName(): string {return $this->nodeName;}
    public function getExtension(): string {return $this->ext;}
    public function getSelectorString(): string {return $this->selectorString;}
    public function getPath(): string {return $this->path;}
    public function getPathFullName(): string {return $this->pathFullName;}
    public function isMinify(): bool {return $this->minify;}
    public function getFile(): string {return $this->file;}
    public function getFileFullName(): string {return $this->fileFullName;}

    public function setMinify(bool $flag) {$this->minify=$flag;}
}
