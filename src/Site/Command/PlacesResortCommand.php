<?php
namespace Bundle\Site\Command;

use Bolt\Nut\BaseCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PlacesResortCommand  extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('app:resort')
            ->setDescription('Resort places')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var \Bolt\Storage\Database\Connection $dbConnection */
        $dbConnection = $this->app['db'];
        $stmt = $dbConnection->query('UPDATE bolt_places SET sort = FLOOR( 1 + RAND( ) * 100)');
        $output->writeln('OK');
    }
}