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

namespace Predis\Command\Redis\CountMinSketch;

use Predis\Command\PrefixableCommand as RedisCommand;

/**
 * @see https://redis.io/commands/cms.incrby/
 *
 * Increases the count of item by increment.
 * Multiple items can be increased with one call.
 */
class CMSINCRBY extends RedisCommand
{
    public function getId()
    {
        return 'CMS.INCRBY';
    }

    public function prefixKeys($prefix)
    {
        $this->applyPrefixForFirstArgument($prefix);
    }
}
