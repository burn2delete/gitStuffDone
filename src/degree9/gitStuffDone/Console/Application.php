<?php

namespace degree9\gitStuffDone\Console;

use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Composer\Command\Helper\DialogHelper as ComposerDialogHelper;
use degree9\gitStuffDone\Command\InitCommand;
use degree9\gitStuffDone\Command\BranchSemVerCommand;

class Application extends BaseApplication
{
    
    public function __construct()
    {
        
        if (function_exists('date_default_timezone_set') && function_exists('date_default_timezone_get')) {
            date_default_timezone_set(@date_default_timezone_get());
        }

        parent::__construct('gitStuffDone', '0.1.x-dev');
        
    }
    /**
     * Gets the default commands that should always be available.
     *
     * @return array An array of default Command instances
     */
    protected function getDefaultCommands()
    {
        $defaultCommands = parent::getDefaultCommands();

        $defaultCommands[] = new InitCommand();
        $defaultCommands[] = new BranchSemVerCommand();

        return $defaultCommands;
    }
    
    protected function getDefaultHelperSet()
    {
        $helperSet = parent::getDefaultHelperSet();

        $helperSet->set(new ComposerDialogHelper());

        return $helperSet;
    }
    
    protected function getDefaultInputDefinition()
    {
        return new InputDefinition(array(
            new InputArgument('command', InputArgument::REQUIRED, 'The command to execute'),

            new InputOption('--help',           '-h', InputOption::VALUE_NONE, 'Display this help message.'),
            //new InputOption('--quiet',          '-q', InputOption::VALUE_NONE, 'Do not output any message.'),
            //new InputOption('--verbose',        '-v', InputOption::VALUE_NONE, 'Increase verbosity of messages.'),
            new InputOption('--version',        '-V', InputOption::VALUE_NONE, 'Display this application version.'),
            //new InputOption('--ansi',           '',   InputOption::VALUE_NONE, 'Force ANSI output.'),
            //new InputOption('--no-ansi',        '',   InputOption::VALUE_NONE, 'Disable ANSI output.'),
            new InputOption('--no-interaction', '-n', InputOption::VALUE_NONE, 'Do not ask any interactive question.'),
        ));
    }
    
}