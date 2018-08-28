<?php

namespace GiacomoFurlan\Graylog\Test;

use GiacomoFurlan\Graylog\GELFException;
use GiacomoFurlan\Graylog\GELFLogger;
use GiacomoFurlan\Graylog\WriterInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class GELFLoggerTest extends TestCase
{
    public function testLoggerWillThrowExceptionHostNotSet(): void
    {
        $writer = $this->prophesize(WriterInterface::class)->reveal();

        $logger = new GELFLogger($writer, '');
        $this->expectException(GELFException::class);
        $this->expectExceptionCode(GELFException::CODE_MISSING_HOST);

        $logger->error('whatever');
    }

    public function testLoggerWillThrowExceptionMessageTooBig(): void
    {
        $writer = $this->prophesize(WriterInterface::class)->reveal();

        $logger = new GELFLogger($writer, 'host');
        $message = '';
        for ($i = 0; $i < 1024; $i++) {
            $message .= '0';
        }
        $this->expectException(GELFException::class);
        $this->expectExceptionCode(GELFException::CODE_MESSAGE_TOO_BIG);

        $logger->error($message);
    }

    public function testLoggerWillSendGELFToWriter(): void
    {
        $host = 'Whatever';
        $message = 'Simple message';
        $context = ['var1' => 1, 'var_2' => 'two'];

        $writer = $this->prophesize(WriterInterface::class);
        $writer->write(Argument::type('string'))->will(function (array $arguments) use ($host, $message, $context) {
            $json = \json_decode($arguments[0]);

            TestCase::assertEquals('1.1', $json->version);
            TestCase::assertEquals($host, $json->host);
            TestCase::assertEquals($message, $json->short_message);

            foreach ($context as $var => $val) {
                TestCase::assertEquals($val, $json->{"_$var"});
            }
        })->shouldBeCalledTimes(1);

        $logger = new GELFLogger($writer->reveal(), $host);

        $logger->error($message, $context);
    }
}
