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

namespace Predis\Command\Redis\Search;

use InvalidArgumentException;
use Predis\Command\Argument\Search\SchemaFields\TextField;
use Predis\Command\Argument\Search\SpellcheckArguments;
use Predis\Command\PrefixableCommand;
use Predis\Command\Redis\PredisCommandTestCase;
use Predis\Response\ServerException;

/**
 * @group commands
 * @group realm-stack
 */
class FTSPELLCHECK_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return FTSPELLCHECK::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'FTSPELLCHECK';
    }

    /**
     * @group disconnected
     * @dataProvider argumentsProvider
     */
    public function testFilterArguments(array $actualArguments, array $expectedArguments): void
    {
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

        $command->setRawArguments($actualArguments);
        $command->prefixKeys($prefix);

        $this->assertSame($expectedArguments, $command->getArguments());
    }

    /**
     * @group connected
     * @group relay-resp3
     * @return void
     * @requiresRediSearchVersion >= 1.4.0
     */
    public function testSpellcheckReturnsPossibleSuggestionsToGivenMisspelledTerm(): void
    {
        $redis = $this->getClient();
        $expectedResponse = [['TERM', 'held', [['0', 'hello'], ['0', 'help']]]];

        $this->assertEquals('OK', $redis->ftcreate(
            'index',
            [new TextField('text_field')]
        ));

        $this->assertEquals(2, $redis->ftdictadd('dict', 'hello', 'help'));

        $actualResponse = $redis->ftspellcheck(
            'index',
            'held',
            (new SpellcheckArguments())->distance(2)->terms('dict')
        );

        $this->assertEquals($expectedResponse, $actualResponse);
    }

    /**
     * @group connected
     * @group relay-resp3
     * @return void
     * @requiresRediSearchVersion >= 2.8.0
     */
    public function testSpellcheckReturnsPossibleSuggestionsToGivenMisspelledTermResp3(): void
    {
        $redis = $this->getResp3Client();
        $expectedResponse = [
            'results' => [
                'held' => [['hello' => 0.0], ['help' => 0.0]],
            ],
        ];

        $this->assertEquals('OK', $redis->ftcreate(
            'index',
            [new TextField('text_field')]
        ));

        $this->assertEquals(2, $redis->ftdictadd('dict', 'hello', 'help'));

        $actualResponse = $redis->ftspellcheck(
            'index',
            'held',
            (new SpellcheckArguments())->distance(2)->terms('dict')
        );

        $this->assertSame($expectedResponse, $actualResponse);
    }

    /**
     * @group connected
     * @group relay-resp3
     * @return void
     * @requiresRediSearchVersion >= 1.4.0
     */
    public function testThrowsExceptionOnIncorrectTermsModifierGiven(): void
    {
        $redis = $this->getClient();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Wrong modifier value given. Currently supports: INCLUDE, EXCLUDE');

        $redis->ftspellcheck(
            'index',
            'held',
            (new SpellcheckArguments())->distance(2)->terms('dict', 'wrong')
        );
    }

    /**
     * @group connected
     * @return void
     * @requiresRediSearchVersion >= 1.4.0
     */
    public function testThrowsExceptionOnNonExistingIndex(): void
    {
        $redis = $this->getClient();

        $this->expectException(ServerException::class);

        $redis->ftspellcheck(
            'index',
            'held',
            (new SpellcheckArguments())->distance(2)->terms('dict')
        );
    }

    public function argumentsProvider(): array
    {
        return [
            'with default arguments' => [
                ['index', 'query'],
                ['index', 'query', 'DIALECT', 2],
            ],
            'with DISTANCE modifier' => [
                ['index', 'query', (new SpellcheckArguments())->distance(2)],
                ['index', 'query', 'DISTANCE', 2, 'DIALECT', 2],
            ],
            'with TERMS modifier - INCLUDE' => [
                ['index', 'query', (new SpellcheckArguments())->terms('dict', 'INCLUDE', 'term')],
                ['index', 'query', 'TERMS', 'INCLUDE', 'dict', 'term', 'DIALECT', 2],
            ],
            'with TERMS modifier - EXCLUDE' => [
                ['index', 'query', (new SpellcheckArguments())->terms('dict', 'EXCLUDE', 'term')],
                ['index', 'query', 'TERMS', 'EXCLUDE', 'dict', 'term', 'DIALECT', 2],
            ],
            'with DIALECT modifier' => [
                ['index', 'query', (new SpellcheckArguments())->dialect('dialect')],
                ['index', 'query', 'DIALECT', 'dialect'],
            ],
            'with all arguments' => [
                ['index', 'query', (new SpellcheckArguments())->distance(2)->terms('dict', 'INCLUDE', 'term')->dialect('dialect')],
                ['index', 'query', 'DISTANCE', 2, 'TERMS', 'INCLUDE', 'dict', 'term', 'DIALECT', 'dialect'],
            ],
        ];
    }
}
