<?php

namespace App\Command;

use Exception;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class JWTGenerateCommand extends Command
{
    private const NAME = 'jwt:generate';

    private string $passPhrase;
    private string $privatePemPath;
    private string $publicPemPath;

    public function __construct(string $privatePemPath, string $publicPemPath, string $passPhrase)
    {
        $this->privatePemPath = $privatePemPath;
        $this->publicPemPath = $publicPemPath;
        $this->passPhrase = $passPhrase;
        parent::__construct(self::NAME);
    }

    /**
     * Configuration
     */
    protected function configure()
    {
        $this
            ->setDescription('Creates a new JWT security keys')
            ->setHelp('Generate and rewrite JWT security keys files');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        try {
            $this->generate();
            $io->success('Генерация ключей завершена!');
        } catch (Exception $e) {
            $io->error($e);
        }

        return 0;
    }

    /**
     * Генерирует ключи шифрования JWT
     */
    private function generate()
    {
        $keyPair = openssl_pkey_new([
            'private_key_bits' => '2048',
        ]);
        openssl_pkey_export($keyPair, $privateKey, $this->passPhrase);

        $publicKey = openssl_pkey_get_details($keyPair);
        $publicKey = $publicKey['key'];

        $this->saveKey($this->privatePemPath, $privateKey);
        $this->saveKey($this->publicPemPath, $publicKey);
    }

    /**
     * Сохраняет $content в файл $path
     * @param string $path
     * @param string $content
     */
    private function saveKey(string $path, string $content): void
    {
        $dir = dirname($path);
        if (!file_exists($dir)
            && !mkdir($dir, 0777, true)
            && !is_dir($dir)
        ) {
            throw new RuntimeException(sprintf('Директорию "%s" создать не удалось', $dir));
        }
        file_put_contents($path, $content);
    }
}
