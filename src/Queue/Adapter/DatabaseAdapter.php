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

use Caldera\Database\Database;
use Caldera\Database\Schema\Schema;
use Caldera\Database\Query\Argument;
use Caldera\Database\Query\Query;

use Caldera\Queue\JobInterface;
use Caldera\Queue\QueueAwareInterface;

class DatabaseAdapter extends AbstractAdapter {

    /**
     * Table name
     */
    protected string $table = 'queue';

    /**
     * Column name
     */
    protected string $column = 'uid';

    /**
     * Database instance
     */
    protected Database $database;

    /**
     * Constructor
     * @param Database $database Database instance
     */
    public function __construct(Database $database, string $table = 'queue', string $column = 'uid') {
        $this->database = $database;
        $this->table = $table;
        $this->column = $column;
        $this->createTable();
    }

    /**
     * @inheritdoc
     */
    public function add(string $type, mixed $data): string {
        $ret = null;
        $uid = $this->generateUID();
        $row = [
            'id' => 0,
            $this->column => $uid,
            'type' => $type,
            'data' => serialize($data),
            'status' => 'Pending',
            'retries' => 0,
            'created' => Argument::method('NOW'),
            'modified' => Argument::method('NOW')
        ];
        $query = new Query($this->database);
        $id = $query->table($this->table)->insert($row);
        if ($id) {
            $ret = $uid;
        }
        return $ret;
    }

    /**
     * @inheritdoc
     */
    public function get(): ?JobInterface {
        $ret = null;
        $query = new Query($this->database);
        $item = $query->table($this->table)
            ->where('status', 'Pending')
            ->order('modified', 'ASC')
            ->first();
        if ($item) {
            $query = new Query($this->database);
            $query->table($this->table)
                ->where('id', $item->id)
                ->update(['status' => 'Working']);
            if ( class_exists($item->type) ) {
                $ret = new $item->type($item->{$this->column}, @unserialize($item->data), (int) $item->retries);
                if ($ret instanceof QueueAwareInterface) {
                    $ret->setQueue($this->queue);
                }
                if (! $ret instanceof JobInterface ) {
                    $this->failed($item->{$this->column});
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
        $query = new Query($this->database);
        $query->table($this->table)
            ->where($this->column, $uid)
            ->delete();
    }

    /**
     * @inheritdoc
     */
    public function failed(string $uid): void {
        $query = new Query($this->database);
        $query->table($this->table)
            ->where($this->column, $uid)
            ->update(['status' => 'Failed']);
    }

    /**
     * @inheritdoc
     */
    public function retry(string $uid): void {
        $query = new Query($this->database);
        $query->table($this->table)
            ->where($this->column, $uid)
            ->update(['status' => 'Pending', 'retries' => Argument::raw('retries + 1')]);
    }

    /**
     * @inheritdoc
     */
    public function pending(string $type = ''): int {
        $ret = 0;
        $query = new Query($this->database);
        $query->table($this->table)->where('status', 'Pending');
        if ($type) {
            $query->where('type', $type);
        }
        $ret = $query->count();
        return $ret;
    }

    /**
     * @inheritdoc
     */
    public function reset(string $type = ''): void {
        $query = new Query($this->database);
        $query->table($this->table)->where('status', 'Working', '!=');
        if ($type) {
            $query->where('type', $type);
        }
        $query->update(['status' => 'Pending', 'retries' => 0]);
    }

    /**
     * @inheritdoc
     */
    public function purge(string $type = ''): void {
        $query = new Query($this->database);
        $query->table($this->table)->where('status', 'Failed');
        if ($type) {
            $query->where('type', $type);
        }
        $query->delete();
    }

    /**
     * Create the jobs table
     */
    protected function createTable(): void {
        $schema = new Schema($this->database);
        if (! $schema->hasTable($this->table) ) {
            $schema->create($this->table, function($table) {
                $table->bigInteger('id')->autoIncrement();
                $table->string($this->column, 180);
                $table->mediumText('data');
                $table->string('type', 50);
                $table->string('status', 50);
                $table->integer('retries');
                $table->dateTime('created');
                $table->dateTime('modified');
                $table->index('key_uid', $this->column);
                $table->index('key_type', 'type');
                $table->index('key_status', 'status');
                $table->primary('pk_id', 'id');
            });
        }
    }
}
