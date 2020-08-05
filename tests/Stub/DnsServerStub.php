<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Tests\Stub;

use NoGlitchYo\Dealdoh\Factory\Dns\MessageFactory;
use NoGlitchYo\Dealdoh\Helper\Base64UrlCodecHelper;
use React\Datagram\Factory;
use React\Datagram\Socket;
use React\Datagram\Socket as ReactSocket;
use React\EventLoop\Factory as EventLoopFactory;
use const STDOUT;

/**
 * Stub of a DNS server
 * Run a socket on an available port, listen for message, and send it back.
 * Can send back a specific message which must be provided to the run method.
 */
class DnsServerStub
{
    public const RECEIVE_ACTION = 'receive';

    /**
     * @var MessageFactory
     */
    private $messageFactory;

    public function __construct()
    {
        $this->messageFactory = new MessageFactory();
    }

    /**
     * @param string|null $dnsResponseMessage A base64_encoded dns message in wire format
     *                                        to send back to the request sender.
     */
    public function run(string $dnsResponseMessage = null)
    {
        $loop = EventLoopFactory::create();
        $factory = new Factory($loop);

        $factory->createServer('127.0.0.1:0')->then(
            function (Socket $socket) use ($dnsResponseMessage) {
                $address = $socket->getLocalAddress();

                // Display server address so it can be use by tests scripts to connect to it
                echo $address;

                $socket->on(
                    'message',
                    function ($message, $address, $server) use ($dnsResponseMessage) {
                        $this->output($this->createReceiveAction($address, $message));

                        /** @var $server ReactSocket */
                        $server->send(
                            $dnsResponseMessage ? Base64UrlCodecHelper::decode($dnsResponseMessage) : $message,
                            $address
                        );
                    }
                );

                return $socket;
            }
        );
        $loop->run();
    }

    private function output(string $action)
    {
        fwrite(STDOUT, $action . "\r\n");
    }

    private function createReceiveAction(string $from, $message)
    {
        return $this->createAction(
            static::RECEIVE_ACTION,
            [
                'from'    => $from,
                'message' => Base64UrlCodecHelper::encode($message),
            ]
        );
    }

    private function createAction(string $name, array $data): string
    {
        return json_encode(
            [
                'name' => $name,
                'data' => $data,
            ]
        );
    }
}
