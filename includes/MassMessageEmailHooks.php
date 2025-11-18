<?php

use MediaWiki\MassMessage\LanguageAwareText;
use MediaWiki\MassMessage\MessageBuilder;
use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;
use Soundasleep\Html2Text;
use Soundasleep\Html2TextException;

class MassMessageEmailHooks {

	/**
	 * Hooks into MassMessage
	 *
	 * @param callable $failureCallback
	 * @param Title $title target page
	 * @param string $subject
	 * @param string $message
	 * @param LanguageAwareText|null $pageSubject
	 * @param LanguageAwareText|null $pageMessage
	 * @param array $comment
	 * @return bool
	 */
	public static function onMassMessageJobBeforeMessageSent(
		callable $failureCallback,
		Title $title,
		string $subject,
		string $message,
		?LanguageAwareText $pageSubject,
		?LanguageAwareText $pageMessage,
		array $comment
	) {
		if ( $title->getNamespace() == NS_USER || $title->getNamespace() == NS_USER_TALK ) {
			$user = User::newFromName( $title->getBaseText() );
			if ( $user->canReceiveEmail() ) {
				$messageBuilder = new MessageBuilder;
				$subject = $messageBuilder->buildPlaintextSubject( $subject, $pageSubject );
				$messageWikitext = $messageBuilder->buildMessage(
					$message,
					$pageMessage,
					$title->getPageLanguage(),
					$comment
				);
				return self::sendMassMessageEmail( $failureCallback, $title, $subject, $messageWikitext );
			}
		}
		// We didn't do anything. Continue execution as if we're not here.
		return true;
	}

	/**
	 * Sends the email
	 *
	 * @param callable $failureCallback
	 * @param Title $title Target page
	 * @param string $subject Plaintext subject
	 * @param string $messageWikitext Plaintext version of message
	 * @return bool
	 */
	public static function sendMassMessageEmail(
		callable $failureCallback,
		Title $title,
		string $subject,
		string $messageWikitext
	) {
		$user = User::newFromName( $title->getBaseText() );

		// Make sure we don't send relative links in the email. Shouldn't that be a ParserOption?
		$parser = MediaWikiServices::getInstance()->getParserFactory()->create();
		$parserOutput = $parser->parse( $messageWikitext, $title, ParserOptions::newFromAnon() );
		// ... and also generate HTML from the wikitext, which makes sense since
		// we're sending an email, but it requires $wgAllowHTMLEmail
		$html = $parserOutput->getText( [
			'enableSectionEditLinks' => false,
			// absoluteURLs is new in 1.38
			'absoluteURLs' => true,
		] );

		try {
			$text = Html2Text::convert( $html, [ 'ignore_errors' => true, 'drop_links' => true ] );
		} catch ( Html2TextException ) {
			wfDebugLog( 'MassMessageEmail',
				'Unable to convert HTML email version into text version, falling back to tags stripping' );
			$text = strip_tags( $html );
		}

		$status = $user->sendMail( $subject, [ 'text' => $text, 'html' => $html ] );
		if ( !$status->isGood() ) {
			/** @todo This should really be sending a code - not a message */
			$failureCallback( $status->getMessage() );
			return true;
			// If the status isn't good, MassMessage will proceed to post to the user's page instead.
		} else {
			// Good status - stop execution since we already emailed the user.
			return false;
		}
	}
}
