<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Talk\Controller;

use OCA\Talk\Capabilities;
use OCA\Talk\Config;
use OCA\Talk\Events\AAttendeeRemovedEvent;
use OCA\Talk\Events\BeforeRoomsFetchEvent;
use OCA\Talk\Exceptions\CannotReachRemoteException;
use OCA\Talk\Exceptions\FederationRestrictionException;
use OCA\Talk\Exceptions\ForbiddenException;
use OCA\Talk\Exceptions\GuestImportException;
use OCA\Talk\Exceptions\InvalidPasswordException;
use OCA\Talk\Exceptions\ParticipantNotFoundException;
use OCA\Talk\Exceptions\ParticipantProperty\PermissionsException;
use OCA\Talk\Exceptions\RoomNotFoundException;
use OCA\Talk\Exceptions\RoomProperty\DefaultPermissionsException;
use OCA\Talk\Exceptions\RoomProperty\DescriptionException;
use OCA\Talk\Exceptions\RoomProperty\ListableException;
use OCA\Talk\Exceptions\RoomProperty\LobbyException;
use OCA\Talk\Exceptions\RoomProperty\MentionPermissionsException;
use OCA\Talk\Exceptions\RoomProperty\MessageExpirationException;
use OCA\Talk\Exceptions\RoomProperty\NameException;
use OCA\Talk\Exceptions\RoomProperty\PasswordException;
use OCA\Talk\Exceptions\RoomProperty\ReadOnlyException;
use OCA\Talk\Exceptions\RoomProperty\RecordingConsentException;
use OCA\Talk\Exceptions\RoomProperty\SipConfigurationException;
use OCA\Talk\Exceptions\RoomProperty\TypeException;
use OCA\Talk\Exceptions\UnauthorizedException;
use OCA\Talk\Federation\Authenticator;
use OCA\Talk\Federation\FederationManager;
use OCA\Talk\Federation\Proxy\TalkV1\ProxyRequest;
use OCA\Talk\GuestManager;
use OCA\Talk\Manager;
use OCA\Talk\MatterbridgeManager;
use OCA\Talk\Middleware\Attribute\FederationSupported;
use OCA\Talk\Middleware\Attribute\RequireLoggedInModeratorParticipant;
use OCA\Talk\Middleware\Attribute\RequireLoggedInParticipant;
use OCA\Talk\Middleware\Attribute\RequireModeratorOrNoLobby;
use OCA\Talk\Middleware\Attribute\RequireModeratorParticipant;
use OCA\Talk\Middleware\Attribute\RequireParticipant;
use OCA\Talk\Middleware\Attribute\RequireRoom;
use OCA\Talk\Model\Attendee;
use OCA\Talk\Model\BreakoutRoom;
use OCA\Talk\Model\Session;
use OCA\Talk\Participant;
use OCA\Talk\ResponseDefinitions;
use OCA\Talk\Room;
use OCA\Talk\Service\BanService;
use OCA\Talk\Service\BreakoutRoomService;
use OCA\Talk\Service\ChecksumVerificationService;
use OCA\Talk\Service\NoteToSelfService;
use OCA\Talk\Service\ParticipantService;
use OCA\Talk\Service\RecordingService;
use OCA\Talk\Service\RoomFormatter;
use OCA\Talk\Service\RoomService;
use OCA\Talk\Service\SessionService;
use OCA\Talk\Share\Helper\Preloader;
use OCA\Talk\TalkSession;
use OCA\Talk\Webinary;
use OCP\App\IAppManager;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\BruteForceProtection;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Services\IAppConfig;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Calendar\CalendarEventStatus;
use OCP\Calendar\Exceptions\CalendarException;
use OCP\Calendar\ICreateFromString;
use OCP\Calendar\IManager as ICalendarManager;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Federation\ICloudIdManager;
use OCP\IConfig;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IPhoneNumberUtil;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Security\Bruteforce\IThrottler;
use OCP\User\Events\UserLiveStatusEvent;
use OCP\UserStatus\IManager as IUserStatusManager;
use OCP\UserStatus\IUserStatus;
use Psr\Log\LoggerInterface;

/**
 * @psalm-import-type TalkCapabilities from ResponseDefinitions
 * @psalm-import-type TalkParticipant from ResponseDefinitions
 * @psalm-import-type TalkRoom from ResponseDefinitions
 */
class RoomController extends AEnvironmentAwareOCSController {
	protected array $commonReadMessages = [];

