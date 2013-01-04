<?php

namespace degree9\gitStuffDone\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\ProcessBuilder;
use degree9\gitStuffDone\PHPGit\Repository as PHPGit_Repository;
use PHPGit_Configuration;

class InitCommand extends Command
{
    
    private $versionArgDefault = '0.1.0';
    
    protected function configure()
    {
        $this
            ->setName('init')
            ->setDescription('Initialize gitsd project')
            ->addOption(
               'force',
               '-f',
               InputOption::VALUE_NONE,
               'Force reinitialization'
            )
            ->addOption(
               'uninit',
               '-u',
               InputOption::VALUE_NONE,
               'Uninitialize gitsd project'
            )
            ->addOption(
               'default',
               '-d',
               InputOption::VALUE_NONE,
               'Use default version'
            )
            ->addArgument(
                'version',
                InputArgument::OPTIONAL,
                'Version string; in the format of x.y.z',
                null
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //set function variables
        $dialog = $this->getHelperSet()->get('dialog');
        $formatter = $this->getHelperSet()->get('formatter');
        $procFinder = new ExecutableFinder();
        $gitBin = $procFinder->find('git');
        
        //attempt to create PHPGit_Repository
        try {
            
            $gitRepo = new PHPGit_Repository(getcwd(), false, array('git_executable'  => $gitBin));
        
        //catch invalid repo exception
        } catch (InvalidGitRepositoryDirectoryException $e)
        {
            //prompt for repo creation
            if($dialog->askConfirmation($output, $dialog->getQuestion('The current directory is not a git repo, create one', 'yes', '?'), true))
            {
                
                $gitRepo->create(getcwd());
                
            }
        }
        
        //create PHPGit_Configuration object
        $gitConfig = new PHPGit_Configuration($gitRepo);
        
        //not a gitsd project and user attempting uninit
        if($input->getOption('uninit') && !$gitConfig->get('gitsd.init', false))
        {
            
            $errorMessages = array('Deinitialization Error!', 'Unable to deinitialize a non-project, gitStuffDone has not been initialized here!');
            $formattedBlock = $formatter->formatBlock($errorMessages, 'error', true);
            $output->writeln($formattedBlock);
            die();
            
        //is a gitsd project and user attempting uninit
        } elseif($input->getOption('uninit') && $gitConfig->get('gitsd.init', false))
        {
            
           $gitConfig->remove('gitsd.init');
           die();
           
        //is gitsd project and not force reinit
        } elseif(!$input->getOption('force') && $gitConfig->get('gitsd.init', false))
        {
            $errorMessages = array();
            $errorMessages[] = 'Initialization Error!';
            $errorMessages[] = 'gitStuffDone has already been initialized here!';
            $errorMessages[] = 'Use [-f||--force] to force reinitialization or [-u||--uninit] to deinitialize.';
            $formattedBlock = $formatter->formatBlock($errorMessages, 'error', true);
            $output->writeln($formattedBlock);
            die();
        
        //is gitsd project and force reinit OR is not gitsd project and no uninit
        } else
        {
            //is interactive, use defaults not set, version arg null
            if($input->isInteractive() && !$input->getOption('default') && $input->getArgument('version') == null)
            {
                
                $output->writeln('<info>gitStuffDone uses Semantic Versioning (SemVer) for it\'s branching, in the format x.y.z</info>');
                $output->writeln('');
                
                $major = $dialog->ask($output, $dialog->getQuestion('Please enter initial Major Version number (X.y.z)', '0', ':'), '0');
                $minor = $dialog->ask($output, $dialog->getQuestion('Please enter initial Minor Version number (x.Y.z)', '1', ':'), '1');
                $patch = '0';
                
            //use defaults not set, version arg not null
            } elseif(!$input->getOption('default') && $input->getArgument('version') != null)
            {
                
                $version = explode('.', $input->getArgument('version'));
                $major = $version[0];
                $minor = $version[1];
                $patch = (count($version) > 2) ? $version[2] : '0';
                
            //use defaults set
            } elseif($input->getOption('default'))
            {
                
                $input->setArgument('version', $this->versionArgDefault);
                
                $version = explode('.', $input->getArgument('version'));
                $major = $version[0];
                $minor = $version[1];
                $patch = (count($version) > 2) ? $version[2] : '0';
            
            //not interactive, use defaults not set, version arg null
            } elseif(!$input->isInteractive() && !$input->getOption('default') && $input->getArgument('version') == null)
            {
                
                $errorMessages = array();
                $errorMessages[] = 'Initialization Error!';
                $errorMessages[] = 'Invalid argument use.';
                $errorMessages[] = '[version] argument not provided and [-d||--default] not set, unable to prompt for version input [-n||--no-interaction] is set';
                $formattedBlock = $formatter->formatBlock($errorMessages, 'error', true);
                $output->writeln($formattedBlock);
                die();
                
            }
            
            //set gitsd version config
            $version = $major . '.' . $minor .'.' . $patch;
            $gitConfig->set('gitsd.init', true);
            $gitConfig->set('gitsd.version.initial', $version);
            $gitConfig->set('gitsd.version.major', $major);
            $gitConfig->set('gitsd.version.minor', $minor);
            $gitConfig->set('gitsd.version.patch', $patch);
        
            //display initial version
            $output->writeln('');
            $output->writeln('<info>Your initial project version:</info><comment> ' . $version . '</comment>');
            
            //existing branch list
            $output->writeln('');
            $output->writeln('<info>Your project has the following branches:</info>');
            $existingBranches = $gitRepo->getBranches();
            foreach($existingBranches as $branch)
            {
            
            $output->writeln('<comment>' . $branch . '</comment>');
            
            }
        
            //new branch list
            $output->writeln('');
            $newBranches = array();
            $newBranches[] = 'v' . $major . '.' . $minor;
            $newBranches[] = 'v' . $major . '.' . $minor . '-dev';
        
            //merge existing and new branch list
            $createBranches = array_diff($newBranches, $existingBranches);
        
            //create non-existing branches
            if (count($createBranches) > 0 )
            {
        
                $output->writeln('<info>gitStuffDone will create the following branches:</info>');
        
                foreach($createBranches as $branch)
                {
            
                    $output->writeln('');
                    $output->writeln('<comment>' . $branch . '</comment>');
                
                    $branchCommand = 'branch ' . $branch;
                    $gitRepo->git($branchCommand);
                    $branchConfig = 'gitsd.verbranch.' . $branch;
                    $gitConfig->set($branchConfig, true);
            
                }
            
            }
        
            //switch to development branch
            $output->writeln('<info>gitStuffDone is switching to branch</info> <comment>' . 'v' . $major . '.' . $minor . '</comment>');
        
            $checkoutCommand = 'checkout ' . 'v' . $major . '.' . $minor. '-dev';
        
            $gitRepo->git($checkoutCommand);
        
            $output->writeln('');
            $output->writeln('<info>Project initialization</info> <comment>Complete!</comment>');
            
        }
        
    }
    
}