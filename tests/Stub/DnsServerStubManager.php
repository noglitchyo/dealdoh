<?php

declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Tests\Stub;

use Exception;
use NoGlitchYo\Dealdoh\Entity\Dns\MessageInterface;
use NoGlitchYo\Dealdoh\Factory\Dns\MessageFactory;
use NoGlitchYo\Dealdoh\Helper\Base64UrlCodecHelper;
use Symfony\Component\Process\Process;

class DnsServerStubManager
{
    /**
     * @var MessageFactory
     */
    private $messageFactory;

    /**
     * @var Process
     */
    private $process;

    public function __construct()
    {
        $this->messageFactory = new MessageFactory();
    }

    /**
     * Create a fake DNS upstream which listen for messages and send them back.
     * Return address and port of the created server
     *
     * @param MessageInterface $dnsResponseToReturn A message to send back to the sender.
     *
     * @return mixed
     * @throws Exception
     */
    public function create(MessageInterface $dnsResponseToReturn = null)
    {
        $process = [
            "php",
            __DIR__ . "/../dns-server.php",
        ];

        if ($dnsResponseToReturn) {
            $encodedDnsResponseMessage = Base64UrlCodecHelper::encode(
                $this->messageFactory->createDnsWireMessageFromMessage($dnsResponseToReturn)
            );

            $process[] = "--message=" . $encodedDnsResponseMessage;
        }

        $this->process = new Process($process);
        $this->process->start();

        // The first output from dns server stub is the remote address of the server to be use to connect to it
        foreach ($this->process as $type => $data) {
            if ($this->process::OUT === $type) {
                return $data;
            } else { // $process::ERR === $type
                throw new Exception('Failed to run server: ' . $data);
            }
        }
    }

    /**
     * @return Process
     */
    public function getProcess()
    {
        return $this->process;
    }
}
