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

use PHPUnit\Framework\TestCase;

use Caldera\Database\Adapter\MySQLAdapter;
use Caldera\Database\Database;
use Caldera\Events\EventDispatcher;
use Caldera\Events\Listener\CallableListener;
use Caldera\Events\ListenerProvider;

use Caldera\Queue\Adapter\DatabaseAdapter;
use Caldera\Queue\Events\JobCompletedEvent;
use Caldera\Queue\Events\JobExceptionOcurredEvent;
use Caldera\Queue\Events\JobFailedEvent;
use Caldera\Queue\JobInterface;
use Caldera\Queue\Queue;

class QueueWithDatabaseAdapterTest extends TestCase {

	static protected Database $database;

	public static function setUpBeforeClass(): void {
		$options = [
			'host' => $_ENV['MYSQL_HOST'] ?? 'localhost',
			'port' => $_ENV['MYSQL_PORT'] ?? '',
			'name' => $_ENV['MYSQL_NAME'] ?? 'caldera',
			'user' => $_ENV['MYSQL_USER'] ?? 'root',
		];
		$adapter = new MySQLAdapter($options);
		self::$database = new Database($adapter);
	}

	public static function tearDownAfterClass(): void {
		self::$database->query('DROP TABLE queue');
	}

	function testConstructor() {
		$adapter = new DatabaseAdapter(self::$database);
		$queue = new Queue($adapter);
		#
		$this->assertInstanceOf(DatabaseAdapter::class, $queue->getAdapter());
		$this->assertEquals($queue, $adapter->getQueue());
	}

	function testAddJob() {
		$adapter = new DatabaseAdapter(self::$database);
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
        $provider = new ListenerProvider();
        $provider->add(JobCompletedEvent::class, new CallableListener(function(JobCompletedEvent $event) {
            $this->assertFalse($event->getJob()->hasFailed());
        }));
        $provider->add(JobFailedEvent::class, new CallableListener(function(JobFailedEvent $event) {
            $this->assertInstanceOf(RuntimeException::class, $event->getException());
        }));
        $provider->add(JobExceptionOcurredEvent::class, new CallableListener(function(JobExceptionOcurredEvent $event) {
            $this->assertInstanceOf(RuntimeException::class, $event->getException());
        }));
        $dispatcher = new EventDispatcher($provider);
		#
		$adapter = new DatabaseAdapter(self::$database);
		$queue = new Queue($adapter, $dispatcher);
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

	function testResetAndPurge() {
		$adapter = new DatabaseAdapter(self::$database);
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
		$adapter = new DatabaseAdapter(self::$database);
		$queue = new Queue($adapter);
		#
		try {
			$queue->add(DummyJob::class, ['foo' => 'baz']);
			$queue->work(complete: function(Queue $queue) {
				return true;
			});
			$queue->purge(TestJob::class);
			$this->fail('Should have thrown a RuntimeException');
		} catch (Exception $e) {
			$this->assertInstanceOf(RuntimeException::class, $e);
		}
	}
}
