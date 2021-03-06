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

    /**
     * @throws GELFException
     */
    public function __destruct()
    {
        if (!empty($this->queue)) {
            $this->flush();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function write(string $content, bool $flush = true): void
    {
        $this->enqueue($content);

        if (!$flush) {
            return;
        }

        $this->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function flush(): void
    {
        $socket = null;
        $buffer = [];

        foreach ($this->queue as $message) {
            if (\strlen($message) <= 8192) {
                $socket = $this->send($socket, $message);

                continue;
            }

            $part = '';
            $partLength = 0;
            foreach (\str_split($message) as $char) {
                $part .= $char;

                if (++$partLength === 8180) {
                    $buffer[] = $part;
                    $part = '';
                }
            }

            if ($partLength > 0) {
                $buffer[] = $part;
            }
        }

        $bufferChunks = array_chunk($buffer, 128);

        foreach ($bufferChunks as $partialBuffer) {
            try {
                $messageId = \random_bytes(8);
            } catch (\Exception $exception) {
                throw new GELFException('Impossible to gather enough entropy', GELFException::CODE_CANT_SEND_MESSAGE);
            }

            $chunkSize = \count($partialBuffer);
            $header = "\x1e\x0f" . $messageId;
            $packedChunkSize = pack('C', $chunkSize);

            foreach ($partialBuffer as $i => $part) {
                $message = $header . pack('C', $i) . $packedChunkSize . $part;

                $socket = $this->send($socket, $message);
            }
        }

        $this->queue = [];
    }

    protected function enqueue(string $content): void
    {
        if (!$this->queue) {
            $this->queue = [];
        }

        $this->queue[] = $content;
    }

    /**
     * @return string[]
     */
    protected function getEnqueuedMessagesBuffer(): array
    {
        $buffer = [];

        $lastBuff = '';
        $buffSize = 0;
        $buffKey = 0;
        foreach ($this->queue as $message) {
            foreach (\str_split($message) as $char) {
                if ($buffSize === 8180) {
                    $buffSize = 0;
                    $buffer[$buffKey++] = $lastBuff;
                    $lastBuff = '';
                }

                $buffSize++;
                $lastBuff .= $char;
            }
        }

        $buffer[] = $lastBuff;

        return $buffer;
    }

    /**
     * @param resource $socket
     * @param string   $content
     *
     * @return resource the socket
     * @throws GELFException
     */
    private function send($socket, string $content)
    {
        if ($socket === null) {
            $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);

            if ($socket === false) {
                throw new GELFException('Impossible to open a UDP socket', GELFException::CODE_CANT_SEND_MESSAGE);
            }
        }

        $contentLen = \strlen($content);
        $bytesSent = socket_sendto($socket, $content, $contentLen, 0, $this->address, $this->port);

        if ($bytesSent !== $contentLen) {
            throw new GELFException(
                sprintf('Sent %d bytes, %d bytes expected', $bytesSent, $contentLen),
                GELFException::CODE_CANT_SEND_MESSAGE
            );
        }

        return $socket;
    }
}
