<?php

declare(strict_types = 1);

/**
 * Caldera Queue
 * Job queue layer, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2023 Vecode. All rights reserved
 */

namespace Caldera\Tests\Queue;

use Exception;
use RuntimeException;

use Predis\Client;

use Caldera\Queue\Adapter\RedisAdapter;
use Caldera\Queue\JobInterface;
use Caldera\Queue\Queue;
use PHPUnit\Framework\TestCase;

class QueueWithRedisAdapterTest extends TestCase {

	static protected $client;

	public static function setUpBeforeClass(): void {
		$params = [
			'scheme' => 'tcp',
			'host'   => '127.0.0.1',
			'port'   => 6379,
		];
		self::$client = new Client($params);
	}

	public static function tearDownAfterClass(): void {
		//
	}

	function testConstructor() {
		$adapter = new RedisAdapter(self::$client);
		$queue = new Queue($adapter);
		#
		$this->assertInstanceOf(RedisAdapter::class, $queue->getAdapter());
		$this->assertEquals($queue, $adapter->getQueue());
	}

	function testAddJob() {
		$adapter = new RedisAdapter(self::$client);
		$queue = new Queue($adapter);
		#
		$user = new User(1, 'foo@example.com');
		$queue->add(TestJob::class, ['user' => $user]);
		$user = new User(2, 'bar@example.com');
		$queue->add(TestJob::class, ['user' => $user]);
		$user = new User(3, 'baz@example.com');
		$queue->add(TestJob::class, ['user' => $user]);
		$user = new User(4, 'qux@example.com');
		$queue->add(TestJob::class, ['user' => $user]);
		$pending = $queue->pending(TestJob::class);
		$this->assertEquals(4, $pending);
	}

	function testWork() {
		$counter = 0;
		$adapter = new RedisAdapter(self::$client);
		$queue = new Queue($adapter);
		#
		$queue->work(function(JobInterface $job) use (&$counter) {
			$counter++;
			$retries = $job->getRetries();
			$failed = $job->hasFailed();
			if ($failed && $retries > 3) {
				$this->fail('Should not have reached more than 3 retries');
			}
		}, function(Queue $queue) {
			return true;
		});
		#
		$this->assertEquals(7, $counter);
	}

	function testResetPurge() {
		$adapter = new RedisAdapter(self::$client);
		$queue = new Queue($adapter);
		# Reset the failed ones
		$queue->reset(TestJob::class);
		$this->assertEquals(3, $queue->pending());
		# Run the queue again and purge the failed ones
		$queue->work(complete: function(Queue $queue) {
			return true;
		});
		$queue->purge(TestJob::class);
	}

	function testDummyJob() {
		$adapter = new RedisAdapter(self::$client);
		$queue = new Queue($adapter);
		#
		try {
			$queue->add(DummyJob::class, ['foo' => 'baz']);
			$queue->work(complete: function(Queue $queue) {
				return true;
			});
			$this->fail('Should have thrown a RuntimeException');
		} catch (Exception $e) {
			$this->assertInstanceOf(RuntimeException::class, $e);
		}
		$queue->purge(DummyJob::class);
	}
}
