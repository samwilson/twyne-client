<?php

namespace Twyne\Client\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class ConfigureCommand extends CommandBase
{
    protected function configure()
    {
        $this->setName('configure')
            ->setDescription('Configure the Twyne client and store the configuration in a local file.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        // Load existing and default.
        $config = $this->getConfig();

        // Ask user for new values.
        $config['url'] = $this->output->ask('URL of your Twyne site:', $config['url']);
        $config['api_key'] = $this->output->ask('API key:', $config['api_key']);
        $config['default_author'] = $this->output->ask('Default author name:', $config['default_author']);
        $config['default_group'] = $this->output->ask('Default user-group name:', $config['default_group']);

        $configFile = $this->getConfigFilename();

        // Create config directory.
        $configDir = dirname($configFile);
        if (!is_dir($configDir)) {
            $this->output->writeln('Creating directory: ' . $configDir);
            mkdir($configDir, 0700, true);
        }

        // Save config file.
        $yaml = Yaml::dump($config);
        file_put_contents($configFile, $yaml);
        $this->output->writeln('Config saved to ' . $configFile);
        $this->output->block($yaml);

        return Command::SUCCESS;
    }
}
