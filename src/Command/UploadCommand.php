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
use Symfony\Contracts\HttpClient\ResponseInterface;

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
            ->addOption('author', 'a', InputOption::VALUE_REQUIRED, 'Author name.', 'Anon.')
            ->addOption('group', 'g', InputOption::VALUE_REQUIRED, 'The name of the user group.', 'Private');
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
        $authHeader = 'Twyne api_key=' . $this->input->getOption('apikey');
        // Check by checksum.
        $options = [
            'query' => [
                'checksums' => sha1_file($filename),
            ],
            'headers' => [
                'Authorization' => $authHeader,
            ],
        ];
        $response1Data = $client->request('GET', $url . '/post/search', $options);
        $response1 = $this->getJson($response1Data);
        if ($response1['post_count'] > 0) {
            $this->output->writeln('Already exists as ' . $response1['posts'][0]['url'] . ' -- ' . $filename);
            return;
        }

        $this->output->write('Uploading ' . $filename . ' . . . ');

        $formFields = [
            'tags' => $this->input->getOption('tags'),
            'timezone' => $this->input->getOption('timezone'),
            'author' => $authorName,
            'view_group' => $this->input->getOption('group'),
            'files' => [DataPart::fromPath($filename)],
        ];
        $formData = new FormDataPart($formFields);
        $options = [
            'headers' => array_merge($formData->getPreparedHeaders()->toArray(), ['Authorization' => $authHeader]),
            'body' => $formData->bodyToIterable(),
        ];
        $response2Data = $client->request('POST', $url . '/upload-api', $options);
        $response2 = $this->getJson($response2Data);
        if (isset($response2['upload_count']) && $response2['upload_count'] > 0) {
            foreach ($response2['success'] as $success) {
                $this->output->writeln('<info>' . $success . '</info>');
            }
        } elseif (isset($response2['fail'])) {
            foreach ($response2['fail'] as $failure) {
                $this->output->error($failure);
            }
        }
    }

    private function getJson(ResponseInterface $response): array
    {
        $responseContent = $response->getContent(false);
        $responseData = json_decode($responseContent, true);
        if (!$responseData) {
            throw new Exception('Unable to decode response: ' . $responseContent);
        }
        if (isset($responseData['error'])) {
            throw new Exception('Error: ' . $responseData['error']);
        }
        return $responseData;
    }
}
