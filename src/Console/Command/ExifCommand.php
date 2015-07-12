<?php

namespace leonverschuren\Console\Command;

use leonverschuren\Manager\ExifManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Leon Verschuren <contact@leonverschuren.nl>
 */
class ExifCommand extends Command
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('exif:calculate')
            ->setDescription('Calculate missing DateTimes')
            ->addArgument(
                'dir',
                InputArgument::REQUIRED,
                'Fetch from what directory?'
            );
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $exifManager = new ExifManager();

        $dir = $input->getArgument('dir');

        foreach (array_diff(scandir($dir), ['..', '.']) as $file) {
            $exifManager->addFilePath($dir . "\\" . $file);
        }

        $exifManager->writeMissingDates();
    }
}
