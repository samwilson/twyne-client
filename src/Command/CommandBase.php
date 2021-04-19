<?php

namespace Twyne\Client\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;
use XdgBaseDir\Xdg;

abstract class CommandBase extends Command
{

    /** @var InputInterface */
    protected $input;

    /** @var SymfonyStyle */
    protected $output;

    protected function configure()
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = new SymfonyStyle($input, $output);
        return Command::SUCCESS;
    }

    protected function getConfigFilename(): string
    {
        $xdg = new Xdg();
        return $xdg->getHomeConfigDir() . '/twyne-client/config.yaml';
    }

    public function getDefaultConfig(): array
    {
        return [
            'url' => '',
            'api_key' => '',
            'default_author' => '',
            'default_group' => 'Private',
        ];
    }

    protected function getConfig(): array
    {
        $configFile = $this->getConfigFilename();
        $config = $this->getDefaultConfig();
        if (file_exists($configFile)) {
            $config = array_merge($config, Yaml::parseFile($configFile));
        }
        return $config;
    }
}
