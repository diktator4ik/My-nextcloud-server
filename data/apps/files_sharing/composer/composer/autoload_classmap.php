<?php

// autoload_classmap.php @generated by Composer

$vendorDir = dirname(__DIR__);
$baseDir = $vendorDir;

return array(
    'Composer\\InstalledVersions' => $vendorDir . '/composer/InstalledVersions.php',
    'OCA\\Files_Sharing\\Activity\\Filter' => $baseDir . '/../lib/Activity/Filter.php',
    'OCA\\Files_Sharing\\Activity\\Providers\\Base' => $baseDir . '/../lib/Activity/Providers/Base.php',
    'OCA\\Files_Sharing\\Activity\\Providers\\Downloads' => $baseDir . '/../lib/Activity/Providers/Downloads.php',
    'OCA\\Files_Sharing\\Activity\\Providers\\Groups' => $baseDir . '/../lib/Activity/Providers/Groups.php',
    'OCA\\Files_Sharing\\Activity\\Providers\\PublicLinks' => $baseDir . '/../lib/Activity/Providers/PublicLinks.php',
    'OCA\\Files_Sharing\\Activity\\Providers\\RemoteShares' => $baseDir . '/../lib/Activity/Providers/RemoteShares.php',
    'OCA\\Files_Sharing\\Activity\\Providers\\Users' => $baseDir . '/../lib/Activity/Providers/Users.php',
    'OCA\\Files_Sharing\\Activity\\Settings\\PublicLinks' => $baseDir . '/../lib/Activity/Settings/PublicLinks.php',
    'OCA\\Files_Sharing\\Activity\\Settings\\PublicLinksUpload' => $baseDir . '/../lib/Activity/Settings/PublicLinksUpload.php',
    'OCA\\Files_Sharing\\Activity\\Settings\\RemoteShare' => $baseDir . '/../lib/Activity/Settings/RemoteShare.php',
    'OCA\\Files_Sharing\\Activity\\Settings\\ShareActivitySettings' => $baseDir . '/../lib/Activity/Settings/ShareActivitySettings.php',
    'OCA\\Files_Sharing\\Activity\\Settings\\Shared' => $baseDir . '/../lib/Activity/Settings/Shared.php',
    'OCA\\Files_Sharing\\AppInfo\\Application' => $baseDir . '/../lib/AppInfo/Application.php',
    'OCA\\Files_Sharing\\BackgroundJob\\FederatedSharesDiscoverJob' => $baseDir . '/../lib/BackgroundJob/FederatedSharesDiscoverJob.php',
    'OCA\\Files_Sharing\\Cache' => $baseDir . '/../lib/Cache.php',
    'OCA\\Files_Sharing\\Capabilities' => $baseDir . '/../lib/Capabilities.php',
    'OCA\\Files_Sharing\\Collaboration\\ShareRecipientSorter' => $baseDir . '/../lib/Collaboration/ShareRecipientSorter.php',
    'OCA\\Files_Sharing\\Command\\CleanupRemoteStorages' => $baseDir . '/../lib/Command/CleanupRemoteStorages.php',
    'OCA\\Files_Sharing\\Command\\DeleteOrphanShares' => $baseDir . '/../lib/Command/DeleteOrphanShares.php',
    'OCA\\Files_Sharing\\Command\\ExiprationNotification' => $baseDir . '/../lib/Command/ExiprationNotification.php',
    'OCA\\Files_Sharing\\Command\\FixShareOwners' => $baseDir . '/../lib/Command/FixShareOwners.php',
    'OCA\\Files_Sharing\\Config\\ConfigLexicon' => $baseDir . '/../lib/Config/ConfigLexicon.php',
    'OCA\\Files_Sharing\\Controller\\AcceptController' => $baseDir . '/../lib/Controller/AcceptController.php',
    'OCA\\Files_Sharing\\Controller\\DeletedShareAPIController' => $baseDir . '/../lib/Controller/DeletedShareAPIController.php',
    'OCA\\Files_Sharing\\Controller\\ExternalSharesController' => $baseDir . '/../lib/Controller/ExternalSharesController.php',
    'OCA\\Files_Sharing\\Controller\\PublicPreviewController' => $baseDir . '/../lib/Controller/PublicPreviewController.php',
    'OCA\\Files_Sharing\\Controller\\RemoteController' => $baseDir . '/../lib/Controller/RemoteController.php',
    'OCA\\Files_Sharing\\Controller\\SettingsController' => $baseDir . '/../lib/Controller/SettingsController.php',
    'OCA\\Files_Sharing\\Controller\\ShareAPIController' => $baseDir . '/../lib/Controller/ShareAPIController.php',
    'OCA\\Files_Sharing\\Controller\\ShareController' => $baseDir . '/../lib/Controller/ShareController.php',
    'OCA\\Files_Sharing\\Controller\\ShareInfoController' => $baseDir . '/../lib/Controller/ShareInfoController.php',
    'OCA\\Files_Sharing\\Controller\\ShareesAPIController' => $baseDir . '/../lib/Controller/ShareesAPIController.php',
    'OCA\\Files_Sharing\\DefaultPublicShareTemplateProvider' => $baseDir . '/../lib/DefaultPublicShareTemplateProvider.php',
    'OCA\\Files_Sharing\\DeleteOrphanedSharesJob' => $baseDir . '/../lib/DeleteOrphanedSharesJob.php',
    'OCA\\Files_Sharing\\Event\\BeforeTemplateRenderedEvent' => $baseDir . '/../lib/Event/BeforeTemplateRenderedEvent.php',
    'OCA\\Files_Sharing\\Event\\ShareLinkAccessedEvent' => $baseDir . '/../lib/Event/ShareLinkAccessedEvent.php',
    'OCA\\Files_Sharing\\Event\\ShareMountedEvent' => $baseDir . '/../lib/Event/ShareMountedEvent.php',
    'OCA\\Files_Sharing\\Exceptions\\BrokenPath' => $baseDir . '/../lib/Exceptions/BrokenPath.php',
    'OCA\\Files_Sharing\\Exceptions\\S2SException' => $baseDir . '/../lib/Exceptions/S2SException.php',
    'OCA\\Files_Sharing\\Exceptions\\SharingRightsException' => $baseDir . '/../lib/Exceptions/SharingRightsException.php',
    'OCA\\Files_Sharing\\ExpireSharesJob' => $baseDir . '/../lib/ExpireSharesJob.php',
    'OCA\\Files_Sharing\\External\\Cache' => $baseDir . '/../lib/External/Cache.php',
    'OCA\\Files_Sharing\\External\\Manager' => $baseDir . '/../lib/External/Manager.php',
    'OCA\\Files_Sharing\\External\\Mount' => $baseDir . '/../lib/External/Mount.php',
    'OCA\\Files_Sharing\\External\\MountProvider' => $baseDir . '/../lib/External/MountProvider.php',
    'OCA\\Files_Sharing\\External\\Scanner' => $baseDir . '/../lib/External/Scanner.php',
    'OCA\\Files_Sharing\\External\\Storage' => $baseDir . '/../lib/External/Storage.php',
    'OCA\\Files_Sharing\\External\\Watcher' => $baseDir . '/../lib/External/Watcher.php',
    'OCA\\Files_Sharing\\Helper' => $baseDir . '/../lib/Helper.php',
    'OCA\\Files_Sharing\\Hooks' => $baseDir . '/../lib/Hooks.php',
    'OCA\\Files_Sharing\\ISharedMountPoint' => $baseDir . '/../lib/ISharedMountPoint.php',
    'OCA\\Files_Sharing\\ISharedStorage' => $baseDir . '/../lib/ISharedStorage.php',
    'OCA\\Files_Sharing\\Listener\\BeforeDirectFileDownloadListener' => $baseDir . '/../lib/Listener/BeforeDirectFileDownloadListener.php',
    'OCA\\Files_Sharing\\Listener\\BeforeNodeReadListener' => $baseDir . '/../lib/Listener/BeforeNodeReadListener.php',
    'OCA\\Files_Sharing\\Listener\\BeforeZipCreatedListener' => $baseDir . '/../lib/Listener/BeforeZipCreatedListener.php',
    'OCA\\Files_Sharing\\Listener\\LoadAdditionalListener' => $baseDir . '/../lib/Listener/LoadAdditionalListener.php',
    'OCA\\Files_Sharing\\Listener\\LoadPublicFileRequestAuthListener' => $baseDir . '/../lib/Listener/LoadPublicFileRequestAuthListener.php',
    'OCA\\Files_Sharing\\Listener\\LoadSidebarListener' => $baseDir . '/../lib/Listener/LoadSidebarListener.php',
    'OCA\\Files_Sharing\\Listener\\ShareInteractionListener' => $baseDir . '/../lib/Listener/ShareInteractionListener.php',
    'OCA\\Files_Sharing\\Listener\\UserAddedToGroupListener' => $baseDir . '/../lib/Listener/UserAddedToGroupListener.php',
    'OCA\\Files_Sharing\\Listener\\UserShareAcceptanceListener' => $baseDir . '/../lib/Listener/UserShareAcceptanceListener.php',
    'OCA\\Files_Sharing\\Middleware\\OCSShareAPIMiddleware' => $baseDir . '/../lib/Middleware/OCSShareAPIMiddleware.php',
    'OCA\\Files_Sharing\\Middleware\\ShareInfoMiddleware' => $baseDir . '/../lib/Middleware/ShareInfoMiddleware.php',
    'OCA\\Files_Sharing\\Middleware\\SharingCheckMiddleware' => $baseDir . '/../lib/Middleware/SharingCheckMiddleware.php',
    'OCA\\Files_Sharing\\Migration\\OwncloudGuestShareType' => $baseDir . '/../lib/Migration/OwncloudGuestShareType.php',
    'OCA\\Files_Sharing\\Migration\\SetAcceptedStatus' => $baseDir . '/../lib/Migration/SetAcceptedStatus.php',
    'OCA\\Files_Sharing\\Migration\\SetPasswordColumn' => $baseDir . '/../lib/Migration/SetPasswordColumn.php',
    'OCA\\Files_Sharing\\Migration\\Version11300Date20201120141438' => $baseDir . '/../lib/Migration/Version11300Date20201120141438.php',
    'OCA\\Files_Sharing\\Migration\\Version21000Date20201223143245' => $baseDir . '/../lib/Migration/Version21000Date20201223143245.php',
    'OCA\\Files_Sharing\\Migration\\Version22000Date20210216084241' => $baseDir . '/../lib/Migration/Version22000Date20210216084241.php',
    'OCA\\Files_Sharing\\Migration\\Version24000Date20220208195521' => $baseDir . '/../lib/Migration/Version24000Date20220208195521.php',
    'OCA\\Files_Sharing\\Migration\\Version24000Date20220404142216' => $baseDir . '/../lib/Migration/Version24000Date20220404142216.php',
    'OCA\\Files_Sharing\\Migration\\Version31000Date20240821142813' => $baseDir . '/../lib/Migration/Version31000Date20240821142813.php',
    'OCA\\Files_Sharing\\MountProvider' => $baseDir . '/../lib/MountProvider.php',
    'OCA\\Files_Sharing\\Notification\\Listener' => $baseDir . '/../lib/Notification/Listener.php',
    'OCA\\Files_Sharing\\Notification\\Notifier' => $baseDir . '/../lib/Notification/Notifier.php',
    'OCA\\Files_Sharing\\OrphanHelper' => $baseDir . '/../lib/OrphanHelper.php',
    'OCA\\Files_Sharing\\ResponseDefinitions' => $baseDir . '/../lib/ResponseDefinitions.php',
    'OCA\\Files_Sharing\\Scanner' => $baseDir . '/../lib/Scanner.php',
    'OCA\\Files_Sharing\\Settings\\Personal' => $baseDir . '/../lib/Settings/Personal.php',
    'OCA\\Files_Sharing\\ShareBackend\\File' => $baseDir . '/../lib/ShareBackend/File.php',
    'OCA\\Files_Sharing\\ShareBackend\\Folder' => $baseDir . '/../lib/ShareBackend/Folder.php',
    'OCA\\Files_Sharing\\SharedMount' => $baseDir . '/../lib/SharedMount.php',
    'OCA\\Files_Sharing\\SharedStorage' => $baseDir . '/../lib/SharedStorage.php',
    'OCA\\Files_Sharing\\SharesReminderJob' => $baseDir . '/../lib/SharesReminderJob.php',
    'OCA\\Files_Sharing\\Updater' => $baseDir . '/../lib/Updater.php',
    'OCA\\Files_Sharing\\ViewOnly' => $baseDir . '/../lib/ViewOnly.php',
);