	public function __construct(
		string $appName,
		protected ?string $userId,
		IRequest $request,
		protected IAppManager $appManager,
		protected TalkSession $session,
		protected IUserManager $userManager,
		protected IGroupManager $groupManager,
		protected Manager $manager,
		protected RoomService $roomService,
		protected BreakoutRoomService $breakoutRoomService,
		protected NoteToSelfService $noteToSelfService,
		protected ParticipantService $participantService,
		protected SessionService $sessionService,
		protected GuestManager $guestManager,
		protected IUserStatusManager $statusManager,
		protected ICalendarManager $calendarManager,
		protected IEventDispatcher $dispatcher,
		protected ITimeFactory $timeFactory,
		protected ChecksumVerificationService $checksumVerificationService,
		protected RoomFormatter $roomFormatter,
		protected Preloader $sharePreloader,
		protected IConfig $config,
		protected IAppConfig $appConfig,
		protected Config $talkConfig,
		protected ICloudIdManager $cloudIdManager,
		protected IPhoneNumberUtil $phoneNumberUtil,
		protected IThrottler $throttler,
		protected LoggerInterface $logger,
		protected Authenticator $federationAuthenticator,
		protected Capabilities $capabilities,
		protected FederationManager $federationManager,
		protected BanService $banService,
		protected IURLGenerator $url,
		protected IL10N $l,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @return array{X-Nextcloud-Talk-Hash: string}
	 */
	protected function getTalkHashHeader(): array {
		$values = [
			$this->config->getSystemValueString('version'),
			$this->config->getAppValue('spreed', 'installed_version'),
			$this->config->getAppValue('spreed', 'stun_servers'),
			$this->config->getAppValue('spreed', 'turn_servers'),
			$this->config->getAppValue('spreed', 'signaling_servers'),
			$this->config->getAppValue('spreed', 'signaling_mode'),
			$this->config->getAppValue('spreed', 'signaling_ticket_secret'),
			$this->config->getAppValue('spreed', 'signaling_token_alg', 'ES256'),
			$this->config->getAppValue('spreed', 'signaling_token_privkey_' . $this->config->getAppValue('spreed', 'signaling_token_alg', 'ES256')),
			$this->config->getAppValue('spreed', 'signaling_token_pubkey_' . $this->config->getAppValue('spreed', 'signaling_token_alg', 'ES256')),
			$this->config->getAppValue('spreed', 'call_recording'),
			$this->config->getAppValue('spreed', 'recording_servers'),
			$this->config->getAppValue('spreed', 'allowed_groups'),
			$this->config->getAppValue('spreed', 'start_calls'),
			$this->config->getAppValue('spreed', 'start_conversations'),
			$this->config->getAppValue('spreed', 'default_permissions'),
			$this->config->getAppValue('spreed', 'breakout_rooms'),
			$this->config->getAppValue('spreed', 'federation_enabled'),
			$this->config->getAppValue('spreed', 'enable_matterbridge'),
			$this->config->getAppValue('spreed', 'has_reference_id'),
			$this->config->getAppValue('spreed', 'sip_bridge_groups', '[]'),
			$this->config->getAppValue('spreed', 'sip_bridge_dialin_info'),
			$this->config->getAppValue('spreed', 'sip_bridge_shared_secret'),
			$this->config->getAppValue('spreed', 'recording_consent'),
			$this->config->getAppValue('spreed', 'call_recording_transcription'),
			$this->config->getAppValue('spreed', 'call_recording_summary'),
			$this->config->getAppValue('theming', 'cachebuster', '1'),
			$this->config->getUserValue($this->userId, 'theming', 'userCacheBuster', '0'),
			$this->config->getAppValue('spreed', 'federation_incoming_enabled'),
			$this->config->getAppValue('spreed', 'federation_outgoing_enabled'),
			$this->config->getAppValue('spreed', 'federation_only_trusted_servers'),
			$this->config->getAppValue('spreed', 'federation_allowed_groups', '[]'),
		];

		return [
			'X-Nextcloud-Talk-Hash' => sha1(implode('#', $values)),
		];
	}

	/**
	 * Get all currently existent rooms which the user has joined
	 *
	 * @param 0|1 $noStatusUpdate When the user status should not be automatically set to online set to 1 (default 0)
	 * @param bool $includeStatus Include the user status
	 * @param int $modifiedSince Filter rooms modified after a timestamp
	 * @param bool $includeLastMessage Include the last message, clients should opt-out when only rendering a compact list
	 * @psalm-param non-negative-int $modifiedSince
	 * @return DataResponse<Http::STATUS_OK, list<TalkRoom>, array{X-Nextcloud-Talk-Hash: string, X-Nextcloud-Talk-Modified-Before: numeric-string, X-Nextcloud-Talk-Federation-Invites?: numeric-string}>
	 *
	 * 200: Return list of rooms
	 */
	#[NoAdminRequired]
	public function getRooms(int $noStatusUpdate = 0, bool $includeStatus = false, int $modifiedSince = 0, bool $includeLastMessage = true): DataResponse {
		$nextModifiedSince = $this->timeFactory->getTime();

		$event = new BeforeRoomsFetchEvent($this->userId);
		$this->dispatcher->dispatchTyped($event);
		$user = $this->userManager->get($this->userId);

		if ($noStatusUpdate === 0) {
			$isMobileApp = $this->request->isUserAgent([
				IRequest::USER_AGENT_TALK_ANDROID,
				IRequest::USER_AGENT_TALK_IOS,
			]);

			if ($isMobileApp) {
				// Bump the user status again
				$event = new UserLiveStatusEvent(
					$user,
					IUserStatus::ONLINE,
					$this->timeFactory->getTime()
				);
				$this->dispatcher->dispatchTyped($event);
			}
		}

		$sessionIds = $this->session->getAllActiveSessions();
		$rooms = $this->manager->getRoomsForUser($this->userId, $sessionIds, $includeLastMessage);

		if ($modifiedSince !== 0) {
			$rooms = array_filter($rooms, function (Room $room) use ($includeStatus, $modifiedSince): bool {
				if ($includeStatus && $room->getType() === Room::TYPE_ONE_TO_ONE) {
					// Always include 1-1s to update the user status
					return true;
				}
				if ($room->getCallFlag() !== Participant::FLAG_DISCONNECTED) {
					// Always include active calls
					return true;
				}
				if ($room->getLastActivity() && $room->getLastActivity()->getTimestamp() >= $modifiedSince) {
					// Include rooms which had activity
					return true;
				}

				// Include rooms where only attendee level things changed,
				// e.g. favorite, read-marker update, notification setting
				$attendee = $room->getParticipant($this->userId)->getAttendee();
				return $attendee->getLastAttendeeActivity() >= $modifiedSince;
			});
		}

		$readPrivacy = $this->talkConfig->getUserReadPrivacy($this->userId);
		if ($readPrivacy === Participant::PRIVACY_PUBLIC) {
			$roomIds = array_map(static function (Room $room) {
				return $room->getId();
			}, $rooms);
			$this->commonReadMessages = $this->participantService->getLastCommonReadChatMessageForMultipleRooms($roomIds);
		}

		$statuses = [];
		if ($includeStatus
			&& $this->appManager->isEnabledForUser('user_status')) {
			$userIds = array_filter(array_map(function (Room $room) {
				if ($room->getType() === Room::TYPE_ONE_TO_ONE) {
					$participants = json_decode($room->getName(), true);
					foreach ($participants as $participant) {
						if ($participant !== $this->userId) {
							return $participant;
						}
					}
				}
				return null;
			}, $rooms));

			$statuses = $this->statusManager->getUserStatuses($userIds);
		}

		if ($includeLastMessage) {
			$lastMessages = array_filter(array_map(static fn (Room $room) => $room->getLastMessage()?->getVerb() === 'object_shared' ? $room->getLastMessage() : null, $rooms));
			$this->sharePreloader->preloadShares($lastMessages);
		}

		$return = [];
		foreach ($rooms as $room) {
			try {
				$return[] = $this->formatRoom($room, $this->participantService->getParticipant($room, $this->userId), $statuses, skipLastMessage: !$includeLastMessage);
			} catch (ParticipantNotFoundException $e) {
				// for example in case the room was deleted concurrently,
				// the user is not a participant anymore
			}
		}

		/** @var array{X-Nextcloud-Talk-Modified-Before: numeric-string, X-Nextcloud-Talk-Federation-Invites?: numeric-string} $headers */
		$headers = ['X-Nextcloud-Talk-Modified-Before' => (string)$nextModifiedSince];
		if ($this->talkConfig->isFederationEnabledForUserId($user)) {
			$numInvites = $this->federationManager->getNumberOfPendingInvitationsForUser($user);
			if ($numInvites !== 0) {
				$headers['X-Nextcloud-Talk-Federation-Invites'] = (string)$numInvites;
			}
		}

		/** @var array{X-Nextcloud-Talk-Hash: string, X-Nextcloud-Talk-Modified-Before: numeric-string, X-Nextcloud-Talk-Federation-Invites?: numeric-string} $headers */
		$headers = array_merge($this->getTalkHashHeader(), $headers);

		return new DataResponse($return, Http::STATUS_OK, $headers);
	}

	/**
	 * Get listed rooms with optional search term
	 *
	 * @param string $searchTerm search term
	 * @return DataResponse<Http::STATUS_OK, list<TalkRoom>, array{}>
	 *
	 * 200: Return list of matching rooms
	 */
	#[NoAdminRequired]
	public function getListedRooms(string $searchTerm = ''): DataResponse {
		$rooms = $this->manager->getListedRoomsForUser($this->userId, $searchTerm);

		$return = [];
		foreach ($rooms as $room) {
			$return[] = $this->formatRoom($room, null, skipLastMessage: true);
		}

		return new DataResponse($return, Http::STATUS_OK);
	}

	/**
	 * Get breakout rooms
	 *
	 * All for moderators and in case of "free selection", or the assigned breakout room for other participants
	 *
	 * @return DataResponse<Http::STATUS_OK, list<TalkRoom>, array{}>|DataResponse<Http::STATUS_BAD_REQUEST, array{error: string}, array{}>
	 *
	 * 200: Breakout rooms returned
	 * 400: Getting breakout rooms is not possible
	 */
	#[NoAdminRequired]
	#[BruteForceProtection(action: 'talkRoomToken')]
	#[RequireLoggedInParticipant]
	public function getBreakoutRooms(): DataResponse {
		try {
			$rooms = $this->breakoutRoomService->getBreakoutRooms($this->room, $this->participant);
		} catch (\InvalidArgumentException $e) {
			return new DataResponse(['error' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
		}

		$return = [];
		foreach ($rooms as $room) {
			try {
				$participant = $this->participantService->getParticipant($room, $this->userId);
			} catch (ParticipantNotFoundException $e) {
				$participant = null;
			}

			$return[] = $this->formatRoom($room, $participant, null, false, true, true);
		}


		return new DataResponse($return);
	}

	/**
	 * Get a room
	 *
	 * @param string $token Token of the room
	 * @return DataResponse<Http::STATUS_OK, TalkRoom, array{X-Nextcloud-Talk-Hash: string}>|DataResponse<Http::STATUS_UNAUTHORIZED|Http::STATUS_NOT_FOUND, null, array{}>
	 *
	 * 200: Room returned
	 * 401: SIP request invalid
	 * 404: Room not found
	 */
	#[PublicPage]
	#[BruteForceProtection(action: 'talkFederationAccess')]
	#[BruteForceProtection(action: 'talkRoomToken')]
	#[BruteForceProtection(action: 'talkSipBridgeSecret')]
	#[OpenAPI]
	#[OpenAPI(scope: 'backend-sipbridge')]
	public function getSingleRoom(string $token): DataResponse {
		try {
			$isSIPBridgeRequest = $this->validateSIPBridgeRequest($token);
		} catch (UnauthorizedException $e) {
			/**
			 * A hack to fix type collision
			 * @var DataResponse<Http::STATUS_UNAUTHORIZED, null, array{}> $response
			 */
			$response = new DataResponse([], Http::STATUS_UNAUTHORIZED);
			$response->throttle(['action' => 'talkSipBridgeSecret']);
			return $response;
		}

		// The SIP bridge only needs room details (public, sip enabled, lobby state, etc)
		$includeLastMessage = !$isSIPBridgeRequest;

		try {
			$action = 'talkRoomToken';
			$participant = null;

			$isTalkFederation = $this->request->getHeader('X-Nextcloud-Federation');

			if (!$isTalkFederation) {
				$sessionId = $this->session->getSessionForRoom($token);
				$room = $this->manager->getRoomForUserByToken($token, $this->userId, $sessionId, $includeLastMessage, $isSIPBridgeRequest);

				try {
					$participant = $this->participantService->getParticipant($room, $this->userId, $sessionId);
				} catch (ParticipantNotFoundException $e) {
					try {
						$participant = $this->participantService->getParticipantBySession($room, $sessionId);
					} catch (ParticipantNotFoundException $e) {
					}
				}
			} else {
				$action = 'talkFederationAccess';
				try {
					$room = $this->federationAuthenticator->getRoom();
				} catch (RoomNotFoundException) {
					$room = $this->manager->getRoomByRemoteAccess(
						$token,
						Attendee::ACTOR_FEDERATED_USERS,
						$this->federationAuthenticator->getCloudId(),
						$this->federationAuthenticator->getAccessToken(),
					);
				}
				try {
					$participant = $this->federationAuthenticator->getParticipant();
				} catch (ParticipantNotFoundException) {
					$participant = $this->participantService->getParticipantByActor(
						$room,
						Attendee::ACTOR_FEDERATED_USERS,
						$this->federationAuthenticator->getCloudId(),
					);
					$this->federationAuthenticator->authenticated($room, $participant);
				}
			}

			$statuses = [];
			if ($this->userId !== null
				&& $this->appManager->isEnabledForUser('user_status')) {
				$userIds = array_filter(array_map(function (Room $room) {
					if ($room->getType() === Room::TYPE_ONE_TO_ONE) {
						$participants = json_decode($room->getName(), true);
						foreach ($participants as $participant) {
							if ($participant !== $this->userId) {
								return $participant;
							}
						}
					}
					return null;
				}, [$room]));

				$statuses = $this->statusManager->getUserStatuses($userIds);
			}

			return new DataResponse($this->formatRoom($room, $participant, $statuses, $isSIPBridgeRequest), Http::STATUS_OK, $this->getTalkHashHeader());
		} catch (RoomNotFoundException $e) {
			/**
			 * A hack to fix type collision
			 * @var DataResponse<Http::STATUS_NOT_FOUND, null, array{}> $response
			 */
			$response = new DataResponse([], Http::STATUS_NOT_FOUND);
			$response->throttle(['token' => $token, 'action' => $action]);
			return $response;
		}
	}

	/**
	 * Get the "Note to self" conversation for the user
	 *
	 * It will be automatically created when it is currently missing
	 *
	 * @return DataResponse<Http::STATUS_OK, TalkRoom, array{X-Nextcloud-Talk-Hash: string}>
	 *
	 * 200: Room returned successfully
	 */
	#[NoAdminRequired]
	public function getNoteToSelfConversation(): DataResponse {
		$room = $this->noteToSelfService->ensureNoteToSelfExistsForUser($this->userId);
		$participant = $this->participantService->getParticipant($room, $this->userId, false);
		return new DataResponse($this->formatRoom($room, $participant), Http::STATUS_OK, $this->getTalkHashHeader());
	}

	/**
	 * Check if the current request is coming from an allowed backend.
	 *
	 * The SIP bridge is sending the custom header "Talk-SIPBridge-Random"
	 * containing at least 32 bytes random data, and the header
	 * "Talk-SIPBridge-Checksum", which is the SHA256-HMAC of the random data
	 * and the body of the request, calculated with the shared secret from the
	 * configuration.
	 *
	 * @param string $token
	 * @return bool True if the request is from the SIP bridge and valid, false if not from SIP bridge
	 * @throws UnauthorizedException when the request tried to sign as SIP bridge but is not valid
	 */
	private function validateSIPBridgeRequest(string $token): bool {
		$random = $this->request->getHeader('TALK_SIPBRIDGE_RANDOM');
		$checksum = $this->request->getHeader('TALK_SIPBRIDGE_CHECKSUM');
		$secret = $this->talkConfig->getSIPSharedSecret();
		return $this->checksumVerificationService->validateRequest($random, $checksum, $secret, $token);
	}

	/**
	 * @return TalkRoom
	 */
	protected function formatRoom(
		Room $room,
		?Participant $currentParticipant,
		?array $statuses = null,
		bool $isSIPBridgeRequest = false,
		bool $isListingBreakoutRooms = false,
		bool $skipLastMessage = false,
	): array {
		return $this->roomFormatter->formatRoom(
			$this->getResponseFormat(),
			$this->commonReadMessages,
			$room,
			$currentParticipant,
			$statuses,
			$isSIPBridgeRequest,
			$isListingBreakoutRooms,
			$skipLastMessage,
		);
	}

	/**
	 * Create a room with a user, a group or a circle
	 *
	 * @param int $roomType Type of the room
	 * @psalm-param Room::TYPE_* $roomType
	 * @param string $invite User, group, … ID to invite
	 * @param string $roomName Name of the room
	 * @param 'groups'|'circles'|'' $source Source of the invite ID ('circles' to create a room with a circle, etc.)
	 * @param string $objectType Type of the object
	 * @param string $objectId ID of the object
	 * @param string $password The room password (only available with `conversation-creation-password` capability)
	 * @return DataResponse<Http::STATUS_OK|Http::STATUS_CREATED, TalkRoom, array{}>|DataResponse<Http::STATUS_BAD_REQUEST|Http::STATUS_FORBIDDEN|Http::STATUS_NOT_FOUND, array{error: 'invite'|'mode'|'object'|'password'|'permissions'|'room'|'type', message?: string}, array{}>
	 *
	 * 200: Room already existed
	 * 201: Room created successfully
	 * 400: Room type invalid or missing or invalid password
	 * 403: Missing permissions to create room
	 * 404: User, group or other target to invite was not found
	 */
	#[NoAdminRequired]
	public function createRoom(
		int $roomType,
		string $invite = '',
		string $roomName = '',
		string $source = '',
		string $objectType = '',
		string $objectId = '',
		string $password = '',
	): DataResponse {
		if ($roomType !== Room::TYPE_ONE_TO_ONE) {
			/** @var IUser $user */
			$user = $this->userManager->get($this->userId);

			if ($this->talkConfig->isNotAllowedToCreateConversations($user)) {
				return new DataResponse(['error' => 'permissions'], Http::STATUS_FORBIDDEN);
			}
		}

		switch ($roomType) {
			case Room::TYPE_ONE_TO_ONE:
				return $this->createOneToOneRoom($invite);
			case Room::TYPE_GROUP:
				if ($invite === '') {
					return $this->createEmptyRoom($roomName, false, $objectType, $objectId);
				}
				if ($source === 'circles') {
					return $this->createCircleRoom($invite);
				}
				return $this->createGroupRoom($invite);
			case Room::TYPE_PUBLIC:
				return $this->createEmptyRoom($roomName, true, $objectType, $objectId, $password);
		}

		return new DataResponse(['error' => 'type'], Http::STATUS_BAD_REQUEST);
	}

	/**
	 * Initiates a one-to-one video call from the current user to the recipient
	 *
	 * @param string $targetUserId ID of the user
	 * @return DataResponse<Http::STATUS_OK|Http::STATUS_CREATED, TalkRoom, array{}>|DataResponse<Http::STATUS_FORBIDDEN|Http::STATUS_NOT_FOUND, array{error: 'invite'}, array{}>
	 */
	#[NoAdminRequired]
	protected function createOneToOneRoom(string $targetUserId): DataResponse {
		$currentUser = $this->userManager->get($this->userId);
		if (!$currentUser instanceof IUser) {
			// Should never happen, basically an internal server error so we reuse another error
			return new DataResponse(['error' => 'invite'], Http::STATUS_NOT_FOUND);
		}

		if ($targetUserId === MatterbridgeManager::BRIDGE_BOT_USERID) {
			return new DataResponse(['error' => 'invite'], Http::STATUS_NOT_FOUND);
		}

		$targetUser = $this->userManager->get($targetUserId);
		if (!$targetUser instanceof IUser) {
			return new DataResponse(['error' => 'invite'], Http::STATUS_NOT_FOUND);
		}

		try {
			// We are only doing this manually here to be able to return different status codes
			// Actually createOneToOneConversation also checks it.
			$room = $this->manager->getOne2OneRoom($currentUser->getUID(), $targetUser->getUID());
			$this->participantService->ensureOneToOneRoomIsFilled($room, $currentUser->getUID());
			return new DataResponse(
				$this->formatRoom($room, $this->participantService->getParticipant($room, $currentUser->getUID(), false)),
				Http::STATUS_OK
			);
		} catch (RoomNotFoundException $e) {
		}

		try {
			$room = $this->roomService->createOneToOneConversation($currentUser, $targetUser);
			return new DataResponse(
				$this->formatRoom($room, $this->participantService->getParticipant($room, $currentUser->getUID(), false)),
				Http::STATUS_CREATED
			);
		} catch (\InvalidArgumentException $e) {
			// Same current and target user
			return new DataResponse(['error' => 'invite'], Http::STATUS_FORBIDDEN);
		} catch (RoomNotFoundException $e) {
			return new DataResponse(['error' => 'invite'], Http::STATUS_FORBIDDEN);
		}
	}

	/**
	 * Initiates a group video call from the selected group
	 *
	 * @param string $targetGroupName
	 * @return DataResponse<Http::STATUS_CREATED, TalkRoom, array{}>|DataResponse<Http::STATUS_NOT_FOUND, array{error: 'invite'}, array{}>
	 */
	#[NoAdminRequired]
	protected function createGroupRoom(string $targetGroupName): DataResponse {
		$currentUser = $this->userManager->get($this->userId);
		if (!$currentUser instanceof IUser) {
			// Should never happen, basically an internal server error so we reuse another error
			return new DataResponse(['error' => 'invite'], Http::STATUS_NOT_FOUND);
		}

		$targetGroup = $this->groupManager->get($targetGroupName);
		if (!$targetGroup instanceof IGroup) {
			return new DataResponse(['error' => 'invite'], Http::STATUS_NOT_FOUND);
		}

		// Create the room
		$name = $this->roomService->prepareConversationName($targetGroup->getDisplayName());
		$room = $this->roomService->createConversation(Room::TYPE_GROUP, $name, $currentUser);
		$this->participantService->addGroup($room, $targetGroup);

		return new DataResponse($this->formatRoom($room, $this->participantService->getParticipant($room, $currentUser->getUID(), false)), Http::STATUS_CREATED);
	}

	/**
	 * Initiates a group video call from the selected circle
	 *
	 * @param string $targetCircleId
	 * @return DataResponse<Http::STATUS_CREATED, TalkRoom, array{}>|DataResponse<Http::STATUS_BAD_REQUEST|Http::STATUS_NOT_FOUND, array{error: 'invite'}, array{}>
	 */
	#[NoAdminRequired]
	protected function createCircleRoom(string $targetCircleId): DataResponse {
		if (!$this->appManager->isEnabledForUser('circles')) {
			return new DataResponse(['error' => 'invite'], Http::STATUS_BAD_REQUEST);
		}

		$currentUser = $this->userManager->get($this->userId);
		if (!$currentUser instanceof IUser) {
			// Should never happen, basically an internal server error so we reuse another error
			return new DataResponse(['error' => 'invite'], Http::STATUS_NOT_FOUND);
		}

		try {
			$circle = $this->participantService->getCircle($targetCircleId, $this->userId);
		} catch (\Exception $e) {
			return new DataResponse(['error' => 'invite'], Http::STATUS_NOT_FOUND);
		}

		// Create the room
		$name = $this->roomService->prepareConversationName($circle->getName());
		$room = $this->roomService->createConversation(Room::TYPE_GROUP, $name, $currentUser);
		$this->participantService->addCircle($room, $circle);

		return new DataResponse($this->formatRoom($room, $this->participantService->getParticipant($room, $currentUser->getUID(), false)), Http::STATUS_CREATED);
	}

	/**
	 * @return DataResponse<Http::STATUS_CREATED, TalkRoom, array{}>|DataResponse<Http::STATUS_BAD_REQUEST|Http::STATUS_NOT_FOUND, array{error: 'invite'|'mode'|'object'|'password'|'permissions'|'room', message?: string}, array{}>
	 */
	#[NoAdminRequired]
	protected function createEmptyRoom(string $roomName, bool $public = true, string $objectType = '', string $objectId = '', string $password = ''): DataResponse {
		$currentUser = $this->userManager->get($this->userId);
		if (!$currentUser instanceof IUser) {
			// Should never happen, basically an internal server error so we reuse another error
			return new DataResponse(['error' => 'invite'], Http::STATUS_NOT_FOUND);
		}

		$roomType = $public ? Room::TYPE_PUBLIC : Room::TYPE_GROUP;
		/** @var Room|null $parentRoom */
		$parentRoom = null;

		if ($objectType === BreakoutRoom::PARENT_OBJECT_TYPE) {
			try {
				$parentRoom = $this->manager->getRoomForUserByToken($objectId, $this->userId);
				$parentRoomParticipant = $this->participantService->getParticipant($parentRoom, $this->userId);

				if (!$parentRoomParticipant->hasModeratorPermissions()) {
					return new DataResponse(['error' => 'permissions'], Http::STATUS_BAD_REQUEST);
				}
				if ($parentRoom->getBreakoutRoomMode() === BreakoutRoom::MODE_NOT_CONFIGURED) {
					return new DataResponse(['error' => 'mode'], Http::STATUS_BAD_REQUEST);
				}

				// Overwriting the type with the parent type.
				$roomType = $parentRoom->getType();
			} catch (RoomNotFoundException $e) {
				return new DataResponse(['error' => 'room'], Http::STATUS_BAD_REQUEST);
			} catch (ParticipantNotFoundException $e) {
				return new DataResponse(['error' => 'permissions'], Http::STATUS_BAD_REQUEST);
			}
		} elseif ($objectType === Room::OBJECT_TYPE_PHONE) {
			// Ignoring any user input on this one
			$objectId = $objectType;
		} elseif ($objectType === Room::OBJECT_TYPE_EVENT) {
			// Allow event rooms in future versions without breaking in older talk versions that the same calendar version supports
			$objectType = '';
			$objectId = '';
		} elseif ($objectType !== '') {
			return new DataResponse(['error' => 'object'], Http::STATUS_BAD_REQUEST);
		}

		// Create the room
		try {
			$room = $this->roomService->createConversation($roomType, $roomName, $currentUser, $objectType, $objectId, $password);
		} catch (PasswordException $e) {
			return new DataResponse(['error' => 'password', 'message' => $e->getHint()], Http::STATUS_BAD_REQUEST);
		} catch (\InvalidArgumentException $e) {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}

		$currentParticipant = $this->participantService->getParticipant($room, $currentUser->getUID(), false);
		if ($objectType === BreakoutRoom::PARENT_OBJECT_TYPE) {
			// Enforce the lobby state when breakout rooms are disabled
			if ($parentRoom instanceof Room && $parentRoom->getBreakoutRoomStatus() === BreakoutRoom::STATUS_STOPPED) {
				$this->roomService->setLobby($room, Webinary::LOBBY_NON_MODERATORS, null, false, false);
			}

			$participants = $this->participantService->getParticipantsForRoom($parentRoom);
			$moderators = array_filter($participants, static function (Participant $participant) use ($currentParticipant) {
				return $participant->hasModeratorPermissions()
					&& $participant->getAttendee()->getId() !== $currentParticipant->getAttendee()->getId();
			});
			if (!empty($moderators)) {
				$this->breakoutRoomService->addModeratorsToBreakoutRooms([$room], $moderators);
			}
		}

		return new DataResponse($this->formatRoom($room, $currentParticipant), Http::STATUS_CREATED);
	}

	/**
	 * Add a room to the favorites
	 *
	 * @return DataResponse<Http::STATUS_OK, TalkRoom, array{}>
	 *
	 * 200: Successfully added room to favorites
	 */
	#[FederationSupported]
	#[NoAdminRequired]
	#[RequireLoggedInParticipant]
	public function addToFavorites(): DataResponse {
		$this->participantService->updateFavoriteStatus($this->participant, true);
		return new DataResponse($this->formatRoom($this->room, $this->participant));
	}

	/**
	 * Remove a room from the favorites
	 *
	 * @return DataResponse<Http::STATUS_OK, TalkRoom, array{}>
	 *
	 * 200: Successfully removed room from favorites
	 */
	#[FederationSupported]
	#[NoAdminRequired]
	#[RequireLoggedInParticipant]
	public function removeFromFavorites(): DataResponse {
		$this->participantService->updateFavoriteStatus($this->participant, false);
		return new DataResponse($this->formatRoom($this->room, $this->participant));
	}

	/**
	 * Update the notification level for a room
	 *
	 * @param int $level New level
	 * @psalm-param Participant::NOTIFY_* $level
	 * @return DataResponse<Http::STATUS_OK, TalkRoom, array{}>|DataResponse<Http::STATUS_BAD_REQUEST, array{error: 'level'}, array{}>
	 *
	 * 200: Notification level updated successfully
	 * 400: Updating notification level is not possible
	 */
	#[FederationSupported]
	#[NoAdminRequired]
	#[RequireLoggedInParticipant]
	public function setNotificationLevel(int $level): DataResponse {
		try {
			$this->participantService->updateNotificationLevel($this->participant, $level);
		} catch (\InvalidArgumentException) {
			return new DataResponse(['error' => 'level'], Http::STATUS_BAD_REQUEST);
		}

		return new DataResponse($this->formatRoom($this->room, $this->participant));
	}

	/**
	 * Update call notifications
	 *
	 * @param int $level New level
	 * @psalm-param Participant::NOTIFY_CALLS_* $level
	 * @return DataResponse<Http::STATUS_OK, TalkRoom, array{}>|DataResponse<Http::STATUS_BAD_REQUEST, array{error: 'level'}, array{}>
	 *
	 * 200: Call notification level updated successfully
	 * 400: Updating call notification level is not possible
	 */
	#[NoAdminRequired]
	#[RequireLoggedInParticipant]
	public function setNotificationCalls(int $level): DataResponse {
		try {
			$this->participantService->updateNotificationCalls($this->participant, $level);
		} catch (\InvalidArgumentException) {
			return new DataResponse(['error' => 'level'], Http::STATUS_BAD_REQUEST);
		}

		return new DataResponse($this->formatRoom($this->room, $this->participant));
	}

	/**
	 * Rename a room
	 *
	 * @param string $roomName New name
	 * @return DataResponse<Http::STATUS_OK, TalkRoom, array{}>|DataResponse<Http::STATUS_BAD_REQUEST, array{error: 'type'|'value'}, array{}>
	 *
	 * 200: Room renamed successfully
	 * 400: Renaming room is not possible
	 */
	#[PublicPage]
	#[RequireModeratorParticipant]
	public function renameRoom(string $roomName): DataResponse {
		try {
			$this->roomService->setName($this->room, $roomName, validateType: true);
		} catch (NameException $e) {
			return new DataResponse(['error' => $e->getReason()], Http::STATUS_BAD_REQUEST);
		}
		return new DataResponse($this->formatRoom($this->room, $this->participant));
	}

	/**
	 * Update the description of a room
	 *
	 * @param string $description New description for the conversation (limited to 2.000 characters, was 500 before Talk 21)
	 * @return DataResponse<Http::STATUS_OK, TalkRoom, array{}>|DataResponse<Http::STATUS_BAD_REQUEST, array{error: 'type'|'value'}, array{}>
	 *
	 * 200: Description updated successfully
	 * 400: Updating description is not possible
	 */
	#[PublicPage]
	#[RequireModeratorParticipant]
	public function setDescription(string $description): DataResponse {
		try {
			$this->roomService->setDescription($this->room, $description);
		} catch (DescriptionException $e) {
			return new DataResponse(['error' => $e->getReason()], Http::STATUS_BAD_REQUEST);
		}

		return new DataResponse($this->formatRoom($this->room, $this->participant));
	}

	/**
	 * Delete a room
	 *
	 * @return DataResponse<Http::STATUS_OK|Http::STATUS_BAD_REQUEST, null, array{}>
	 *
	 * 200: Room successfully deleted
	 * 400: Deleting room is not possible
	 */
	#[PublicPage]
	#[RequireModeratorParticipant]
	public function deleteRoom(): DataResponse {
		if (!$this->appConfig->getAppValueBool('delete_one_to_one_conversations')
			&& in_array($this->room->getType(), [Room::TYPE_ONE_TO_ONE, Room::TYPE_ONE_TO_ONE_FORMER], true)) {
			return new DataResponse(null, Http::STATUS_BAD_REQUEST);
		}

		$this->roomService->deleteRoom($this->room);

		return new DataResponse(null);
	}

	/**
	 *
	 * Get a list of participants for a room
	 *
	 * @param bool $includeStatus Include the user statuses
	 * @return DataResponse<Http::STATUS_OK, list<TalkParticipant>, array{X-Nextcloud-Has-User-Statuses?: bool}>|DataResponse<Http::STATUS_FORBIDDEN, null, array{}>
	 *
	 * 200: Participants returned
	 * 403: Missing permissions for getting participants
	 */
	#[FederationSupported]
	#[PublicPage]
	#[RequireModeratorOrNoLobby]
	#[RequireParticipant]
	public function getParticipants(bool $includeStatus = false): DataResponse {
		if ($this->room->isFederatedConversation()) {
			/** @var \OCA\Talk\Federation\Proxy\TalkV1\Controller\RoomController $proxy */
			$proxy = \OCP\Server::get(\OCA\Talk\Federation\Proxy\TalkV1\Controller\RoomController::class);
			$response = $proxy->getParticipants($this->room, $this->participant);
			$data = $response->getData();

			if ($this->userId !== null
				&& $includeStatus
				&& count($data) < Config::USER_STATUS_INTEGRATION_LIMIT
				&& $this->appManager->isEnabledForUser('user_status')) {
				$userIds = array_filter(array_map(static function (array $parsedParticipant): ?string {
					if ($parsedParticipant['actorType'] === Attendee::ACTOR_USERS) {
						return $parsedParticipant['actorId'];
					}
					return null;
				}, $data));

				$statuses = $this->statusManager->getUserStatuses($userIds);
				$data = array_map(static function (array $parsedParticipant) use ($statuses): array {
					if ($parsedParticipant['actorType'] === Attendee::ACTOR_USERS
						&& isset($statuses[$parsedParticipant['actorId']])) {
						$userId = $parsedParticipant['actorId'];
						if (isset($statuses[$userId])) {
							$parsedParticipant['status'] = $statuses[$userId]->getStatus();
							$parsedParticipant['statusIcon'] = $statuses[$userId]->getIcon();
							$parsedParticipant['statusMessage'] = $statuses[$userId]->getMessage();
							$parsedParticipant['statusClearAt'] = $statuses[$userId]->getClearAt()?->getTimestamp();
						} else {
							$parsedParticipant['status'] = IUserStatus::OFFLINE;
							$parsedParticipant['statusIcon'] = null;
							$parsedParticipant['statusMessage'] = null;
							$parsedParticipant['statusClearAt'] = null;
						}
					}
					return $parsedParticipant;
				}, $data);
			}

			$response->setData($data);
			return $response;
		}

		if ($this->participant->getAttendee()->getParticipantType() === Participant::GUEST) {
			return new DataResponse(null, Http::STATUS_FORBIDDEN);
		}

		$participants = $this->participantService->getSessionsAndParticipantsForRoom($this->room);

		return $this->formatParticipantList($participants, $includeStatus);
	}

	/**
	 * Get the breakout room participants for a room
	 *
	 * @param bool $includeStatus Include the user statuses
	 * @return DataResponse<Http::STATUS_OK, list<TalkParticipant>, array{X-Nextcloud-Has-User-Statuses?: bool}>|DataResponse<Http::STATUS_BAD_REQUEST, array{error: string}, array{}>|DataResponse<Http::STATUS_FORBIDDEN, null, array{}>
	 *
	 * 200: Breakout room participants returned
	 * 400: Getting breakout room participants is not possible
	 * 403: Missing permissions to get breakout room participants
	 */
	#[PublicPage]
	#[RequireModeratorOrNoLobby]
	#[RequireParticipant]
	public function getBreakoutRoomParticipants(bool $includeStatus = false): DataResponse {
		if ($this->participant->getAttendee()->getParticipantType() === Participant::GUEST) {
			return new DataResponse(null, Http::STATUS_FORBIDDEN);
		}

		try {
			$breakoutRooms = $this->breakoutRoomService->getBreakoutRooms($this->room, $this->participant);
		} catch (\InvalidArgumentException $e) {
			return new DataResponse(['error' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
		}

		$breakoutRooms[] = $this->room;
		$participants = $this->participantService->getSessionsAndParticipantsForRooms($breakoutRooms);

		return $this->formatParticipantList($participants, $includeStatus);
	}

	/**
	 * @param Participant[] $participants
	 * @param bool $includeStatus
	 * @return DataResponse<Http::STATUS_OK, list<TalkParticipant>, array{X-Nextcloud-Has-User-Statuses?: true}>
	 */
	protected function formatParticipantList(array $participants, bool $includeStatus): DataResponse {
		$results = $headers = $statuses = [];
		$maxPingAge = $this->timeFactory->getTime() - Session::SESSION_TIMEOUT_KILL;

		if ($this->userId !== null
			&& $includeStatus
			&& count($participants) < Config::USER_STATUS_INTEGRATION_LIMIT
			&& $this->appManager->isEnabledForUser('user_status')) {
			$userIds = array_filter(array_map(static function (Participant $participant): ?string {
				if ($participant->getAttendee()->getActorType() === Attendee::ACTOR_USERS) {
					return $participant->getAttendee()->getActorId();
				}
				return null;
			}, $participants));

			$statuses = $this->statusManager->getUserStatuses($userIds);

			$headers['X-Nextcloud-Has-User-Statuses'] = true;
		}

		$currentUser = null;
		if ($this->userId !== null) {
			$currentUser = $this->userManager->get($this->userId);
		}

		$cleanGuests = false;
		foreach ($participants as $participant) {
			$attendeeId = $participant->getAttendee()->getId();
			if (isset($results[$attendeeId])) {
				$session = $participant->getSession();
				if (!$session instanceof Session) {
					// If the user has an entry already and this has no session we don't need it anymore.
					continue;
				}

				if ($session->getLastPing() <= $maxPingAge) {
					if ($participant->getAttendee()->getActorType() === Attendee::ACTOR_GUESTS) {
						$cleanGuests = true;
					} elseif ($participant->getAttendee()->getActorType() === Attendee::ACTOR_USERS
						|| $participant->getAttendee()->getActorType() === Attendee::ACTOR_EMAILS
						|| $participant->getAttendee()->getActorType() === Attendee::ACTOR_FEDERATED_USERS) {
						$this->participantService->leaveRoomAsSession($this->room, $participant);
					}
					// Session expired, ignore
					continue;
				}

				// Combine the session values: All inCall bit flags, newest lastPing and any sessionId (for online checking)
				$results[$attendeeId]['inCall'] |= $session->getInCall();
				$results[$attendeeId]['lastPing'] = max($results[$attendeeId]['lastPing'], $session->getLastPing());
				$results[$attendeeId]['sessionIds'][] = $session->getSessionId();
				continue;
			}

			$result = [
				'roomToken' => $participant->getRoom()->getToken(),
				'inCall' => Participant::FLAG_DISCONNECTED,
				'lastPing' => 0,
				'sessionIds' => [],
				'participantType' => $participant->getAttendee()->getParticipantType(),
				'attendeeId' => $attendeeId,
				'actorId' => $participant->getAttendee()->getActorId(),
				'actorType' => $participant->getAttendee()->getActorType(),
				'displayName' => $participant->getAttendee()->getActorId(),
				'permissions' => $participant->getPermissions(),
				'attendeePermissions' => $participant->getAttendee()->getPermissions(),
				'attendeePin' => '',
				'phoneNumber' => '',
				'callId' => '',
			];
			if ($this->talkConfig->isSIPConfigured()
				&& $this->room->getSIPEnabled() !== Webinary::SIP_DISABLED
				&& ($this->participant->hasModeratorPermissions(false)
					|| $this->participant->getAttendee()->getId() === $participant->getAttendee()->getId())) {
				// Generate a PIN if the attendee is a user and doesn't have one.
				$this->participantService->generatePinForParticipant($this->room, $participant);

				$result['attendeePin'] = (string)$participant->getAttendee()->getPin();
			}

			if ($participant->getSession() instanceof Session) {
				$result['inCall'] = $participant->getSession()->getInCall();
				$result['lastPing'] = $participant->getSession()->getLastPing();
				$result['sessionIds'] = [$participant->getSession()->getSessionId()];
			}

			if ($participant->getAttendee()->getActorType() === Attendee::ACTOR_USERS) {
				$userId = $participant->getAttendee()->getActorId();
				if ($participant->getSession() instanceof Session && $participant->getSession()->getLastPing() <= $maxPingAge) {
					$this->participantService->leaveRoomAsSession($this->room, $participant);
				}

				$result['displayName'] = $participant->getAttendee()->getDisplayName();
				if (!$result['displayName']) {
					$userDisplayName = $this->userManager->getDisplayName($userId);
					if ($userDisplayName === null) {
						continue;
					}
					$result['displayName'] = $userDisplayName;
				}

				if (isset($statuses[$userId])) {
					$result['status'] = $statuses[$userId]->getStatus();
					$result['statusIcon'] = $statuses[$userId]->getIcon();
					$result['statusMessage'] = $statuses[$userId]->getMessage();
					$result['statusClearAt'] = $statuses[$userId]->getClearAt()?->getTimestamp();
				} elseif (isset($headers['X-Nextcloud-Has-User-Statuses'])) {
					$result['status'] = IUserStatus::OFFLINE;
					$result['statusIcon'] = null;
					$result['statusMessage'] = null;
					$result['statusClearAt'] = null;
				}
			} elseif ($participant->getAttendee()->getActorType() === Attendee::ACTOR_GUESTS) {
				if ($participant->getAttendee()->getParticipantType() === Participant::GUEST
					&& ($participant->getAttendee()->getPermissions() === Attendee::PERMISSIONS_DEFAULT
						|| $participant->getAttendee()->getPermissions() === Attendee::PERMISSIONS_CUSTOM)) {
					// Guests without an up-to-date session are filtered out. We
					// only keep there attendees in the database, so that the
					// comments show the display name. Only when they have
					// non-default permissions we show them, so permissions can
					// be reset or removed
					if ($result['lastPing'] <= $maxPingAge) {
						$cleanGuests = true;
						continue;
					}
				}

				$result['displayName'] = $participant->getAttendee()->getDisplayName();
			} elseif ($participant->getAttendee()->getActorType() === Attendee::ACTOR_GROUPS) {
				$result['displayName'] = $participant->getAttendee()->getDisplayName();
			} elseif ($participant->getAttendee()->getActorType() === Attendee::ACTOR_CIRCLES) {
				$result['displayName'] = $participant->getAttendee()->getDisplayName();
			} elseif ($participant->getAttendee()->getActorType() === Attendee::ACTOR_EMAILS) {
				if ($participant->getSession() instanceof Session && $participant->getSession()->getLastPing() <= $maxPingAge) {
					$this->participantService->leaveRoomAsSession($this->room, $participant);
				}
				$result['displayName'] = $participant->getAttendee()->getDisplayName();
				if ($this->participant->hasModeratorPermissions() || $this->participant->getAttendee()->getId() === $participant->getAttendee()->getId()) {
					$result['invitedActorId'] = $participant->getAttendee()->getInvitedCloudId();
				}
			} elseif ($participant->getAttendee()->getActorType() === Attendee::ACTOR_FEDERATED_USERS) {
				if ($participant->getSession() instanceof Session && $participant->getSession()->getLastPing() <= $maxPingAge) {
					$this->participantService->leaveRoomAsSession($this->room, $participant);
				}
				$result['displayName'] = $participant->getAttendee()->getDisplayName();
			} elseif ($participant->getAttendee()->getActorType() === Attendee::ACTOR_PHONES) {
				$result['displayName'] = $participant->getAttendee()->getDisplayName();
				if ($this->talkConfig->isSIPConfigured()
					&& $this->participant->hasModeratorPermissions(false)) {
					$result['phoneNumber'] = $participant->getAttendee()->getPhoneNumber();

					if ($currentUser instanceof IUser && $this->talkConfig->canUserDialOutSIP($currentUser)) {
						$result['callId'] = $participant->getAttendee()->getCallId();
					}
				}
			}

			$results[$attendeeId] = $result;
		}

		if ($cleanGuests) {
			$this->participantService->cleanGuestParticipants($this->room);
		}

		return new DataResponse(array_values($results), Http::STATUS_OK, $headers);
	}

	/**
	 * Add a participant to a room
	 *
	 * @param string $newParticipant New participant
	 * @param 'users'|'groups'|'circles'|'emails'|'federated_users'|'phones'|'teams' $source Source of the participant
	 * @return DataResponse<Http::STATUS_OK, array{type?: int}, array{}>|DataResponse<Http::STATUS_BAD_REQUEST|Http::STATUS_NOT_FOUND|Http::STATUS_NOT_IMPLEMENTED, array{error: 'ban'|'cloud-id'|'federation'|'moderator'|'new-participant'|'outgoing'|'reach-remote'|'room-type'|'sip'|'source'|'trusted-servers'}, array{}>
	 *
	 * 200: Participant successfully added
	 * 400: Adding participant is not possible, e.g. when the user is banned (check error attribute of response for detail key)
	 * 404: User, group or other target to invite was not found
	 * 501: SIP dial-out is not configured
	 */
	#[NoAdminRequired]
	#[RequireLoggedInModeratorParticipant]
	public function addParticipantToRoom(string $newParticipant, string $source = 'users'): DataResponse {
		if ($this->room->getType() === Room::TYPE_ONE_TO_ONE
			|| $this->room->getType() === Room::TYPE_ONE_TO_ONE_FORMER
			|| $this->room->getType() === Room::TYPE_NOTE_TO_SELF
			|| $this->room->getObjectType() === Room::OBJECT_TYPE_VIDEO_VERIFICATION) {
			return new DataResponse(['error' => 'room-type'], Http::STATUS_BAD_REQUEST);
		}

		if ($source !== 'users' && $this->room->getObjectType() === BreakoutRoom::PARENT_OBJECT_TYPE) {
			// Can only add users to breakout rooms
			return new DataResponse(['error' => 'source'], Http::STATUS_BAD_REQUEST);
		}

		$participants = $this->participantService->getParticipantsForRoom($this->room);
		$participantsByUserId = [];
		$remoteParticipantsByFederatedId = [];
		foreach ($participants as $participant) {
			if ($participant->getAttendee()->getActorType() === Attendee::ACTOR_USERS) {
				$participantsByUserId[$participant->getAttendee()->getActorId()] = $participant;
			} elseif ($participant->getAttendee()->getActorType() === Attendee::ACTOR_FEDERATED_USERS) {
				$remoteParticipantsByFederatedId[$participant->getAttendee()->getActorId()] = $participant;
			}
		}

		/** @var IUser $addedBy */
		$addedBy = $this->userManager->get($this->userId);

		// list of participants to attempt adding,
		// existing ones will be filtered later below
		$participantsToAdd = [];

		if ($source === 'users') {
			if ($newParticipant === MatterbridgeManager::BRIDGE_BOT_USERID) {
				return new DataResponse(['error' => 'new-participant'], Http::STATUS_NOT_FOUND);
			}

			$newUser = $this->userManager->get($newParticipant);
			if (!$newUser instanceof IUser) {
				return new DataResponse(['error' => 'new-participant'], Http::STATUS_NOT_FOUND);
			}

			//Check if the user is banned
			if ($this->banService->isActorBanned($this->room, Attendee::ACTOR_USERS, $newUser->getUID())) {
				return new DataResponse(['error' => 'ban'], Http::STATUS_BAD_REQUEST);
			}

			$participantsToAdd[] = [
				'actorType' => Attendee::ACTOR_USERS,
				'actorId' => $newUser->getUID(),
				'displayName' => $newUser->getDisplayName(),
			];
		} elseif ($source === 'groups') {
			$group = $this->groupManager->get($newParticipant);
			if (!$group instanceof IGroup) {
				return new DataResponse(['error' => 'new-participant'], Http::STATUS_NOT_FOUND);
			}

			$this->participantService->addGroup($this->room, $group, $participants);
		} elseif ($source === 'circles' || $source === 'teams') {
			if (!$this->appManager->isEnabledForUser('circles')) {
				return new DataResponse(['error' => 'new-participant'], Http::STATUS_BAD_REQUEST);
			}

			try {
				$circle = $this->participantService->getCircle($newParticipant, $this->userId);
			} catch (\Exception) {
				return new DataResponse(['error' => 'new-participant'], Http::STATUS_NOT_FOUND);
			}

			$this->participantService->addCircle($this->room, $circle, $participants);
		} elseif ($source === 'emails') {
			$data = [];
			try {
				$this->roomService->setType($this->room, Room::TYPE_PUBLIC);
				$data = ['type' => $this->room->getType()];
			} catch (TypeException) {
			}

			$email = strtolower($newParticipant);
			$actorId = hash('sha256', $email);
			try {
				$this->participantService->getParticipantByActor($this->room, Attendee::ACTOR_EMAILS, $actorId);
			} catch (ParticipantNotFoundException) {
				$participant = $this->participantService->inviteEmailAddress($this->room, $actorId, $email);
				$this->guestManager->sendEmailInvitation($this->room, $participant);
			}

			return new DataResponse($data);
		} elseif ($source === 'federated_users') {
			if (!$this->talkConfig->isFederationEnabled()) {
				return new DataResponse(['error' => 'federation'], Http::STATUS_NOT_IMPLEMENTED);
			}
			try {
				$newUser = $this->cloudIdManager->resolveCloudId($newParticipant);
			} catch (\InvalidArgumentException $e) {
				$this->logger->error($e->getMessage(), [
					'exception' => $e,
				]);
				return new DataResponse(['error' => 'cloud-id'], Http::STATUS_BAD_REQUEST);
			}
			try {
				$this->federationManager->isAllowedToInvite($addedBy, $newUser);
			} catch (FederationRestrictionException $e) {
				return new DataResponse(['error' => $e->getReason()], Http::STATUS_BAD_REQUEST);
			}

			$participantsToAdd[] = [
				'actorType' => Attendee::ACTOR_FEDERATED_USERS,
				'actorId' => $newUser->getId(),
				'displayName' => $newUser->getDisplayId(),
			];
		} elseif ($source === 'phones') {
			if (
				!$addedBy instanceof IUser
				|| !$this->talkConfig->isSIPConfigured()
				|| !$this->talkConfig->canUserDialOutSIP($addedBy)
				|| preg_match(Room::SIP_INCOMPATIBLE_REGEX, $this->room->getToken())
				|| ($this->room->getType() !== Room::TYPE_GROUP && $this->room->getType() !== Room::TYPE_PUBLIC)) {
				return new DataResponse(['error' => 'sip'], Http::STATUS_NOT_IMPLEMENTED);
			}

			$phoneRegion = $this->config->getSystemValueString('default_phone_region');
			if ($phoneRegion === '') {
				$phoneRegion = null;
			}

			$formattedNumber = $this->phoneNumberUtil->convertToStandardFormat($newParticipant, $phoneRegion);
			if ($formattedNumber === null) {
				return new DataResponse([], Http::STATUS_BAD_REQUEST);
			}

			$participantsToAdd[] = [
				'actorType' => Attendee::ACTOR_PHONES,
				'actorId' => sha1($formattedNumber . '#' . $this->timeFactory->getTime()),
				'displayName' => substr($formattedNumber, 0, -4) . '…', // FIXME Allow the UI to hand in a name (when selected from contacts?)
				'phoneNumber' => $formattedNumber,
			];
		} else {
			$this->logger->error('Trying to add participant from unsupported source ' . $source);
			return new DataResponse(['error' => 'source'], Http::STATUS_BAD_REQUEST);
		}

		// attempt adding the listed users to the room
		// existing users with USER_SELF_JOINED will get converted to regular USER participants
		foreach ($participantsToAdd as $index => $participantToAdd) {
			$existingParticipant = $participantsByUserId[$participantToAdd['actorId']] ?? null;
			if ($participantToAdd['actorType'] === Attendee::ACTOR_FEDERATED_USERS) {
				$existingParticipant = $remoteParticipantsByFederatedId[$participantToAdd['actorId']] ?? null;
			}

			if ($existingParticipant !== null) {
				unset($participantsToAdd[$index]);
				if ($existingParticipant->getAttendee()->getParticipantType() !== Participant::USER_SELF_JOINED) {
					// user is already a regular participant, skip
					continue;
				}
				$this->participantService->updateParticipantType($this->room, $existingParticipant, Participant::USER);
			}
		}

		if ($source === 'users' && $this->room->getObjectType() === BreakoutRoom::PARENT_OBJECT_TYPE) {
			$parentRoom = $this->manager->getRoomByToken($this->room->getObjectId());

			// Also add to parent room in case the user is missing
			try {
				$this->participantService->getParticipantByActor(
					$parentRoom,
					Attendee::ACTOR_USERS,
					$newParticipant
				);
			} catch (ParticipantNotFoundException $e) {
				$this->participantService->addUsers($parentRoom, $participantsToAdd, $addedBy);
			}

			// Remove from previous breakout room in case the user is moved
			try {
				$this->breakoutRoomService->removeAttendeeFromBreakoutRoom($parentRoom, Attendee::ACTOR_USERS, $newParticipant);
			} catch (\InvalidArgumentException $e) {
				/** @var 'moderator' $error */
				$error = $e->getMessage();
				return new DataResponse(['error' => $error], Http::STATUS_BAD_REQUEST);
			}
		}

		// add the remaining users in batch
		try {
			$this->participantService->addUsers($this->room, $participantsToAdd, $addedBy);
		} catch (CannotReachRemoteException $e) {
			return new DataResponse(['error' => 'reach-remote'], Http::STATUS_NOT_FOUND);
		}

		return new DataResponse([]);
	}

	/**
	 * Remove the current user from a room
	 *
	 * @return DataResponse<Http::STATUS_OK, null, array{}>|DataResponse<Http::STATUS_BAD_REQUEST|Http::STATUS_NOT_FOUND, array{error: 'last-moderator'|'participant'}, array{}>
	 *
	 * 200: Participant removed successfully
	 * 400: Removing participant is not possible
	 * 404: Participant not found
	 */
	#[FederationSupported]
	#[NoAdminRequired]
	#[RequireLoggedInParticipant]
	public function removeSelfFromRoom(): DataResponse {
		return $this->removeSelfFromRoomLogic($this->room, $this->participant);
	}

	/**
	 * @return DataResponse<Http::STATUS_OK, null, array{}>|DataResponse<Http::STATUS_BAD_REQUEST|Http::STATUS_NOT_FOUND, array{error: 'last-moderator'|'participant'}, array{}>
	 */
	protected function removeSelfFromRoomLogic(Room $room, Participant $participant): DataResponse {
		if ($room->isFederatedConversation()) {
			$this->federationManager->rejectByRemoveSelf($room, $this->userId);
		}

		if ($room->getType() !== Room::TYPE_ONE_TO_ONE && $room->getType() !== Room::TYPE_ONE_TO_ONE_FORMER) {
			if ($participant->hasModeratorPermissions(false)
				&& $this->participantService->getNumberOfUsers($room) > 1
				&& $this->participantService->getNumberOfModerators($room) === 1) {
				return new DataResponse(['error' => 'last-moderator'], Http::STATUS_BAD_REQUEST);
			}
		}

		if ($room->getType() !== Room::TYPE_CHANGELOG &&
			$room->getObjectType() !== Room::OBJECT_TYPE_FILE &&
			$this->participantService->getNumberOfUsers($room) === 1 &&
			\in_array($participant->getAttendee()->getParticipantType(), [
				Participant::USER,
				Participant::MODERATOR,
				Participant::OWNER,
			], true)) {
			$this->roomService->deleteRoom($room);
			return new DataResponse(null);
		}

		if ($this->appConfig->getAppValueBool('delete_one_to_one_conversations')
			&& in_array($this->room->getType(), [Room::TYPE_ONE_TO_ONE, Room::TYPE_ONE_TO_ONE_FORMER], true)) {
			$this->roomService->deleteRoom($room);
			return new DataResponse(null);
		}

		$currentUser = $this->userManager->get($this->userId);
		if (!$currentUser instanceof IUser) {
			return new DataResponse(['error' => 'participant'], Http::STATUS_NOT_FOUND);
		}

		$this->participantService->removeUser($room, $currentUser, AAttendeeRemovedEvent::REASON_LEFT);

		return new DataResponse(null);
	}

	/**
	 * Remove an attendee from a room
	 *
	 * @param int $attendeeId ID of the attendee
	 * @psalm-param non-negative-int $attendeeId
	 * @return DataResponse<Http::STATUS_OK, null, array{}>|DataResponse<Http::STATUS_BAD_REQUEST|Http::STATUS_FORBIDDEN|Http::STATUS_NOT_FOUND, array{error: 'last-moderator'|'owner'|'participant'|'room-type'}, array{}>
	 *
	 * 200: Attendee removed successfully
	 * 400: Removing attendee is not possible
	 * 403: Removing attendee is not allowed
	 * 404: Attendee not found
	 */
	#[PublicPage]
	#[RequireModeratorParticipant]
	public function removeAttendeeFromRoom(int $attendeeId): DataResponse {
		try {
			$targetParticipant = $this->participantService->getParticipantByAttendeeId($this->room, $attendeeId);
		} catch (ParticipantNotFoundException $e) {
			return new DataResponse(['error' => 'participant'], Http::STATUS_NOT_FOUND);
		}

		if ($targetParticipant->getAttendee()->getActorType() === Attendee::ACTOR_USERS
			&& $targetParticipant->getAttendee()->getActorId() === MatterbridgeManager::BRIDGE_BOT_USERID) {
			return new DataResponse(['error' => 'participant'], Http::STATUS_NOT_FOUND);
		}

		if ($this->room->getType() === Room::TYPE_ONE_TO_ONE || $this->room->getType() === Room::TYPE_ONE_TO_ONE_FORMER) {
			return new DataResponse(['error' => 'room-type'], Http::STATUS_BAD_REQUEST);
		}

		if ($this->participant->getAttendee()->getId() === $targetParticipant->getAttendee()->getId()) {
			return $this->removeSelfFromRoomLogic($this->room, $targetParticipant);
		}

		if ($targetParticipant->getAttendee()->getParticipantType() === Participant::OWNER) {
			return new DataResponse(['error' => 'owner'], Http::STATUS_FORBIDDEN);
		}

		$this->participantService->removeAttendee($this->room, $targetParticipant, AAttendeeRemovedEvent::REASON_REMOVED);
		return new DataResponse(null);
	}

	/**
	 * Allowed guests to join conversation
	 *
	 * Required capability: `conversation-creation-password` for `string $password` parameter
	 *
	 * @param string $password New password (only available with `conversation-creation-password` capability)
	 * @return DataResponse<Http::STATUS_OK, TalkRoom, array{}>|DataResponse<Http::STATUS_BAD_REQUEST, array{error: 'breakout-room'|'type'|'value'|'password', message?: string}, array{}>
	 *
	 * 200: Allowed guests successfully
	 * 400: Allowing guests is not possible
	 */
	#[NoAdminRequired]
	#[RequireLoggedInModeratorParticipant]
	public function makePublic(string $password = ''): DataResponse {
		if ($this->talkConfig->isPasswordEnforced() && $password === '') {
			return new DataResponse(['error' => 'password', 'message' => $this->l->t('Password needs to be set')], Http::STATUS_BAD_REQUEST);
		}

		try {
			if ($password !== '') {
				$this->roomService->makePublicWithPassword($this->room, $password);
			} else {
				$this->roomService->setType($this->room, Room::TYPE_PUBLIC);
			}
		} catch (PasswordException $e) {
			return new DataResponse(['error' => 'password', 'message' => $e->getHint()], Http::STATUS_BAD_REQUEST);
		} catch (TypeException $e) {
			return new DataResponse(['error' => $e->getReason()], Http::STATUS_BAD_REQUEST);
		}

		return new DataResponse($this->formatRoom($this->room, $this->participant));
	}

	/**
	 * Disallowed guests to join conversation
	 *
	 * @return DataResponse<Http::STATUS_OK, TalkRoom, array{}>|DataResponse<Http::STATUS_BAD_REQUEST, array{error: 'breakout-room'|'type'|'value'}, array{}>
	 *
	 * 200: Room unpublished Disallowing guests successfully
	 * 400: Disallowing guests is not possible
	 */
	#[NoAdminRequired]
	#[RequireLoggedInModeratorParticipant]
	public function makePrivate(): DataResponse {
		try {
			$this->roomService->setType($this->room, Room::TYPE_GROUP);
		} catch (TypeException $e) {
			return new DataResponse(['error' => $e->getReason()], Http::STATUS_BAD_REQUEST);
		}

		return new DataResponse($this->formatRoom($this->room, $this->participant));
	}

	/**
	 * Set read-only state of a room
	 *
	 * @param 0|1 $state New read-only state
	 * @psalm-param Room::READ_* $state
	 * @return DataResponse<Http::STATUS_OK, TalkRoom, array{}>|DataResponse<Http::STATUS_BAD_REQUEST, array{error: 'type'|'value'}, array{}>
	 *
	 * 200: Read-only state updated successfully
	 * 400: Updating read-only state is not possible
	 */
	#[NoAdminRequired]
	#[RequireModeratorParticipant]
	public function setReadOnly(int $state): DataResponse {
		try {
			$this->roomService->setReadOnly($this->room, $state);
		} catch (ReadOnlyException $e) {
			return new DataResponse(['error' => $e->getReason()], Http::STATUS_BAD_REQUEST);
		}

		if ($state === Room::READ_ONLY) {
			$participants = $this->participantService->getParticipantsInCall($this->room);

			// kick out all participants out of the call
			foreach ($participants as $participant) {
				$this->participantService->changeInCall($this->room, $participant, Participant::FLAG_DISCONNECTED);
			}
		}

		return new DataResponse($this->formatRoom($this->room, $this->participant));
	}

	/**
	 * Make a room listable
	 *
	 * @param 0|1|2 $scope Scope where the room is listable
	 * @psalm-param Room::LISTABLE_* $scope
	 * @return DataResponse<Http::STATUS_OK, TalkRoom, array{}>|DataResponse<Http::STATUS_BAD_REQUEST, array{error: 'breakout-room'|'type'|'value'}, array{}>
	 *
	 * 200: Made room listable successfully
	 * 400: Making room listable is not possible
	 */
	#[NoAdminRequired]
	#[RequireModeratorParticipant]
	public function setListable(int $scope): DataResponse {
		try {
			$this->roomService->setListable($this->room, $scope);
		} catch (ListableException $e) {
			return new DataResponse(['error' => $e->getReason()], Http::STATUS_BAD_REQUEST);
		}

		return new DataResponse($this->formatRoom($this->room, $this->participant));
	}

	/**
	 * Update the mention permissions for a room
	 *
	 * @param 0|1 $mentionPermissions New mention permissions
	 * @psalm-param Room::MENTION_PERMISSIONS_* $mentionPermissions
	 * @return DataResponse<Http::STATUS_OK, TalkRoom, array{}>|DataResponse<Http::STATUS_BAD_REQUEST, array{error: 'breakout-room'|'type'|'value'}, array{}>
	 *
	 * 200: Permissions updated successfully
	 * 400: Updating permissions is not possible
	 */
	#[NoAdminRequired]
	#[RequireModeratorParticipant]
	public function setMentionPermissions(int $mentionPermissions): DataResponse {
		try {
			$this->roomService->setMentionPermissions($this->room, $mentionPermissions);
		} catch (MentionPermissionsException $e) {
			return new DataResponse(['error' => $e->getReason()], Http::STATUS_BAD_REQUEST);
		}

		return new DataResponse($this->formatRoom($this->room, $this->participant));
	}

	/**
	 * Set a password for a room
	 *
	 * @param string $password New password
	 * @return DataResponse<Http::STATUS_OK, TalkRoom, array{}>|DataResponse<Http::STATUS_BAD_REQUEST, array{error: 'breakout-room'|'type'|'value', message?: string}, array{}>
	 *
	 * 200: Password set successfully
	 * 400: Setting password is not possible
	 */
	#[PublicPage]
	#[RequireModeratorParticipant]
	public function setPassword(string $password): DataResponse {
		try {
			$this->roomService->setPassword($this->room, $password);
		} catch (PasswordException $e) {
			$data = ['error' => $e->getReason()];
			if ($e->getHint() !== '') {
				$data['message'] = $e->getHint();
			}
			return new DataResponse($data, Http::STATUS_BAD_REQUEST);
		}

		return new DataResponse($this->formatRoom($this->room, $this->participant));
	}

	/**
	 * Archive a conversation
	 *
	 *  Required capability: `archived-conversations-v2`
	 *
	 * @return DataResponse<Http::STATUS_OK, TalkRoom, array{}>
	 *
	 * 200: Conversation was archived
	 */
	#[NoAdminRequired]
	#[FederationSupported]
	#[RequireLoggedInParticipant]
	public function archiveConversation(): DataResponse {
		$this->participantService->archiveConversation($this->participant);
		return new DataResponse($this->formatRoom($this->room, $this->participant));
	}

	/**
	 * Unarchive a conversation
	 *
	 * Required capability: `archived-conversations-v2`
	 *
	 * @return DataResponse<Http::STATUS_OK, TalkRoom, array{}>
	 *
	 * 200: Conversation was unarchived
	 */
	#[NoAdminRequired]
	#[FederationSupported]
	#[RequireLoggedInParticipant]
	public function unarchiveConversation(): DataResponse {
		$this->participantService->unarchiveConversation($this->participant);
		return new DataResponse($this->formatRoom($this->room, $this->participant));
	}

	/**
	 * Join a room
	 *
	 * @param string $token Token of the room
	 * @param string $password Password of the room
	 * @param bool $force Create a new session if necessary
	 * @return DataResponse<Http::STATUS_OK, TalkRoom, array{X-Nextcloud-Talk-Proxy-Hash?: string}>|DataResponse<Http::STATUS_FORBIDDEN, array{error: 'ban'|'password'}, array{}>|DataResponse<Http::STATUS_NOT_FOUND, null, array{}>|DataResponse<Http::STATUS_CONFLICT, array{sessionId: string, inCall: int, lastPing: int}, array{}>
	 *
	 * 200: Room joined successfully
	 * 403: Joining room is not allowed
	 * 404: Room not found
	 * 409: Session already exists
	 */
	#[PublicPage]
	#[BruteForceProtection(action: 'talkRoomPassword')]
	#[BruteForceProtection(action: 'talkRoomToken')]
	public function joinRoom(string $token, string $password = '', bool $force = true): DataResponse {
		$sessionId = $this->session->getSessionForRoom($token);
		try {
			// The participant is just joining, so enforce to not load any session
			$room = $this->manager->getRoomForUserByToken($token, $this->userId, null);
		} catch (RoomNotFoundException $e) {
			$response = new DataResponse(null, Http::STATUS_NOT_FOUND);
			$response->throttle(['token' => $token, 'action' => 'talkRoomToken']);
			return $response;
		}

		try {
			$this->banService->throwIfActorIsBanned($room, $this->userId);
		} catch (ForbiddenException $e) {
			$this->logger->info('Participant ' . ($this->userId ?? 'guest') . ' is banned from room ' . $token . ' by ' . $e->getMessage());
			return new DataResponse([
				'error' => 'ban',
			], Http::STATUS_FORBIDDEN);
		}

		/** @var Participant|null $previousSession */
		$previousParticipant = null;
		/** @var Session|null $previousSession */
		$previousSession = null;

		if ($sessionId !== null) {
			try {
				if ($this->userId !== null) {
					$previousParticipant = $this->participantService->getParticipant($room, $this->userId, $sessionId);
				} else {
					$previousParticipant = $this->participantService->getParticipantBySession($room, $sessionId);
				}
				$previousSession = $previousParticipant->getSession();
			} catch (ParticipantNotFoundException $e) {
			}

			if ($previousSession instanceof Session && $previousSession->getSessionId() === $sessionId) {
				if ($force === false && $previousSession->getInCall() !== Participant::FLAG_DISCONNECTED) {
					// Previous session is/was active in the call, show a warning
					return new DataResponse([
						'sessionId' => $previousSession->getSessionId(),
						'inCall' => $previousSession->getInCall(),
						'lastPing' => $previousSession->getLastPing(),
					], Http::STATUS_CONFLICT);
				}

				if ($previousSession->getInCall() !== Participant::FLAG_DISCONNECTED) {
					$this->participantService->changeInCall($room, $previousParticipant, Participant::FLAG_DISCONNECTED);
				}

				$this->participantService->leaveRoomAsSession($room, $previousParticipant, true);
			}
		}

		$authenticatedEmailGuest = $this->session->getAuthedEmailActorIdForRoom($token);

		$headers = [];
		if ($authenticatedEmailGuest !== null || $room->isFederatedConversation()
			|| ($previousParticipant instanceof Participant && $previousParticipant->isGuest())) {
			// Skip password checking
			$result = [
				'result' => true,
			];
		} else {
			$result = $this->roomService->verifyPassword($room, (string)$this->session->getPasswordForRoom($token));
		}

		$user = $this->userManager->get($this->userId);
		try {
			if ($user instanceof IUser) {
				$participant = $this->participantService->joinRoom($this->roomService, $room, $user, $password, $result['result']);
				$this->participantService->generatePinForParticipant($room, $participant);
			} else {
				if ($authenticatedEmailGuest !== null && $previousParticipant === null) {
					try {
						$previousParticipant = $this->participantService->getParticipantByActor($room, Attendee::ACTOR_EMAILS, $authenticatedEmailGuest);
					} catch (ParticipantNotFoundException $e) {
					}
				}
				$participant = $this->participantService->joinRoomAsNewGuest($this->roomService, $room, $password, $result['result'], $previousParticipant);
				$this->session->setGuestActorIdForRoom($room->getToken(), $participant->getAttendee()->getActorId());
			}
			$this->throttler->resetDelay($this->request->getRemoteAddress(), 'talkRoomPassword', ['token' => $token, 'action' => 'talkRoomPassword']);
			$this->throttler->resetDelay($this->request->getRemoteAddress(), 'talkRoomToken', ['token' => $token, 'action' => 'talkRoomToken']);
		} catch (InvalidPasswordException $e) {
			$response = new DataResponse([
				'error' => 'password',
			], Http::STATUS_FORBIDDEN);
			$response->throttle(['token' => $token, 'action' => 'talkRoomPassword']);
			return $response;
		} catch (UnauthorizedException $e) {
			$response = new DataResponse(null, Http::STATUS_NOT_FOUND);
			$response->throttle(['token' => $token, 'action' => 'talkRoomToken']);
			return $response;
		}

		$this->session->removePasswordForRoom($token);
		$session = $participant->getSession();
		if ($session instanceof Session) {
			$this->session->setSessionForRoom($token, $session->getSessionId());
		}

		if ($room->isFederatedConversation()) {
			/** @var \OCA\Talk\Federation\Proxy\TalkV1\Controller\RoomController $proxy */
			$proxy = \OCP\Server::get(\OCA\Talk\Federation\Proxy\TalkV1\Controller\RoomController::class);

			try {
				$response = $proxy->joinFederatedRoom($room, $participant);
			} catch (CannotReachRemoteException $e) {
				$this->participantService->leaveRoomAsSession($room, $participant);

				throw $e;
			}

			if ($response->getStatus() === Http::STATUS_NOT_FOUND) {
				$this->participantService->removeAttendee($room, $participant, AAttendeeRemovedEvent::REASON_REMOVED);
				return new DataResponse(null, Http::STATUS_NOT_FOUND);
			}

			/** @var TalkRoom $data */
			$data = $response->getData();
			$this->roomService->syncPropertiesFromHostRoom($room, $data);

			$proxyHeaders = $response->getHeaders();
			if (isset($proxyHeaders['X-Nextcloud-Talk-Proxy-Hash'])) {
				$headers['X-Nextcloud-Talk-Proxy-Hash'] = $proxyHeaders['X-Nextcloud-Talk-Proxy-Hash'];
			}
		}

		return new DataResponse($this->formatRoom($room, $participant), Http::STATUS_OK, $headers);
	}

	/**
	 * Join room on the host server using the session id of the federated user
	 *
	 * The session id can be null only for requests from Talk < 20.
	 *
	 * @param string $token Token of the room
	 * @param ?string $sessionId Federated session id to join with
	 * @return DataResponse<Http::STATUS_OK, TalkRoom, array{X-Nextcloud-Talk-Hash: string}>|DataResponse<Http::STATUS_NOT_FOUND, null, array{}>
	 *
	 * 200: Federated user joined the room
	 * 404: Room not found
	 */
	#[OpenAPI(scope: OpenAPI::SCOPE_FEDERATION)]
	#[PublicPage]
	#[BruteForceProtection(action: 'talkRoomToken')]
	#[BruteForceProtection(action: 'talkFederationAccess')]
	public function joinFederatedRoom(string $token, ?string $sessionId): DataResponse {
		if (!$this->federationAuthenticator->isFederationRequest()) {
			$response = new DataResponse(null, Http::STATUS_NOT_FOUND);
			$response->throttle(['token' => $token, 'action' => 'talkRoomToken']);
			return $response;
		}

		try {
			try {
				$room = $this->federationAuthenticator->getRoom();
			} catch (RoomNotFoundException) {
				$room = $this->manager->getRoomByRemoteAccess(
					$token,
					Attendee::ACTOR_FEDERATED_USERS,
					$this->federationAuthenticator->getCloudId(),
					$this->federationAuthenticator->getAccessToken(),
				);
			}

			if ($sessionId !== null) {
				$participant = $this->participantService->joinRoomAsFederatedUser($room, Attendee::ACTOR_FEDERATED_USERS, $this->federationAuthenticator->getCloudId(), $sessionId);
			} else {
				$participant = $this->participantService->getParticipantByActor($room, Attendee::ACTOR_FEDERATED_USERS, $this->federationAuthenticator->getCloudId());
			}

			// Let the clients know if they need to reload capabilities
			$capabilities = $this->capabilities->getCapabilities();
			return new DataResponse($this->formatRoom($room, $participant), Http::STATUS_OK, [
				'X-Nextcloud-Talk-Hash' => sha1(json_encode($capabilities)),
			]);
		} catch (RoomNotFoundException|ParticipantNotFoundException|UnauthorizedException) {
			$response = new DataResponse(null, Http::STATUS_NOT_FOUND);
			$response->throttle(['token' => $token, 'action' => 'talkFederationAccess']);
			return $response;
		}
	}

	/**
	 * Verify a dial-in PIN (SIP bridge)
	 *
	 * @param numeric-string $pin PIN the participant used to dial-in
	 * @return DataResponse<Http::STATUS_OK, TalkRoom, array{}>|DataResponse<Http::STATUS_UNAUTHORIZED|Http::STATUS_NOT_FOUND|Http::STATUS_NOT_IMPLEMENTED, null, array{}>
	 *
	 * 200: Participant returned
	 * 401: SIP request invalid
	 * 404: Participant not found
	 * 501: SIP dial-in is not configured
	 */
	#[PublicPage]
	#[BruteForceProtection(action: 'talkSipBridgeSecret')]
	#[OpenAPI(scope: 'backend-sipbridge')]
	#[RequireRoom]
	public function verifyDialInPin(string $pin): DataResponse {
		try {
			if (!$this->validateSIPBridgeRequest($this->room->getToken())) {
				$response = new DataResponse(null, Http::STATUS_UNAUTHORIZED);
				$response->throttle(['action' => 'talkSipBridgeSecret']);
				return $response;
			}
		} catch (UnauthorizedException) {
			$response = new DataResponse(null, Http::STATUS_UNAUTHORIZED);
			$response->throttle(['action' => 'talkSipBridgeSecret']);
			return $response;
		}

		if (!$this->talkConfig->isSIPConfigured()) {
			return new DataResponse(null, Http::STATUS_NOT_IMPLEMENTED);
		}

		try {
			$participant = $this->participantService->getParticipantByPin($this->room, $pin);
		} catch (ParticipantNotFoundException $e) {
			return new DataResponse(null, Http::STATUS_NOT_FOUND);
		}

		return new DataResponse($this->formatRoom($this->room, $participant, skipLastMessage: true));
	}

	/**
	 * Verify a dial-out number (SIP bridge)
	 *
	 * @param string $number E164 formatted phone number
	 * @param array{actorId?: string, actorType?: string, attendeeId?: int} $options Additional details to verify the validity of the request
	 * @return DataResponse<Http::STATUS_OK, TalkRoom, array{}>|DataResponse<Http::STATUS_BAD_REQUEST|Http::STATUS_UNAUTHORIZED|Http::STATUS_NOT_FOUND|Http::STATUS_NOT_IMPLEMENTED, null, array{}>
	 *
	 * 200: Participant created successfully
	 * 400: Phone number and details could not be confirmed
	 * 401: SIP request invalid
	 * 404: Phone number is not invited as a participant
	 * 501: SIP dial-out is not configured
	 */
	#[PublicPage]
	#[BruteForceProtection(action: 'talkSipBridgeSecret')]
	#[OpenAPI(scope: 'backend-sipbridge')]
	#[RequireRoom]
	public function verifyDialOutNumber(string $number, array $options = []): DataResponse {
		try {
			if (!$this->validateSIPBridgeRequest($this->room->getToken())) {
				$response = new DataResponse(null, Http::STATUS_UNAUTHORIZED);
				$response->throttle(['action' => 'talkSipBridgeSecret']);
				return $response;
			}
		} catch (UnauthorizedException) {
			$response = new DataResponse(null, Http::STATUS_UNAUTHORIZED);
			$response->throttle(['action' => 'talkSipBridgeSecret']);
			return $response;
		}

		if (!$this->talkConfig->isSIPConfigured() || !$this->talkConfig->isSIPDialOutEnabled()) {
			return new DataResponse(null, Http::STATUS_NOT_IMPLEMENTED);
		}

		if (!isset($options['actorId'], $options['actorType']) || $options['actorType'] !== Attendee::ACTOR_PHONES) {
			return new DataResponse(null, Http::STATUS_BAD_REQUEST);
		}

		try {
			$participant = $this->participantService->getParticipantByActor($this->room, Attendee::ACTOR_PHONES, $options['actorId']);
		} catch (ParticipantNotFoundException) {
			return new DataResponse(null, Http::STATUS_NOT_FOUND);
		}

		if ($participant->getAttendee()->getPhoneNumber() !== $number) {
			return new DataResponse(null, Http::STATUS_BAD_REQUEST);
		}

		return new DataResponse($this->formatRoom($this->room, $participant, skipLastMessage: true));
	}

	/**
	 * Create a guest by their dial-in
	 *
	 * @return DataResponse<Http::STATUS_OK, TalkRoom, array{}>|DataResponse<Http::STATUS_BAD_REQUEST|Http::STATUS_UNAUTHORIZED, null, array{}>
	 *
	 * 200: Participant created successfully
	 * 400: SIP not enabled
	 * 401: SIP request invalid
	 */
	#[PublicPage]
	#[BruteForceProtection(action: 'talkSipBridgeSecret')]
	#[OpenAPI(scope: 'backend-sipbridge')]
	#[RequireRoom]
	public function createGuestByDialIn(): DataResponse {
		try {
			if (!$this->validateSIPBridgeRequest($this->room->getToken())) {
				$response = new DataResponse(null, Http::STATUS_UNAUTHORIZED);
				$response->throttle(['action' => 'talkSipBridgeSecret']);
				return $response;
			}
		} catch (UnauthorizedException $e) {
			$response = new DataResponse(null, Http::STATUS_UNAUTHORIZED);
			$response->throttle(['action' => 'talkSipBridgeSecret']);
			return $response;
		}

		if ($this->room->getSIPEnabled() !== Webinary::SIP_ENABLED_NO_PIN) {
			return new DataResponse(null, Http::STATUS_BAD_REQUEST);
		}

		$participant = $this->participantService->joinRoomAsNewGuest($this->roomService, $this->room, '', true);

		return new DataResponse($this->formatRoom($this->room, $participant, skipLastMessage: true));
	}

	/**
	 * Reset call ID of a dial-out participant when the SIP gateway rejected it
	 *
	 * @param string $callId The call ID provided by the SIP bridge earlier to uniquely identify the call to terminate
	 * @param array{actorId?: string, actorType?: string, attendeeId?: int} $options Additional details to verify the validity of the request
	 * @return DataResponse<Http::STATUS_OK|Http::STATUS_BAD_REQUEST|Http::STATUS_UNAUTHORIZED|Http::STATUS_NOT_FOUND|Http::STATUS_NOT_IMPLEMENTED, null, array{}>
	 *
	 * 200: Call ID reset
	 * 400: Call ID mismatch or attendeeId not found in $options
	 * 401: SIP request invalid
	 * 404: Participant was not found
	 * 501: SIP dial-out is not configured
	 */
	#[PublicPage]
	#[BruteForceProtection(action: 'talkSipBridgeSecret')]
	#[OpenAPI(scope: 'backend-sipbridge')]
	#[RequireRoom]
	public function rejectedDialOutRequest(string $callId, array $options = []): DataResponse {
		try {
			if (!$this->validateSIPBridgeRequest($this->room->getToken())) {
				$response = new DataResponse(null, Http::STATUS_UNAUTHORIZED);
				$response->throttle(['action' => 'talkSipBridgeSecret']);
				return $response;
			}
		} catch (UnauthorizedException $e) {
			$response = new DataResponse(null, Http::STATUS_UNAUTHORIZED);
			$response->throttle(['action' => 'talkSipBridgeSecret']);
			return $response;
		}

		if (empty($options['attendeeId'])) {
			return new DataResponse(null, Http::STATUS_BAD_REQUEST);
		}

		if (!$this->talkConfig->isSIPConfigured() || !$this->talkConfig->isSIPDialOutEnabled()) {
			return new DataResponse(null, Http::STATUS_NOT_IMPLEMENTED);
		}

		try {
			$this->participantService->resetDialOutRequest($this->room, $options['attendeeId'], $callId);
		} catch (ParticipantNotFoundException) {
			return new DataResponse(null, Http::STATUS_NOT_FOUND);
		} catch (\InvalidArgumentException) {
			return new DataResponse(null, Http::STATUS_BAD_REQUEST);
		}

		return new DataResponse(null);
	}

	/**
	 * Set active state for a session
	 *
	 * @param 0|1 $state of the room
	 * @psalm-param Session::STATE_* $state
	 * @return DataResponse<Http::STATUS_OK, TalkRoom, array{}>|DataResponse<Http::STATUS_BAD_REQUEST|Http::STATUS_NOT_FOUND, null, array{}>
	 *
	 * 200: Session state set successfully
	 * 400: The provided new state was invalid
	 * 404: The participant did not have a session
	 */
	#[FederationSupported]
	#[PublicPage]
	#[RequireParticipant]
	public function setSessionState(int $state): DataResponse {
		if (!$this->participant->getSession() instanceof Session) {
			return new DataResponse(null, Http::STATUS_NOT_FOUND);
		}

		try {
			$this->sessionService->updateSessionState($this->participant->getSession(), $state);
		} catch (\InvalidArgumentException) {
			return new DataResponse(null, Http::STATUS_BAD_REQUEST);
		}

		return new DataResponse($this->formatRoom($this->room, $this->participant));
	}

	/**
	 * Leave a room
	 *
	 * @param string $token Token of the room
	 * @return DataResponse<Http::STATUS_OK, null, array{}>
	 *
	 * 200: Successfully left the room
	 */
	#[PublicPage]
	public function leaveRoom(string $token): DataResponse {
		$sessionId = $this->session->getSessionForRoom($token);
		$this->session->removeSessionForRoom($token);

		try {
			$room = $this->manager->getRoomForUserByToken($token, $this->userId, $sessionId);
			$participant = $this->participantService->getParticipantBySession($room, $sessionId);

			if ($room->isFederatedConversation()) {
				/** @var \OCA\Talk\Federation\Proxy\TalkV1\Controller\RoomController $proxy */
				$proxy = \OCP\Server::get(\OCA\Talk\Federation\Proxy\TalkV1\Controller\RoomController::class);
				$response = $proxy->leaveFederatedRoom($room, $participant);
			}

			$this->participantService->leaveRoomAsSession($room, $participant);
		} catch (RoomNotFoundException|ParticipantNotFoundException) {
		}

		return new DataResponse(null);
	}

	/**
	 * Leave room on the host server using the session id of the federated user
	 *
	 * @param string $token Token of the room
	 * @param string $sessionId Federated session id to leave with
	 * @return DataResponse<Http::STATUS_OK|Http::STATUS_NOT_FOUND, null, array{}>
	 *
	 * 200: Successfully left the room
	 * 404: Room not found (non-federation request)
	 */
	#[OpenAPI(scope: OpenAPI::SCOPE_FEDERATION)]
	#[PublicPage]
	#[BruteForceProtection(action: 'talkRoomToken')]
	public function leaveFederatedRoom(string $token, string $sessionId): DataResponse {
		if (!$this->federationAuthenticator->isFederationRequest()) {
			$response = new DataResponse(null, Http::STATUS_NOT_FOUND);
			$response->throttle(['token' => $token, 'action' => 'talkRoomToken']);
			return $response;
		}

		try {
			try {
				$room = $this->federationAuthenticator->getRoom();
			} catch (RoomNotFoundException) {
				$room = $this->manager->getRoomByRemoteAccess(
					$token,
					Attendee::ACTOR_FEDERATED_USERS,
					$this->federationAuthenticator->getCloudId(),
					$this->federationAuthenticator->getAccessToken(),
				);
			}

			try {
				$participant = $this->federationAuthenticator->getParticipant();
			} catch (ParticipantNotFoundException) {
				$participant = $this->participantService->getParticipantBySession(
					$room,
					$sessionId,
				);
				$this->federationAuthenticator->authenticated($room, $participant);
			}

			$this->participantService->leaveRoomAsSession($room, $participant);
		} catch (RoomNotFoundException|ParticipantNotFoundException) {
		}

		return new DataResponse(null);
	}

	/**
	 * Promote an attendee to moderator
	 *
	 * @param int $attendeeId ID of the attendee
	 * @psalm-param non-negative-int $attendeeId
	 * @return DataResponse<Http::STATUS_OK|Http::STATUS_BAD_REQUEST|Http::STATUS_FORBIDDEN|Http::STATUS_NOT_FOUND, null, array{}>
	 *
	 * 200: Attendee promoted to moderator successfully
	 * 400: Promoting attendee to moderator is not possible
	 * 403: Promoting attendee to moderator is not allowed
	 * 404: Attendee not found
	 */
	#[PublicPage]
	#[RequireModeratorParticipant]
	public function promoteModerator(int $attendeeId): DataResponse {
		return $this->changeParticipantType($attendeeId, true);
	}

	/**
	 * Demote an attendee from moderator
	 *
	 * @param int $attendeeId ID of the attendee
	 * @psalm-param non-negative-int $attendeeId
	 * @return DataResponse<Http::STATUS_OK|Http::STATUS_BAD_REQUEST|Http::STATUS_FORBIDDEN|Http::STATUS_NOT_FOUND, null, array{}>
	 *
	 * 200: Attendee demoted from moderator successfully
	 * 400: Demoting attendee from moderator is not possible
	 * 403: Demoting attendee from moderator is not allowed
	 * 404: Attendee not found
	 */
	#[PublicPage]
	#[RequireModeratorParticipant]
	public function demoteModerator(int $attendeeId): DataResponse {
		return $this->changeParticipantType($attendeeId, false);
	}

	/**
	 * Toggle a user/guest to moderator/guest-moderator or vice-versa based on
	 * attendeeId
	 *
	 * @param int $attendeeId
	 * @psalm-param non-negative-int $attendeeId
	 * @param bool $promote Shall the attendee be promoted or demoted
	 * @return DataResponse<Http::STATUS_OK|Http::STATUS_BAD_REQUEST|Http::STATUS_FORBIDDEN|Http::STATUS_NOT_FOUND, null, array{}>
	 */
	protected function changeParticipantType(int $attendeeId, bool $promote): DataResponse {
		try {
			$targetParticipant = $this->participantService->getParticipantByAttendeeId($this->room, $attendeeId);
		} catch (ParticipantNotFoundException $e) {
			return new DataResponse(null, Http::STATUS_NOT_FOUND);
		}

		$attendee = $targetParticipant->getAttendee();

		if ($attendee->getActorType() === Attendee::ACTOR_USERS
			&& $attendee->getActorId() === MatterbridgeManager::BRIDGE_BOT_USERID) {
			return new DataResponse(null, Http::STATUS_NOT_FOUND);
		}

		// Prevent users/moderators modifying themselves
		if ($attendee->getActorType() === $this->participant->getAttendee()->getActorType()) {
			if ($attendee->getActorId() === $this->participant->getAttendee()->getActorId()) {
				return new DataResponse(null, Http::STATUS_FORBIDDEN);
			}
		} elseif ($attendee->getActorType() === Attendee::ACTOR_GROUPS) {
			// Can not promote/demote groups
			return new DataResponse(null, Http::STATUS_BAD_REQUEST);
		}

		if ($promote === $targetParticipant->hasModeratorPermissions()) {
			// Prevent concurrent changes
			return new DataResponse(null, Http::STATUS_BAD_REQUEST);
		}

		if ($attendee->getParticipantType() === Participant::USER
			|| $attendee->getParticipantType() === Participant::USER_SELF_JOINED) {
			$newType = Participant::MODERATOR;
		} elseif ($attendee->getParticipantType() === Participant::GUEST) {
			$newType = Participant::GUEST_MODERATOR;
		} elseif ($attendee->getParticipantType() === Participant::MODERATOR) {
			$newType = Participant::USER;
		} elseif ($attendee->getParticipantType() === Participant::GUEST_MODERATOR) {
			$newType = Participant::GUEST;
		} else {
			return new DataResponse(null, Http::STATUS_BAD_REQUEST);
		}

		$this->participantService->updateParticipantType($this->room, $targetParticipant, $newType);

		return new DataResponse(null);
	}

	/**
	 * Update the permissions of a room
	 *
	 * @param 'call'|'default' $mode Level of the permissions ('call' (removed in Talk 20), 'default')
	 * @param int<0, 255> $permissions New permissions
	 * @psalm-param int-mask-of<Attendee::PERMISSIONS_*> $permissions
	 * @return DataResponse<Http::STATUS_OK, TalkRoom, array{}>|DataResponse<Http::STATUS_BAD_REQUEST, array{error: 'breakout-room'|'mode'|'type'|'value'}, array{}>
	 *
	 * 200: Permissions updated successfully
	 * 400: Updating permissions is not possible
	 */
	#[PublicPage]
	#[RequireModeratorParticipant]
	public function setPermissions(string $mode, int $permissions): DataResponse {
		if ($mode !== 'default') {
			return new DataResponse(['error' => 'mode'], Http::STATUS_BAD_REQUEST);
		}

		try {
			$this->roomService->setDefaultPermissions($this->room, $permissions);
		} catch (DefaultPermissionsException $e) {
			return new DataResponse(['error' => $e->getReason()], Http::STATUS_BAD_REQUEST);
		}

		return new DataResponse($this->formatRoom($this->room, $this->participant));
	}

	/**
	 * Update the permissions of an attendee
	 *
	 * @param int $attendeeId ID of the attendee
	 * @psalm-param non-negative-int $attendeeId
	 * @param 'set'|'remove'|'add' $method Method of updating permissions ('set', 'remove', 'add')
	 * @param int<0, 255> $permissions New permissions
	 * @psalm-param int-mask-of<Attendee::PERMISSIONS_*> $permissions
	 * @return DataResponse<Http::STATUS_OK, list<TalkParticipant>, array{X-Nextcloud-Has-User-Statuses?: true}>|DataResponse<Http::STATUS_BAD_REQUEST|Http::STATUS_FORBIDDEN|Http::STATUS_NOT_FOUND, array{error: 'participant'|'method'|'moderator'|'room-type'|'type'|'value'}, array{}>
	 *
	 * 200: Permissions updated successfully
	 * 400: Updating permissions is not possible
	 * 403: Missing permissions to update permissions
	 * 404: Attendee not found
	 */
	#[PublicPage]
	#[RequireModeratorParticipant]
	public function setAttendeePermissions(int $attendeeId, string $method, int $permissions): DataResponse {
		try {
			$targetParticipant = $this->participantService->getParticipantByAttendeeId($this->room, $attendeeId);
		} catch (ParticipantNotFoundException $e) {
			return new DataResponse(['error' => 'participant'], Http::STATUS_NOT_FOUND);
		}

		try {
			$this->participantService->updatePermissions($this->room, $targetParticipant, $method, $permissions);
		} catch (PermissionsException $e) {
			if ($e->getReason() === PermissionsException::REASON_MODERATOR) {
				return new DataResponse(['error' => $e->getReason()], Http::STATUS_FORBIDDEN);
			}
			return new DataResponse(['error' => $e->getReason()], Http::STATUS_BAD_REQUEST);
		}

		return $this->formatParticipantList([$targetParticipant], true);
	}

	/**
	 * Update the permissions of all attendees
	 *
	 * @param 'set'|'remove'|'add' $method Method of updating permissions ('set', 'remove', 'add')
	 * @psalm-param Attendee::PERMISSIONS_MODIFY_* $method
	 * @param int<0, 255> $permissions New permissions
	 * @psalm-param int-mask-of<Attendee::PERMISSIONS_*> $permissions
	 * @return DataResponse<Http::STATUS_OK, TalkRoom, array{}>|DataResponse<Http::STATUS_BAD_REQUEST, null, array{}>
	 * @deprecated Call permissions have been removed
	 *
	 * 200: Permissions updated successfully
	 * 400: Updating permissions is not possible
	 */
	#[PublicPage]
	#[RequireModeratorParticipant]
	public function setAllAttendeesPermissions(string $method, int $permissions): DataResponse {
		return new DataResponse(null, Http::STATUS_BAD_REQUEST);
	}

	/**
	 * Update the lobby state for a room
	 *
	 * @param int $state New state
	 * @psalm-param Webinary::LOBBY_* $state
	 * @param int|null $timer Timer when the lobby will be removed
	 * @psalm-param non-negative-int|null $timer
	 * @return DataResponse<Http::STATUS_OK, TalkRoom, array{}>|DataResponse<Http::STATUS_BAD_REQUEST, array{error: 'breakout-room'|'object'|'type'|'value'}, array{}>
	 *
	 * 200: Lobby state updated successfully
	 * 400: Updating lobby state is not possible
	 */
	#[NoAdminRequired]
	#[RequireModeratorParticipant]
	public function setLobby(int $state, ?int $timer = null): DataResponse {
		$timerDateTime = null;
		if ($timer !== null && $timer > 0) {
			try {
				$timerDateTime = $this->timeFactory->getDateTime('@' . $timer);
				$timerDateTime->setTimezone(new \DateTimeZone('UTC'));
			} catch (\Exception $e) {
				return new DataResponse(['error' => LobbyException::REASON_VALUE], Http::STATUS_BAD_REQUEST);
			}
		}

		if ($this->room->getObjectType() === BreakoutRoom::PARENT_OBJECT_TYPE) {
			// Do not allow manual changing the lobby in breakout rooms
			return new DataResponse(['error' => LobbyException::REASON_BREAKOUT_ROOM], Http::STATUS_BAD_REQUEST);
		}

		try {
			$this->roomService->setLobby($this->room, $state, $timerDateTime);
		} catch (LobbyException $e) {
			return new DataResponse(['error' => $e->getReason()], Http::STATUS_BAD_REQUEST);
		}

		if ($state === Webinary::LOBBY_NON_MODERATORS) {
			$participants = $this->participantService->getParticipantsInCall($this->room);
			foreach ($participants as $participant) {
				if ($participant->hasModeratorPermissions()) {
					continue;
				}

				$this->participantService->changeInCall($this->room, $participant, Participant::FLAG_DISCONNECTED);
			}
		}

		return new DataResponse($this->formatRoom($this->room, $this->participant));
	}

	/**
	 * Update SIP enabled state
	 *
	 * @param 0|1|2 $state New state
	 * @psalm-param Webinary::SIP_* $state
	 * @return DataResponse<Http::STATUS_OK, TalkRoom, array{}>|DataResponse<Http::STATUS_UNAUTHORIZED|Http::STATUS_FORBIDDEN|Http::STATUS_PRECONDITION_FAILED, array{error: 'config'}, array{}>|DataResponse<Http::STATUS_BAD_REQUEST, array{error: 'breakout-room'|'token'|'type'|'value'}, array{}>
	 *
	 * 200: SIP enabled state updated successfully
	 * 400: Updating SIP enabled state is not possible
	 * 401: User not found
	 * 403: Missing permissions to update SIP enabled state
	 * 412: SIP not configured
	 */
	#[NoAdminRequired]
	#[RequireModeratorParticipant]
	public function setSIPEnabled(int $state): DataResponse {
		$user = $this->userManager->get($this->userId);
		if (!$user instanceof IUser) {
			return new DataResponse(['error' => 'config'], Http::STATUS_UNAUTHORIZED);
		}

		if (!$this->talkConfig->canUserEnableSIP($user)) {
			return new DataResponse(['error' => 'config'], Http::STATUS_FORBIDDEN);
		}

		if (!$this->talkConfig->isSIPConfigured()) {
			return new DataResponse(['error' => 'config'], Http::STATUS_PRECONDITION_FAILED);
		}

		try {
			$this->roomService->setSIPEnabled($this->room, $state);
		} catch (SipConfigurationException $e) {
			return new DataResponse(['error' => $e->getReason()], Http::STATUS_BAD_REQUEST);
		}

		return new DataResponse($this->formatRoom($this->room, $this->participant));
	}

	/**
	 * Set recording consent requirement for this conversation
	 *
	 * @param int $recordingConsent New consent setting for the conversation
	 *                              (Only {@see RecordingService::CONSENT_REQUIRED_NO} and {@see RecordingService::CONSENT_REQUIRED_YES} are allowed here.)
	 * @psalm-param RecordingService::CONSENT_REQUIRED_NO|RecordingService::CONSENT_REQUIRED_YES $recordingConsent
	 * @return DataResponse<Http::STATUS_OK, TalkRoom, array{}>|DataResponse<Http::STATUS_BAD_REQUEST, array{error: 'breakout-room'|'call'|'value'}, array{}>|DataResponse<Http::STATUS_PRECONDITION_FAILED, array{error: 'config'}, array{}>
	 *
	 * 200: Recording consent requirement set successfully
	 * 400: Setting recording consent requirement is not possible
	 * 412: No recording server is configured
	 */
	#[NoAdminRequired]
	#[RequireLoggedInModeratorParticipant]
	public function setRecordingConsent(int $recordingConsent): DataResponse {
		if (!$this->talkConfig->isRecordingEnabled()) {
			return new DataResponse(['error' => 'config'], Http::STATUS_PRECONDITION_FAILED);
		}

		try {
			$this->roomService->setRecordingConsent($this->room, $recordingConsent);
		} catch (RecordingConsentException $e) {
			return new DataResponse(['error' => $e->getReason()], Http::STATUS_BAD_REQUEST);
		}

		return new DataResponse($this->formatRoom($this->room, $this->participant));
	}

	/**
	 * Resend invitations
	 *
	 * @param int|null $attendeeId ID of the attendee
	 * @psalm-param non-negative-int|null $attendeeId
	 * @return DataResponse<Http::STATUS_OK|Http::STATUS_NOT_FOUND, null, array{}>
	 *
	 * 200: Invitation resent successfully
	 * 404: Attendee not found
	 */
	#[NoAdminRequired]
	#[RequireModeratorParticipant]
	public function resendInvitations(?int $attendeeId): DataResponse {
		/** @var Participant[] $participants */
		$participants = [];

		// targeting specific participant
		if ($attendeeId !== null) {
			try {
				$participants[] = $this->participantService->getParticipantByAttendeeId($this->room, $attendeeId);
			} catch (ParticipantNotFoundException $e) {
				return new DataResponse(null, Http::STATUS_NOT_FOUND);
			}
		} else {
			$participants = $this->participantService->getParticipantsByActorType($this->room, Attendee::ACTOR_EMAILS);
		}

		foreach ($participants as $participant) {
			if ($participant->getAttendee()->getActorType() === Attendee::ACTOR_EMAILS) {
				// generate PIN if applicable
				$this->participantService->generatePinForParticipant($this->room, $participant);
				$this->guestManager->sendEmailInvitation($this->room, $participant);
			}
		}
		return new DataResponse(null);
	}

	/**
	 * Update message expiration time
	 *
	 * @param int $seconds New time
	 * @psalm-param non-negative-int $seconds
	 * @return DataResponse<Http::STATUS_OK, TalkRoom, array{}>|DataResponse<Http::STATUS_BAD_REQUEST, array{error: 'breakout-room'|'type'|'value'}, array{}>
	 *
	 * 200: Message expiration time updated successfully
	 * 400: Updating message expiration time is not possible
	 */
	#[PublicPage]
	#[RequireModeratorParticipant]
	public function setMessageExpiration(int $seconds): DataResponse {
		try {
			$this->roomService->setMessageExpiration($this->room, $seconds);
		} catch (MessageExpirationException $e) {
			return new DataResponse(['error' => $e->getReason()], Http::STATUS_BAD_REQUEST);
		}

		return new DataResponse($this->formatRoom($this->room, $this->participant));
	}

	/**
	 * Import a list of email attendees
	 *
	 * Content format is comma separated values:
	 * - Header line is required and must match `"email","name"` or `"email"`
	 * - One entry per line (e.g. `"John Doe","john@example.tld"`)
	 *
	 * Required capability: `email-csv-import`
	 *
	 * @param bool $testRun When set to true, the file is validated and no email is actually sent nor any participant added to the conversation
	 * @return DataResponse<Http::STATUS_OK, array{invites: non-negative-int, duplicates: non-negative-int, invalid?: non-negative-int, invalidLines?: list<non-negative-int>, type?: int<-1, 6>}, array{}>|DataResponse<Http::STATUS_BAD_REQUEST, array{error: 'room'|'file'|'header-email'|'header-name'|'rows', message?: string, invites?: non-negative-int, duplicates?: non-negative-int, invalid?: non-negative-int, invalidLines?: list<non-negative-int>, type?: int<-1, 6>}, array{}>
	 *
	 * 200: All entries imported successfully
	 * 400: Import was not successful. When message is provided the string is in user language and should be displayed as an error.
	 */
	#[NoAdminRequired]
	#[RequireModeratorParticipant]
	public function importEmailsAsParticipants(bool $testRun = false): DataResponse {
		$file = $this->request->getUploadedFile('file');
		if ($file === null) {
			return new DataResponse([
				'error' => 'file',
				'message' => $this->l->t('Uploading the file failed'),
			], Http::STATUS_BAD_REQUEST);
		}
		if ($file['error'] !== 0) {
			$this->logger->error('Uploading email CSV file failed with error: ' . $file['error']);
			return new DataResponse([
				'error' => 'file',
				'message' => $this->l->t('Uploading the file failed'),
			], Http::STATUS_BAD_REQUEST);
		}

		try {
			$data = $this->guestManager->importEmails($this->room, $file['tmp_name'], $testRun);
			return new DataResponse($data);
		} catch (GuestImportException $e) {
			return new DataResponse($e->getData(), Http::STATUS_BAD_REQUEST);
		}

	}

	/**
	 * Get capabilities for a room
	 *
	 * See "Capability handling in federated conversations" in https://github.com/nextcloud/spreed/issues/10680
	 * to learn which capabilities should be considered from the local server or from the remote server.
	 *
	 * @return DataResponse<Http::STATUS_OK, TalkCapabilities|\stdClass, array{X-Nextcloud-Talk-Hash?: string, X-Nextcloud-Talk-Proxy-Hash?: string}>
	 *
	 * 200: Get capabilities successfully
	 */
	#[FederationSupported]
	#[PublicPage]
	#[RequireParticipant]
	public function getCapabilities(): DataResponse {
		$headers = [];
		if ($this->room->isFederatedConversation()) {
			/** @var \OCA\Talk\Federation\Proxy\TalkV1\Controller\RoomController $proxy */
			$proxy = \OCP\Server::get(\OCA\Talk\Federation\Proxy\TalkV1\Controller\RoomController::class);
			$response = $proxy->getCapabilities($this->room, $this->participant);

			/** @var TalkCapabilities|array<empty> $data */
			$data = $response->getData();

			/**
			 * IMPORTANT:
			 * When adding, changing or removing anything here, update
			 * @see ProxyRequest::overwrittenRemoteTalkHash()
			 * so clients correctly refresh their capabilities.
			 */
			if (isset($data['config']['chat']['read-privacy'])) {
				$data['config']['chat']['read-privacy'] = Participant::PRIVACY_PRIVATE;
			}
			if (isset($data['config']['chat']['typing-privacy'])) {
				$data['config']['chat']['typing-privacy'] = Participant::PRIVACY_PRIVATE;
			}
			if (isset($data['config']['call']['start-without-media'])) {
				$data['config']['call']['start-without-media'] = $this->talkConfig->getCallsStartWithoutMedia($this->userId);
			}
			if (isset($data['config']['call']['blur-virtual-background'])) {
				$data['config']['call']['blur-virtual-background'] = $this->talkConfig->getBlurVirtualBackground($this->userId);
			}
			if (isset($data['config']['conversations']['list-style'])) {
				$data['config']['conversations']['list-style'] = $this->talkConfig->getConversationsListStyle($this->userId);
			}

			if ($response->getHeaders()['X-Nextcloud-Talk-Hash']) {
				$headers['X-Nextcloud-Talk-Proxy-Hash'] = $response->getHeaders()['X-Nextcloud-Talk-Hash'];
			}

			/** @var TalkCapabilities|\stdClass $data */
			$data = !empty($data) ? $data : new \stdClass();
		} else {
			$capabilities = $this->capabilities->getCapabilities();
			$data = $capabilities['spreed'] ?? new \stdClass();
			$headers['X-Nextcloud-Talk-Hash'] = sha1(json_encode($capabilities));
		}

		return new DataResponse($data, Http::STATUS_OK, $headers);
	}

	/**
	 * Schedule a meeting for a conversation
	 *
	 * Required capability: `schedule-meeting`
	 *
	 * @param string $calendarUri Last part of the calendar URI as seen by the participant e.g. 'personal' or 'company_shared_by_other_user'
	 * @param int $start Unix timestamp when the meeting starts
	 * @param ?list<int> $attendeeIds List of attendee ids to invite, if null everyone will be invited, if empty array only the actor will receive the event
	 * @param ?int $end Unix timestamp when the meeting ends, falls back to 60 minutes after start
	 * @param ?string $title Title or summary of the event, falling back to the conversation name if none is given
	 * @param ?string $description Description of the event, falling back to the conversation description if none is given
	 * @return DataResponse<Http::STATUS_OK, null, array{}>|DataResponse<Http::STATUS_BAD_REQUEST, array{error: 'calendar'|'conversation'|'email'|'end'|'start'}, array{}>
	 *
	 * 200: Meeting scheduled
	 * 400: Meeting could not be created successfully
	 */
	#[NoAdminRequired]
	#[RequireLoggedInModeratorParticipant]
	public function scheduleMeeting(string $calendarUri, int $start, ?array $attendeeIds = null, ?int $end = null, ?string $title = null, ?string $description = null): DataResponse {
		if ($this->room->getType() === Room::TYPE_ONE_TO_ONE_FORMER) {
			return new DataResponse(['error' => 'conversation'], Http::STATUS_BAD_REQUEST);
		}

		$eventBuilder = $this->calendarManager->createEventBuilder();
		$calendars = $this->calendarManager->getCalendarsForPrincipal('principals/users/' . $this->userId, [$calendarUri]);

		if (empty($calendars)) {
			return new DataResponse(['error' => 'calendar'], Http::STATUS_BAD_REQUEST);
		}

		/** @var ICreateFromString $calendar */
		$calendar = array_pop($calendars);

		$user = $this->userManager->get($this->userId);
		if (!$user instanceof IUser || $user->getEMailAddress() === null) {
			return new DataResponse(['error' => 'email'], Http::STATUS_BAD_REQUEST);
		}

		$startDate = $this->timeFactory->getDateTime('@' . $start);
		if ($start < $this->timeFactory->getTime()) {
			return new DataResponse(['error' => 'start'], Http::STATUS_BAD_REQUEST);
		}

		if ($end !== null) {
			$endDate = $this->timeFactory->getDateTime('@' . $end);
			if ($start >= $end) {
				return new DataResponse(['error' => 'end'], Http::STATUS_BAD_REQUEST);
			}
		} else {
			$endDate = clone $startDate;
			$endDate->add(new \DateInterval('PT1H'));
		}

		$eventBuilder->setLocation(
			$this->url->linkToRouteAbsolute(
				'spreed.Page.showCall',
				['token' => $this->room->getToken()]
			)
		);
		$eventBuilder->setSummary($title ?: $this->room->getDisplayName($this->userId));
		$eventBuilder->setDescription($description ?: $this->room->getDescription());
		$eventBuilder->setOrganizer($user->getEMailAddress(), $user->getDisplayName() ?: $this->userId);
		$eventBuilder->setStartDate($startDate);
		$eventBuilder->setEndDate($endDate);
		if (method_exists($eventBuilder, 'setStatus')) {
			$eventBuilder->setStatus(CalendarEventStatus::CONFIRMED);
		}

		if ($this->room->getType() === Room::TYPE_ONE_TO_ONE) {
			$this->participantService->ensureOneToOneRoomIsFilled($this->room);
		}

		$userAttendees = $this->participantService->getParticipantsByActorType($this->room, Attendee::ACTOR_USERS);
		foreach ($userAttendees as $userAttendee) {
			if ($attendeeIds !== null && !in_array($userAttendee->getAttendee()->getId(), $attendeeIds, true)) {
				continue;
			}

			$targetUser = $this->userManager->get($userAttendee->getAttendee()->getActorId());
			if (!$targetUser instanceof IUser) {
				continue;
			}
			if ($targetUser->getEMailAddress() === null) {
				continue;
			}
			// Do not add the organizer as an attendee
			if ($targetUser->getEMailAddress() === $user->getEMailAddress()) {
				continue;
			}

			$eventBuilder->addAttendee(
				$targetUser->getEMailAddress(),
				$targetUser->getDisplayName(),
			);
		}

		$emailGuests = $this->participantService->getParticipantsByActorType($this->room, Attendee::ACTOR_EMAILS);
		foreach ($emailGuests as $emailGuest) {
			if ($attendeeIds !== null && !in_array($emailGuest->getAttendee()->getId(), $attendeeIds, true)) {
				continue;
			}

			$eventBuilder->addAttendee(
				$emailGuest->getAttendee()->getInvitedCloudId(),
				$emailGuest->getAttendee()->getDisplayName(),
			);
		}

		try {
			$eventBuilder->createInCalendar($calendar);
		} catch (\InvalidArgumentException|CalendarException $e) {
			$this->logger->debug('Failed to get calendar to schedule a meeting', ['exception' => $e]);
			return new DataResponse(['error' => 'calendar'], Http::STATUS_BAD_REQUEST);
		}

		return new DataResponse(null, Http::STATUS_OK);
	}
}
