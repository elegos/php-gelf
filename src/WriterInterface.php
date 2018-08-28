<?php

namespace GiacomoFurlan\Graylog;

interface WriterInterface
{
    /**
     * Write a message
     *
     * @param string $content
     */
    public function write(string $content): void;
}
