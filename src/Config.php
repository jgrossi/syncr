<?php

namespace Syncr;

class Config
{
    private $filename;
    protected $content;

    public function __construct($filename)
    {
        $this->filename = $filename;
        $this->content = $this->extractContent($filename);
    }

    public function extractContent()
    {
        if (file_exists($this->filename)) {
            $json = file_get_contents($this->filename);
            return json_decode($json, true);
        }

        return false;
    }

    public function read($server, $parameter)
    {
        if ($this->content) {
            return $this->config[$server][$parameter];
        }
    }

    public function isValid()
    {
        $cfg = $this->content;

        return isset($cfg['remote']) and
            isset($cfg['remote']['server']) and
            isset($cfg['remote']['server']['host']) and
            isset($cfg['remote']['server']['username']) and
            isset($cfg['remote']['server']['password']) and
            isset($cfg['remote']['server']['ssh_port']) and
            isset($cfg['remote']['server']['path']) and
            isset($cfg['remote']['database']) and
            isset($cfg['remote']['database']['name']) and
            isset($cfg['remote']['database']['username']) and
            isset($cfg['remote']['database']['password']) and
            isset($cfg['local']) and
            isset($cfg['local']['server']) and
            isset($cfg['local']['server']['path']) and
            isset($cfg['local']['database']) and
            isset($cfg['local']['database']['name']) and
            isset($cfg['local']['database']['username']) and
            isset($cfg['local']['database']['password']);
    }
}