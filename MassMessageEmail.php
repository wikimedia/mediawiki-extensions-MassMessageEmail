<?php
/**
 * Adds email capability to the MassMessage extension
 * Tested with MassMessage 0.4.0
 * See https://mediawiki.org/wiki/Extension:MassMessage
 *
 * @file
 * @ingroup Extensions
 * @author Ike Hecht
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 * @todo Add extension.json
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	exit;
}

$wgExtensionCredits['other'][] = array(
	'path' => __FILE__,
	'name' => 'MassMessageEmail',
	'author' => 'Ike Hecht',
	'url' => 'https://www.mediawiki.org/wiki/Extension:MassMessageEmail',
	'descriptionmsg' => 'massmessageemail-desc',
	'version' => '0.2.0',
	'license-name' => 'GPL-2.0-or-later',
);

$wgMessagesDirs['MassMessageEmail'] = __DIR__ . '/i18n';

$wgAutoloadClasses['MassMessageEmailHooks'] = __DIR__ . '/MassMessageEmail.hooks.php';

$wgHooks['MassMessageJobBeforeMessageSent'][] = 'MassMessageEmailHooks::onMassMessageJobBeforeMessageSent';
