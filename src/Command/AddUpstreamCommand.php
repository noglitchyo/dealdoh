<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Command;

use Exception;
use InvalidArgumentException;
use NoGlitchYo\Dealdoh\Entity\DnsUpstream;
use NoGlitchYo\Dealdoh\Entity\DnsUpstreamPool;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

class AddUpstreamCommand extends Command
{
    public const NAME = 'upstream:add';

    /**
     * @var string
     */
    private $upstreamPoolFilePath;

    public function __construct(string $upstreamPoolFilePath)
    {
        $this->upstreamPoolFilePath = $upstreamPoolFilePath;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName(static::NAME)
            ->setDescription('Add a DNS upstream to upstreampool.json.bak file.')
            ->setHelp('Resolve a DNS query.')
            ->addArgument(
                'uri',
                InputArgument::REQUIRED,
                'URI of your upstream (i.e: "https://dns.google.com/resolve", "8.8.8.8:53", "https://dns.google.com/resolve")'
            )->addArgument(
                'code',
                InputArgument::OPTIONAL,
                'An identifier for your upstream (i.e: "local", "google", "cloudflare")'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $upstreamCode = $input->getArgument('code');
        $upstreamUri = $input->getArgument('uri');

        if (!empty($upstreamCode) && !is_string($upstreamCode)) {
            throw new InvalidArgumentException('Upstream code must be a string');
        }

        if (!is_string($upstreamUri)) {
            throw new InvalidArgumentException('Upstream URI must be a string.');
        }

        try {
            $dnsUpstreamPool = DnsUpstreamPool::fromJson(file_get_contents($this->upstreamPoolFilePath));
            $dnsUpstreamPool->addUpstream(new DnsUpstream($upstreamUri, $upstreamCode));

            if (false === file_put_contents($this->upstreamPoolFilePath, json_encode($dnsUpstreamPool, JSON_PRETTY_PRINT))) {
                throw new Exception(sprintf('Failed to dump upstream pool to %s', $this->upstreamPoolFilePath));
            }

            $io->success(sprintf("Added upstream `%s` successfully.", $upstreamUri));
        } catch (Throwable $t) {
            $io->error("Failed to configure DNS upstreampool.json.bak file.");
            $output->writeln('<comment>' . $t->getMessage() . '</comment>');
        }
    }
}
