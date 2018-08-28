<?php declare(strict_types=1);

namespace GiacomoFurlan\Graylog;

interface WriterInterface
{
    /**
     * Write a message
     *
     * @param string $content
     * @param bool   $flush if false, self::flush() call is required
     *
     * @throws GELFException
     */
    public function write(string $content, bool $flush = true): void;

    /**
     * Flush all the messages in queue
     *
     * @throws GELFException
     */
    public function flush(): void;
}
