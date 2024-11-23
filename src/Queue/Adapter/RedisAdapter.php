<?php

declare(strict_types = 1);

/**
 * Caldera Queue
 * Job queue layer, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2023 Vecode. All rights reserved
 */

namespace Caldera\Queue\Adapter;

use RuntimeException;

use Predis\Client;

use Caldera\Queue\JobInterface;
use Caldera\Queue\QueueAwareInterface;

class RedisAdapter extends AbstractAdapter {

    /**
     * List prefix
     */
    protected string $prefix;

    /**
     * Client instance
     */
    protected Client $client;

    /**
     * Constructor
     * @param Client $client Client instance
     * @param string $prefix List prefix
     */
    public function __construct(Client $client, string $prefix = 'caldera') {
        $this->client = $client;
        $this->prefix = $prefix;
    }

    /**
     * @inheritdoc
     */
    public function add(string $type, mixed $data): string {
        $uid = $this->generateUID();
        $item = [
            'uid' => $uid,
            'type' => $type,
            'data' => serialize($data),
            'status' => 'Pending',
            'retries' => 0
        ];
        $payload = json_encode($item);
        # Add to the pending queue
        $index = $this->client->lpush("{$this->prefix}:queue:pending", [$payload]);
        # And save the index of the item
        $this->client->hset("{$this->prefix}:index:pending", $uid, (string)$index);
        return $uid;
    }

    /**
     * @inheritdoc
     */
    public function get(): ?JobInterface {
        $ret = null;
        # Pop the oldest item
        $payload = $this->client->rpop("{$this->prefix}:queue:pending");
        $item = @json_decode($payload);
        # And remove the index entry
        $this->client->hdel("{$this->prefix}:index:pending", $item->uid);
        if ($item) {
            # Now add it to the working queue
            $index = $this->client->lpush("{$this->prefix}:queue:working", [$payload]);
            # And save its index
            $this->client->hset("{$this->prefix}:index:working", $item->uid, (string)$index);
            if ( class_exists($item->type) ) {
                $ret = new $item->type($item->uid, @unserialize($item->data), (int) $item->retries);
                if ($ret instanceof QueueAwareInterface) {
                    $ret->setQueue($this->queue);
                }
                if (! $ret instanceof JobInterface ) {
                    $this->failed($item->uid);
                    throw new RuntimeException('Invalid job type');
                }
            }
        }
        return $ret;
    }

    /**
     * @inheritdoc
     */
    public function complete(string $uid): void {
        # Get the index of the job
        $index = $this->client->hget("{$this->prefix}:index:working", $uid);
        if ($index) {
            # Get the value of the job
            $index = (int)$index - 1;
            $value = $this->client->lrange("{$this->prefix}:queue:working", $index, $index);
            # Delete the item from the queue and remove its index too
            $this->client->lrem("{$this->prefix}:queue:working", 1, $value[0]);
            $this->client->hdel("{$this->prefix}:index:working", [$uid]);
        }
    }

    /**
     * @inheritdoc
     */
    public function failed(string $uid): void {
        # Get the index of the job
        $index = $this->client->hget("{$this->prefix}:index:working", $uid);
        if ($index) {
            # Get the value of the job
            $index = (int)$index - 1;
            $value = $this->client->lrange("{$this->prefix}:queue:working", $index, $index);
            # Delete the item from the queue and remove its index too
            $this->client->lrem("{$this->prefix}:queue:working", 1, $value[0]);
            $this->client->hdel("{$this->prefix}:index:working", [$uid]);
            # Add it to the failed queue
            $index = $this->client->lpush("{$this->prefix}:queue:failed", $value);
            # And save its index
            $this->client->hset("{$this->prefix}:index:failed", $uid, (string)$index);
        }
    }

    /**
     * @inheritdoc
     */
    public function retry(string $uid): void {
        # Get the index of the job
        $index = $this->client->hget("{$this->prefix}:index:working", $uid);
        if ($index) {
            # Get the value of the job
            $index = (int)$index - 1;
            $value = $this->client->lrange("{$this->prefix}:queue:working", $index, $index);
            # Delete the item from the queue and remove its index too
            $this->client->lrem("{$this->prefix}:queue:working", 1, $value[0]);
            $this->client->hdel("{$this->prefix}:index:working", [$uid]);
            # Increment the retry counter
            $item = @json_decode($value[0] ?? '');
            if ($item) {
                $item->retries += 1;
                $value = json_encode($item);
                # Add it to the pending queue
                $index = $this->client->lpush("{$this->prefix}:queue:pending", [$value]);
                # And save its index
                $this->client->hset("{$this->prefix}:index:pending", $uid, (string)$index);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function pending(string $type = ''): int {
        $ret = $this->client->llen("{$this->prefix}:queue:pending");
        return $ret;
    }

    /**
     * @inheritdoc
     */
    public function reset(string $type = ''): void {
        while ( $value = $this->client->rpop("{$this->prefix}:queue:failed") ) {
            $item = @json_decode($value);
            if ($item) {
                # Delete the item from the queue and remove its index too
                $this->client->lrem("{$this->prefix}:queue:failed", 1, $value);
                $this->client->hdel("{$this->prefix}:index:failed", $item->uid);
                # Reset the retry counter
                $item->retries = 0;
                $value = json_encode($item);
                # Add it to the pending queue
                $new_index = $this->client->lpush("{$this->prefix}:queue:pending", [$value]);
                # And save its index
                $this->client->hset("{$this->prefix}:index:pending", $item->uid, (string)$new_index);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function purge(string $type = ''): void {
        while ( $value = $this->client->rpop("{$this->prefix}:queue:failed") ) {
            $item = @json_decode($value);
            if ($item) {
                $this->client->hdel("{$this->prefix}:index:failed", $item->uid);
            }
        }
    }
}
