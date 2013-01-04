<?php

namespace degree9\gitStuffDone\PHPGit;

use PHPGit_Repository;

class Repository extends PHPGit_Repository
{
    
    public function __construct($dir, $debug = false, array $options = array())
    {
        parent::__construct($dir, $debug, $options);
        
        $this->gitVersion = $this->checkGitVersion();
    }

    public function git($commandString)
    {
        // clean commands that begin with "git "
        
        $remove = array('/^git\s/');
        
        if(version_compare($this->gitVersion, '1.8', '<'))
        {
        
            $remove[] = '/--local/';
        
        }
        
        $commandString = preg_replace($remove, '', $commandString);

        $commandString = $this->options['git_executable'].' '.$commandString;

        $command = new $this->options['command_class']($this->dir, $commandString, $this->debug);

        return $command->run();
    }
    
    public function checkGitVersion()
    {
        
        $commandString = $this->options['git_executable'].' '.'--version';

        $command = new $this->options['command_class']($this->dir, $commandString, $this->debug);

        $rawVersion = $command->run();
        
        $version = preg_replace(array('/^git\s/', '/^version\s/'), '', $rawVersion);
        
        return $version;
        
    }
    
}