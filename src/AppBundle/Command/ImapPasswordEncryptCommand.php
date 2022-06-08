<?php

// src/AppBundle/Command/GreetCommand.php
namespace AppBundle\Command;

//use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use AppBundle\Service\EncryptionService;

class ImapPasswordEncryptCommand extends Command
{

    protected $encryptionService;

    public function __construct(EncryptionService $encryptionService)
    {
        if(!is_object($encryptionService)) throw new \InvalidArgumentException(__METHOD__.' requires instance of EncryptionService');
        $this->encryptionService = $encryptionService;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('imap:password')
            ->setDescription('Encrypt a password for ImapChecker to use')
            ->addArgument(
                'password',
                InputArgument::REQUIRED,
                'Password to encrypt'
            );
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $password = $input->getArgument('password');
        if ($password) {
            $output->writeln($this->encryptionService->encrypt($password));
        }

        
    }
}
