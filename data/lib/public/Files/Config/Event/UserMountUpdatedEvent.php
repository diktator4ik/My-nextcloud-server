<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Files\Config\Event;

use OCP\EventDispatcher\Event;
use OCP\Files\Config\ICachedMountInfo;
use OCP\Files\Mount\IMountPoint;

/**
 * Event emitted when a user mount was moved.
 *
 * @since 30.0.12
 */
class UserMountUpdatedEvent extends Event {
	public function __construct(
		public readonly IMountPoint|ICachedMountInfo $oldMountPoint,
		public readonly IMountPoint|ICachedMountInfo $newMountPoint,
	) {
		parent::__construct();
	}
}
