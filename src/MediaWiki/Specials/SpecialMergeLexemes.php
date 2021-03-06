<?php

namespace Wikibase\Lexeme\MediaWiki\Specials;

use Exception;
use Html;
use HTMLForm;
use InvalidArgumentException;
use Message;
use SpecialPage;
use UserBlockedError;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Interactors\MergeLexemes\MergeLexemesInteractor;
use Wikibase\Lexeme\Domain\Merge\Exceptions\MergingException;
use Wikibase\Lexeme\WikibaseLexemeServices;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\Localizer\ExceptionLocalizer;
use Wikibase\Repo\WikibaseRepo;

/**
 * Special page for merging one lexeme into another.
 *
 * @license GPL-2.0-or-later
 */
class SpecialMergeLexemes extends SpecialPage {

	const FROM_ID = 'from-id';
	const TO_ID = 'to-id';

	/**
	 * @var MergeLexemesInteractor
	 */
	private $mergeInteractor;

	/**
	 * @var EntityTitleLookup
	 */
	private $titleLookup;

	/**
	 * @var ExceptionLocalizer
	 */
	private $exceptionLocalizer;

	public function __construct(
		MergeLexemesInteractor $mergeInteractor,
		EntityTitleLookup $titleLookup,
		ExceptionLocalizer $exceptionLocalizer
	) {
		parent::__construct( 'MergeLexemes', 'item-merge' );
		$this->mergeInteractor = $mergeInteractor;
		$this->titleLookup = $titleLookup;
		$this->exceptionLocalizer = $exceptionLocalizer;
	}

	/**
	 * @see SpecialWikibasePage::execute
	 **/
	public function execute( $subPage ) {
		$this->setHeaders();
		$this->outputHeader( 'wikibase-mergelexemes-summary' );

		if ( !$this->userCanExecute( $this->getUser() ) ) {
			$this->displayRestrictionError();
		}
		$this->checkBlocked();

		$sourceId = $this->getTextParam( self::FROM_ID );
		$targetId = $this->getTextParam( self::TO_ID );

		if ( $sourceId && $targetId ) {
			$this->mergeLexemes( $sourceId, $targetId );
		}

		$this->showMergeForm();
	}

	public function setHeaders() {
		$out = $this->getOutput();
		$out->setArticleRelated( false );
		$out->setPageTitle( $this->getDescription() );
	}

	private function checkBlocked() {
		if ( $this->getUser()->isBlockedFrom( $this->getFullTitle() ) ) {
			throw new UserBlockedError( $this->getUser()->getBlock() );
		}
	}

	public static function newFromGlobalState() {
		$repo = WikibaseRepo::getDefaultInstance();

		return new self(
			WikibaseLexemeServices::createGlobalInstance( false )->newMergeLexemesInteractor(),
			$repo->getEntityTitleLookup(),
			$repo->getExceptionLocalizer()
		);
	}

	private function showMergeForm() {
		HTMLForm::factory( 'ooui', $this->getFormElements(), $this->getContext() )
			->setId( 'wb-mergelexemes' )
			->setPreText( $this->anonymousEditWarning() )
			->setHeaderText( $this->msg( 'wikibase-lexeme-mergelexemes-intro' )->parse() )
			->setSubmitID( 'wb-mergelexemes-submit' )
			->setSubmitName( 'wikibase-lexeme-mergelexemes-submit' )
			->setSubmitTextMsg( 'wikibase-lexeme-mergelexemes-submit' )
			->setWrapperLegendMsg( 'special-mergelexemes' )
			->setSubmitCallback( function () {
			} )
			->show();
	}

	private function getFormElements() {
		return [
			self::FROM_ID => [
				'name' => self::FROM_ID,
				'default' => $this->getRequest()->getVal( self::FROM_ID ),
				'type' => 'text',
				'id' => 'wb-mergelexemes-from-id',
				'label-message' => 'wikibase-lexeme-mergelexemes-from-id'
			],
			self::TO_ID => [
				'name' => self::TO_ID,
				'default' => $this->getRequest()->getVal( self::TO_ID ),
				'type' => 'text',
				'id' => 'wb-mergelexemes-to-id',
				'label-message' => 'wikibase-lexeme-mergelexemes-to-id'
			]
		];
	}

	private function anonymousEditWarning() {
		if ( $this->getUser()->isAnon() ) {
			return Html::rawElement(
				'p',
				[ 'class' => 'warning' ],
				$this->msg( 'wikibase-anonymouseditwarning' )->escaped()
			);
		}

		return '';
	}

	private function mergeLexemes( $serializedSourceId, $serializedTargetId ) {
		$sourceId = $this->getLexemeId( $serializedSourceId );
		$targetId = $this->getLexemeId( $serializedTargetId );

		if ( !$sourceId ) {
			$this->showInvalidLexemeIdError( $serializedSourceId );
			return;
		}
		if ( !$targetId ) {
			$this->showInvalidLexemeIdError( $serializedTargetId );
			return;
		}

		try {
			/** @var LexemeId $sourceId */
			/** @var LexemeId $targetId */
			$this->mergeInteractor->mergeLexemes( $sourceId, $targetId );
		} catch ( MergingException $e ) {
			$this->showErrorHTML( $e->getErrorMessage()->escaped() );
			return;
		}

		$this->showSuccessMessage( $sourceId, $targetId );
	}

	private function getTextParam( $name ) {
		$value = $this->getRequest()->getText( $name, '' );
		return trim( $value );
	}

	/**
	 * @param string $idSerialization
	 *
	 * @return LexemeId|false
	 */
	private function getLexemeId( $idSerialization ) {
		try {
			return new LexemeId( $idSerialization );
		} catch ( InvalidArgumentException $e ) {
			return false;
		}
	}

	private function showSuccessMessage( LexemeId $sourceId, LexemeId $targetId ) {
		try {
			$sourceTitle = $this->titleLookup->getTitleForId( $sourceId );
			$targetTitle = $this->titleLookup->getTitleForId( $targetId );
		} catch ( Exception $e ) {
			$this->showErrorHTML( $this->exceptionLocalizer->getExceptionMessage( $e )->escaped() );
			return;
		}

		$this->getOutput()->addWikiMsg(
			'wikibase-lexeme-mergelexemes-success',
			Message::rawParam(
				$this->getLinkRenderer()->makePreloadedLink( $sourceTitle )
			),
			Message::rawParam(
				$this->getLinkRenderer()->makePreloadedLink( $targetTitle )
			)
		);
	}

	private function showInvalidLexemeIdError( $id ) {
		$this->showErrorHTML(
			( new Message( 'wikibase-lexeme-mergelexemes-error-invalid-id', [ $id ] ) )
				->escaped()
		);
	}

	protected function getGroupName() {
		return 'wikibase';
	}

	protected function showErrorHTML( $error ) {
		$this->getOutput()->addHTML( '<p class="error">' . $error . '</p>' );
	}

	public function getDescription() {
		return $this->msg( 'special-mergelexemes' )->text();
	}

}
