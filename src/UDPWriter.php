<?php declare(strict_types=1);

namespace GiacomoFurlan\Graylog;


class UDPWriter implements WriterInterface
{
    /** @var string */
    protected $address;

    /** @var int */
    protected $port;

    /** @var string[] */
    protected $queue;

    public function __construct(string $address, int $port)
    {
        $this->address = $address;
        $this->port = $port;
    }

    protected function enqueue(string $content): void
    {
        if (!$this->queue) {
            $this->queue = [];
        }

        $this->queue[] = $content;
    }

    /**
     * {@inheritdoc}
     */
    public function write(string $content, bool $flush = true): void
    {
        if (!$flush) {
            $this->enqueue($content);

            return;
        }

        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);

        if ($socket === false) {
            throw new GELFException('Impossible to open a UDP socket', GELFException::CODE_CANT_SEND_MESSAGE);
        }

        $contentLen = strlen($content);
        $bytesSent = socket_sendto($socket, $content, $contentLen, 0, $this->address, $this->port);

        if ($bytesSent !== $contentLen) {
            throw new GELFException(
                sprintf('Sent %d bytes, %d bytes expected', $bytesSent, $contentLen),
                GELFException::CODE_CANT_SEND_MESSAGE
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function flush(): void
    {
        $buffer = [];
        foreach ($this->queue as $message) {
            if (\count($buffer) === 0) {
                $buffer[] = $message;

                continue;
            }

            $len = strlen($message);
            $bufferLen = strlen(end($buffer));

            if ($len + $bufferLen <= 8180) {
                $key = key($buffer);
                $buffer[$key] .= $message;

                continue;
            }

            $buffer[] = $message;
        }

        $bufferLen = \count($buffer);
        if ($bufferLen === 1) {
            $this->write($buffer[0]);
            $this->queue = [];

            return;
        }

        $bufferChunks = array_chunk($buffer, 128);
        foreach ($bufferChunks as $buffer) {
            try {
                $messageId = \random_bytes(8);
            } catch (\Exception $exception) {
                throw new GELFException('Impossible to gather enough entropy', GELFException::CODE_CANT_SEND_MESSAGE);
            }

            foreach ($buffer as $i => $part) {
                $message = "\x1e\x0f" . $messageId . pack('C', $i) . pack('C', $bufferLen) . $part;

                $this->write($message);
            }
        }

        $this->queue = [];
    }
}