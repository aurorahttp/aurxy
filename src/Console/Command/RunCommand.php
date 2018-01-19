<?php

namespace Aurxy\Console\Command;

use Aurxy;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class RunCommand extends Command
{
    protected function configure()
    {
        $this->setName('run')
            ->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'configure file',
                AURXY_DIR . '/' . AURXY_CONFIG_FILENAME)
            ->addOption('bootstrap', null, InputOption::VALUE_REQUIRED,
                'a bootstrap file for inject user code and events');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->hasOption('config')) {
            Aurxy::configure(Yaml::parse(file_get_contents($input->getOption('config'))));
        }
        if ($input->hasOption('bootstrap')) {
            Aurxy::$bootstrap = $input->getOption('bootstrap');
        }
        $output->writeln("Start server pid => " . posix_getpid());

        Aurxy::run();
    }
}