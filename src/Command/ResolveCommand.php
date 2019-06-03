<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Command;

use InvalidArgumentException;
use NoGlitchYo\Dealdoh\Entity\Dns\Message;
use NoGlitchYo\Dealdoh\Entity\Dns\Message\Section\Query;
use NoGlitchYo\Dealdoh\Entity\Dns\Message\Section\ResourceRecordInterface;
use NoGlitchYo\Dealdoh\Factory\Dns\MessageFactoryInterface;
use NoGlitchYo\Dealdoh\Service\DnsResolverInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

class ResolveCommand extends Command
{
    public const NAME = 'resolve';

    private const TYPES_MAP = [
        'A' => ResourceRecordInterface::TYPE_A,
        'NS' => ResourceRecordInterface::TYPE_NS,
        'CNAME' => ResourceRecordInterface::TYPE_CNAME,
        'SOA' => ResourceRecordInterface::TYPE_SOA,
        'PTR' => ResourceRecordInterface::TYPE_PTR,
        'MX' => ResourceRecordInterface::TYPE_MX,
        'TXT' => ResourceRecordInterface::TYPE_TXT,
        'AAAA' => ResourceRecordInterface::TYPE_AAAA,
        'SRV' => ResourceRecordInterface::TYPE_SRV,
        'ANY' => ResourceRecordInterface::TYPE_ANY,
    ];

    /**
     * @var DnsResolverInterface
     */
    private $dnsResolver;

    /**
     * @var MessageFactoryInterface
     */
    private $messageFactory;

    public function __construct(DnsResolverInterface $dnsResolver, MessageFactoryInterface $messageFactory)
    {
        $this->dnsResolver = $dnsResolver;
        $this->messageFactory = $messageFactory;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName(static::NAME)
            ->setDescription('Resolve a DNS query.')
            ->setHelp('Resolve a DNS query.')
            ->addArgument('qname', InputArgument::REQUIRED, 'The query domain name. (i.e: tools.ietf.org)')
            ->addArgument(
                'qtype',
                InputArgument::REQUIRED,
                sprintf(
                    'The query type. Valid query types are: %s',
                    implode(', ', array_keys(self::TYPES_MAP))
                )
            )
            ->addOption('pretty', 'p', null, 'Print human-readable JSON');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $queryName = $input->getArgument('qname');

        if (!is_string($queryName)) {
            throw new InvalidArgumentException('Query name must be a string.');
        }

        if (!is_string($input->getArgument('qtype'))){
            throw new InvalidArgumentException('Query type must be a string.');
        }

        $queryType = self::TYPES_MAP[$input->getArgument('qtype')] ?? 0;
        if ($queryType === 0) {
            throw new InvalidArgumentException(
                sprintf("`%s` is not a valid query type.", $input->getArgument('qtype'))
            );
        }

        $dnsMessage = (Message::createWithDefaultHeader())
            ->addQuestion(new Query($queryName, $queryType, ResourceRecordInterface::CLASS_IN));

        try {
            $dnsResource = $this->dnsResolver->resolve($dnsMessage);
            $io->success("DNS query resolved successfully.");
            $output->writeln(
                sprintf('<comment>Resolved from upstream: %s</comment>', $dnsResource->getUpstream()->getCode())
            );

            $jsonOptions = JSON_THROW_ON_ERROR | ($input->getOption('pretty') ? JSON_PRETTY_PRINT : 0);

            /** @var string $jsonDnsResponse */
            $jsonDnsResponse = json_encode($dnsResource->getResponse(), $jsonOptions);

            $output->writeln($jsonDnsResponse);
        } catch (Throwable $t) {
            $io->error("DNS query failed to resolve.");
            $output->writeln('<comment>' . $t->getMessage() . '</comment>');
        }
    }
}
