<?php

declare(strict_types = 1);

/**
 * Caldera Queue
 * Job queue layer, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2023 Vecode. All rights reserved
 */

namespace Caldera\Queue\Adapter;

use Caldera\Queue\Adapter\AdapterInterface;
use Caldera\Queue\QueueAware;
use Caldera\Queue\QueueAwareInterface;

abstract class AbstractAdapter implements AdapterInterface, QueueAwareInterface {

    use QueueAware;

    /**
     * Generate an UID
     * @return string
     */
    protected function generateUID(): string {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // Set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // Set bits 6-7 to 10
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}