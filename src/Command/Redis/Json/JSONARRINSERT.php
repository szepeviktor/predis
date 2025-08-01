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

namespace Predis\Command\Redis\Json;

use Predis\Command\PrefixableCommand as RedisCommand;

/**
 * @see https://redis.io/commands/json.arrinsert/
 *
 * Insert the json values into the array at path before the index (shifts to the right)
 */
class JSONARRINSERT extends RedisCommand
{
    public function getId()
    {
        return 'JSON.ARRINSERT';
    }

    public function prefixKeys($prefix)
    {
        $this->applyPrefixForFirstArgument($prefix);
    }
}
