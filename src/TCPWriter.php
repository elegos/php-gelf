<?php

namespace GiacomoFurlan\Graylog;

class TCPWriter extends UDPWriter
{
    public const MAX_MESSAGE_SIZE = 8192;

    /**
     * {@inheritdoc}
     */
    public function flush(): void
    {
        $socket = null;
        $buffer = [];
        $previousSize = 0;

        foreach ($this->queue as $message) {
            $messageSize = \strlen($message);

            // The message exceeds the maximum bytes size, discard it
            if ($messageSize > self::MAX_MESSAGE_SIZE) {
                continue;
            }

            if ($previousSize + $messageSize + 1 > static::MAX_MESSAGE_SIZE) {
                $socket = $this->send($socket, $buffer);
                $previousSize = 0;
                $buffer = [];
            }

            $previousSize += $messageSize + 1;
            $buffer[] = $message;
        }
        $this->send($socket, $buffer);

        $this->queue = [];
    }

    /**
     * @param resource $socket
     * @param string[] $messages
     *
     * @return resource|null
     * @throws GELFException
     */
    private function send($socket, array $messages)
    {
        if (\count($messages) === 0) {
            return null;
        }

        if ($socket === null) {
            $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

            if ($socket === false) {
                throw new GELFException('Impossible to open a TCP socket', GELFException::CODE_CANT_SEND_MESSAGE);
            }

            $socketConnectionResult = socket_connect($socket, $this->address, $this->port);

            if ($socketConnectionResult === false) {
                throw new GELFException('Impossible to connect to the Graylog server via TCP', GELFException::CODE_CANT_SEND_MESSAGE);
            }
        }

        $content = \implode("\0", $messages)."\0";
        $contentLen = \strlen($content);
        $bytesSent = socket_write($socket, $content);

        if ($bytesSent !== $contentLen) {
            throw new GELFException(
                sprintf('Sent %d bytes, %d bytes expected', $bytesSent, $contentLen),
                GELFException::CODE_CANT_SEND_MESSAGE
            );
        }

        return $socket;
    }
}
