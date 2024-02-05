<?php

namespace App\Entity;

use Qwwwest\Namaskar\MemPad;
use Qwwwest\Namaskar\Kernel;
use Qwwwest\Namaskar\PageDataBuilder;

class PageEntity
{
    private $dataFolder = null;
    private $mempadFile = null;
    private $mempad = null;
    private $project;
    private $publicFolder;

    public function init(string $project): bool
    {

        $this->project = basename($project);
        $zen = Kernel::service('ZenConfig');

        $dataFolder = $zen('folder.data');
        $publicFolder = $zen('folder.public');
        $project = $this->project = basename($project);

        if (is_dir("$dataFolder/sites/$project/"))
            $this->dataFolder = "$dataFolder/sites/$project/";
        elseif (is_dir("$publicFolder/media/#data"))
            $this->dataFolder = "$publicFolder/media/#data";
        else
            return false;


        if (is_file(realpath("$this->dataFolder/../$project.lst")))
            $this->mempadFile = realpath("$this->dataFolder/../$project.lst");
        elseif (is_file(realpath("$this->dataFolder/$project.lst")))
            $this->mempadFile = realpath("$this->dataFolder/$project.lst");
        elseif (is_file("$this->dataFolder/mempad.lst"))
            $this->mempadFile = "$this->dataFolder/mempad.lst";
        else
            return false;


        if (!$zen->addFile("$this->dataFolder/site.ini", true)) {
            // no ini file. we use minemalistic values.
            $zen->parseString("
[site]
name: '$project' 
domain: '$project' 
language: 'en'
theme: 'bootstrap5'
auto.title: 'yes'
          
                ");
        }

        $zen->addFile("$this->dataFolder/theme.ini", true);
        $zen->addFile("$this->dataFolder/data.ini", true);


        return true;
    }



    public function buildContent()
    {
        $zen = Kernel::service('ZenConfig');
        $this->mempad = new MemPad($this->mempadFile, '');
        $zen('MemPad', $this->mempad);

        $absroot = $zen('absroot');

        //  $zen('asset', "$absroot/sites/$this->project/asset");
        $zen('asset', "$absroot/asset");
        $zen('media', "$absroot/media");
        $zen('homepath', "$absroot/sites/$this->project");

    }



}