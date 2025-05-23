<?php

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Nextcloud\KItinerary\Sys;

use Nextcloud\KItinerary\Adapter;
use Nextcloud\KItinerary\Exception\KItineraryRuntimeException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use function fclose;
use function fwrite;
use function in_array;
use function is_array;
use function is_resource;
use function json_decode;
use function proc_close;
use function proc_open;
use function stream_get_contents;

class SysAdapter implements Adapter, LoggerAwareInterface
{

	private static $isAvailable = null;

	/** @var LoggerInterface */
	private $logger;

	public function __construct()
	{
		$this->logger = new NullLogger();
	}

	/**
	 * Sets a logger instance on the object.
	 *
	 * @return void
	 */
	public function setLogger(LoggerInterface $logger): void
	{
		$this->logger = $logger;
	}

	private function canRun(): bool
	{
		if (in_array('proc_open', explode(',', ini_get('disable_functions')), true)) {
			$this->logger->warning('proc_open is disabled');
			return false;
		}

		$descriptors = [
			0 => ['pipe', 'r'],
			1 => ['pipe', 'w']
		];
		$binPath = 'kitinerary-extractor';
		$proc = proc_open($binPath, $descriptors, $pipes);
		if (!is_resource($proc)) {
			$this->logger->warning('Could not open ' . realpath($binPath));
			return false;
		}
		fclose($pipes[0]);
		fclose($pipes[1]);
		$ret = proc_close($proc);

		return $ret === 0;
	}

	public function isAvailable(): bool
	{
		if (self::$isAvailable === null) {
			self::$isAvailable = $this->canRun();
		}
		return self::$isAvailable;
	}

	public function extractIcalFromString(string $source): string
	{
		return $this->callBinary($source, ['--output','ical']);
	}

	public function extractFromString(string $source): array
	{
		$output = $this->callBinary($source, []);

		$decoded = json_decode($output, true);
		if (!is_array($decoded)) {
			$this->logger->error('Could not parse kitinerary-extract output');
			return [];
		}
		return $decoded;
	}

	private function callBinary(string $source, array $options): string
	{
		$descriptors = [
			0 => ['pipe', 'r'],
			1 => ['pipe', 'w']
		];

		$proc = proc_open(['kitinerary-extractor', ...$options], $descriptors, $pipes);
		if (!is_resource($proc)) {
			throw new KItineraryRuntimeException("Could not invoke shipped kitinerary-extractor");
		}
		fwrite($pipes[0], $source);
		fclose($pipes[0]);

		$output = stream_get_contents($pipes[1]);
		if ($output === false) {
			throw new KItineraryRuntimeException('Could not get kitinerary-extractor output');
		}
		fclose($pipes[1]);

		$ret = proc_close($proc);
		if ($ret !== 0) {
			throw new KItineraryRuntimeException("kitinerary-extractor returned exit code $ret");
		}

		return $output;
	}

}
