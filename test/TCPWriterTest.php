<?php

namespace GiacomoFurlan\Graylog\Test;

use GiacomoFurlan\Graylog\GELFException;
use GiacomoFurlan\Graylog\TCPWriter;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class TCPWriterTest extends TestCase
{
    use PHPMock;

    protected $classNamespace;

    public function __construct($name = null, $data = [], $dataName = '')
    {
        ini_set('memory_limit', -1);

        parent::__construct($name, $data, $dataName);

        $refClass = new ReflectionClass(TCPWriter::class);
        $this->classNamespace = $refClass->getNamespaceName();
    }

    public function testWriterWillSendMessage(): void
    {
        $address = '127.0.0.1';
        $port = 5099;
        $testData = 'Test message';

        $socketCreateMock = $this->getFunctionMock($this->classNamespace, 'socket_create');
        $socketCreateMock->expects(TestCase::any())->willReturnCallback(function ($domain, $type, $protocol) {
            TestCase::assertEquals(AF_INET, $domain);
            TestCase::assertEquals(SOCK_STREAM, $type);
            TestCase::assertEquals(SOL_TCP, $protocol);

            return 1;
        });

        $socketSendToMock = $this->getFunctionMock($this->classNamespace, 'socket_sendto');
        $socketSendToMock
            ->expects(TestCase::any())
            ->willReturnCallback(function ($socket, $content, $contentLen, $flags, $fnAddress, $fnPort) use ($address, $port) {
                TestCase::assertEquals(1, $socket);
                TestCase::assertEquals(\strlen($content), $contentLen);
                TestCase::assertEquals(0, $flags);
                TestCase::assertEquals($address, $fnAddress);
                TestCase::assertEquals($port, $fnPort);

                return $contentLen;
            });

        $writer = new TCPWriter($address, $port);
        $writer->write($testData);
    }

    public function testWriterWillThrowExceptionSocketCantBeCreated(): void
    {
        $address = '127.0.0.1';
        $port = 5099;
        $testData = 'Test message';

        $socketCreateMock = $this->getFunctionMock($this->classNamespace, 'socket_create');
        $socketCreateMock->expects(TestCase::any())->willReturnCallback(function () {
            return false;
        });

        $this->expectException(GELFException::class);
        $this->expectExceptionCode(GELFException::CODE_CANT_SEND_MESSAGE);

        $writer = new TCPWriter($address, $port);
        $writer->write($testData);
    }

    public function testWriterWillThrowExceptionMessageNotCorrectlySent(): void
    {
        $address = '127.0.0.1';
        $port = 5099;
        $testData = 'Test message';

        $socketCreateMock = $this->getFunctionMock($this->classNamespace, 'socket_create');
        $socketCreateMock->expects(TestCase::any())->willReturnCallback(function ($domain, $type, $protocol) {
            TestCase::assertEquals(AF_INET, $domain);
            TestCase::assertEquals(SOCK_STREAM, $type);
            TestCase::assertEquals(SOL_TCP, $protocol);

            return 1;
        });

        $socketSendToMock = $this->getFunctionMock($this->classNamespace, 'socket_sendto');
        $socketSendToMock
            ->expects(TestCase::any())
            ->willReturnCallback(function ($socket, $content) {
                return strlen($content) - 5;
            });

        $this->expectException(GELFException::class);
        $this->expectExceptionCode(GELFException::CODE_CANT_SEND_MESSAGE);

        $writer = new TCPWriter($address, $port);
        $writer->write($testData);
    }

    public function testWriterWillEnqueueAndSendMultipleMessages(): void
    {
        $address = '127.0.0.1';
        $port = 5099;

        $socketCreateMock = $this->getFunctionMock($this->classNamespace, 'socket_create');
        $socketCreateMock->expects(TestCase::any())->willReturnCallback(function ($domain, $type, $protocol) {
            return 1;
        });

        $messageId = null;
        $seqTot = null;
        $prevSeqNum = null;
        $socketSendToMock = $this->getFunctionMock($this->classNamespace, 'socket_sendto');
        $socketSendToMock
            ->expects(TestCase::any())
            ->willReturnCallback(function ($socket, $content) {
                TestCase::assertLessThanOrEqual(TCPWriter::MAX_MESSAGE_SIZE, \strlen($content));

                return strlen($content);
            });

        $writer = new TCPWriter($address, $port);
        for ($i = 0; $i < 200; $i++) {
            $message = \random_bytes(\random_int(TCPWriter::MAX_MESSAGE_SIZE / 200, TCPWriter::MAX_MESSAGE_SIZE));
            $writer->write($message, false);
        }

        $writer->flush();
    }
}
