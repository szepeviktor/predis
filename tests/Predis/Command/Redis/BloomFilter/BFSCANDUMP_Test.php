<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2025 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis\BloomFilter;

use Predis\Command\PrefixableCommand;
use Predis\Command\Redis\PredisCommandTestCase;
use Predis\Response\ServerException;

/**
 * @group commands
 * @group realm-stack
 */
class BFSCANDUMP_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return BFSCANDUMP::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'BFSCANDUMP';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $actualArguments = ['key', 1];
        $expectedArguments = ['key', 1];

        $command = $this->getCommand();
        $command->setArguments($actualArguments);

        $this->assertSameValues($expectedArguments, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $this->assertSame(1, $this->getCommand()->parseResponse(1));
    }

    /**
     * @group disconnected
     */
    public function testPrefixKeys(): void
    {
        /** @var PrefixableCommand $command */
        $command = $this->getCommand();
        $actualArguments = ['arg1'];
        $prefix = 'prefix:';
        $expectedArguments = ['prefix:arg1'];

        $command->setArguments($actualArguments);
        $command->prefixKeys($prefix);

        $this->assertSame($expectedArguments, $command->getArguments());
    }

    /**
     * @group connected
     * @group relay-resp3
     * @return void
     * @requiresRedisBfVersion >= 1.0.0
     */
    public function testScanDumpReturnsCorrectDataChunk(): void
    {
        $expectedIterator = 1;
        $redis = $this->getClient();

        $redis->bfadd('key', 'item1');
        [$iterator, $dataChunk] = $redis->bfscandump('key', 0);

        $this->assertSame($expectedIterator, $iterator);
        $this->assertNotEmpty($dataChunk);
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisBfVersion >= 2.6.0
     */
    public function testScanDumpReturnsCorrectDataChunkResp3(): void
    {
        $expectedIterator = 1;
        $redis = $this->getResp3Client();

        $redis->bfadd('key', 'item1');
        [$iterator, $dataChunk] = $redis->bfscandump('key', 0);

        $this->assertSame($expectedIterator, $iterator);
        $this->assertNotEmpty($dataChunk);
    }

    /**
     * @group connected
     * @group relay-resp3
     * @requiresRedisBfVersion >= 1.0.0
     */
    public function testThrowsExceptionOnWrongType(): void
    {
        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $redis = $this->getClient();

        $redis->set('bfscandump_foo', 'bar');
        $redis->bfscandump('bfscandump_foo', 0);
    }
}
