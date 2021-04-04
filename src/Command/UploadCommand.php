<?php

namespace Twyne\Client\Command;

use DirectoryIterator;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;

class UploadCommand extends Command
{

    /** @var InputInterface */
    private $input;

    /** @var SymfonyStyle */
    private $output;

    protected function configure()
    {
        $this->setName('upload')
            ->setDescription('Upload files.')
            ->addArgument('source', InputArgument::REQUIRED, 'A directory or file name.')
            ->addOption('url', 'u', InputOption::VALUE_REQUIRED, 'The Twyne site base URL.')
            ->addOption('apikey', 'k', InputOption::VALUE_REQUIRED, 'The Twyne API key.')
            ->addOption('tags', 't', InputOption::VALUE_REQUIRED, 'Semicolon-separated tags.', '')
            ->addOption(
                'timezone',
                null,
                InputOption::VALUE_REQUIRED,
                'The timezone of the times in the EXIF data of any uploaded photos.',
                'Z'
            )
            ->addOption('author', 'a', InputOption::VALUE_REQUIRED, 'Author name.')
            ->addOption('group', 'g', InputOption::VALUE_REQUIRED, 'The name of the user group.', '1');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = new SymfonyStyle($input, $output);

        $sourceArg = $input->getArgument('source');
        $source = realpath($sourceArg);

        if (!$source) {
            $this->output->error('Invalid source: ' . $sourceArg);
            return Command::FAILURE;
        }

        if (is_dir($source)) {
            $this->uploadDirectory($source);
        } else {
            $this->uploadOne($source);
        }

        return Command::SUCCESS;
    }

    private function uploadDirectory(string $dir): void
    {
        foreach (new DirectoryIterator($dir) as $file) {
            if ($file->isDot()) {
                continue;
            }

            if ($file->isDir()) {
                $this->uploadDirectory($file->getPathname());
                continue;
            }

            if ($file->isFile()) {
                $this->uploadOne($file->getPathname());
                continue;
            }
        }
    }

    private function uploadOne(string $filename): void
    {
        $urlOpt = $this->input->getOption('url');
        if (!$urlOpt) {
            throw new Exception('Required option: url');
        }
        $url = rtrim($urlOpt, '/');

        $authorName = $this->input->getOption('author');
        if (!$authorName) {
            throw new Exception('Required option: author');
        }

        $client = HttpClient::create();
        // Check by checksum.
        $options = [
            'query' => [
                'api_key' => $this->input->getOption('apikey'),
                'checksums' => sha1_file($filename),
            ],
        ];
        $response1 = $client->request('GET', $url . '/post/search', $options);
        $response1Data = json_decode($response1->getContent(false), true);
        if ($response1Data && $response1Data['post_count'] > 0) {
            $this->output->writeln('Already exists as P' . $response1Data['posts'][0]['id'] . ': ' . $filename);
            return;
        }

        $this->output->write('Uploading ' . $filename . ' . . . ');

        $formFields = [
            'api_key' => $this->input->getOption('apikey'),
            'tags' => $this->input->getOption('tags'),
            'timezone' => $this->input->getOption('timezone'),
            'author' => $authorName,
            'view_group' => $this->input->getOption('group'),
            'files' => [DataPart::fromPath($filename)],
        ];
        $formData = new FormDataPart($formFields);
        $options = [
            'headers' => $formData->getPreparedHeaders()->toArray(),
            'body' => $formData->bodyToIterable(),
        ];
        $response2Data = $client->request('POST', $url . '/upload-api', $options)->getContent(false);
        $response2 = json_decode($response2Data, true);
        if (!$response2) {
            throw new Exception('Unable to decode response: ' . $response2Data);
        }
        if (isset($response2['upload_count']) && $response2['upload_count'] > 0) {
            $this->output->writeln('<info>OK</info>');
        } elseif (isset($response2['fail'])) {
            foreach ($response2['fail'] as $failure) {
                $this->output->error($failure);
            }
        }
    }
}
