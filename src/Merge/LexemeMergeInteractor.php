<?php

namespace Wikibase\Lexeme\Merge;

use User;
use WatchedItemStoreInterface;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Lexeme\Merge\Exceptions\LexemeLoadingException;
use Wikibase\Lexeme\Merge\Exceptions\LexemeNotFoundException;
use Wikibase\Lexeme\Merge\Exceptions\LexemeSaveFailedException;
use Wikibase\Lexeme\Merge\Exceptions\MergingException;
use Wikibase\Lexeme\Merge\Exceptions\PermissionDeniedException;
use Wikibase\Lexeme\Merge\Exceptions\ReferenceSameLexemeException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\EntityContent;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Summary;
use Wikibase\Lib\FormatableSummary;
use Wikibase\SummaryFormatter;

/**
 * @license GPL-2.0-or-later
 */
class LexemeMergeInteractor {

	/**
	 * @var EntityRevisionLookup
	 */
	private $entityRevisionLookup;

	/**
	 * @var EntityStore
	 */
	private $entityStore;

	/**
	 * @var EntityPermissionChecker
	 */
	private $permissionChecker;

	/**
	 * @var SummaryFormatter
	 */
	private $summaryFormatter;

	/**
	 * @var User
	 */
	private $user;

	/**
	 * @var LexemeRedirectCreationInteractor
	 */
	private $redirectInteractor;

	/**
	 * @var EntityTitleStoreLookup
	 */
	private $entityTitleLookup;

	/**
	 * @var LexemeMerger
	 */
	private $lexemeMerger;

	/**
	 * @var WatchedItemStoreInterface
	 */
	private $watchedItemStore;

	public function __construct(
		LexemeMerger $lexemeMerger,
		EntityRevisionLookup $entityRevisionLookup,
		EntityStore $entityStore,
		EntityPermissionChecker $permissionChecker,
		SummaryFormatter $summaryFormatter,
		User $user,
		LexemeRedirectCreationInteractor $redirectInteractor,
		EntityTitleStoreLookup $entityTitleLookup,
		WatchedItemStoreInterface $watchedItemStore
	) {
		$this->lexemeMerger = $lexemeMerger;
		$this->entityRevisionLookup = $entityRevisionLookup;
		$this->entityStore = $entityStore;
		$this->permissionChecker = $permissionChecker;
		$this->summaryFormatter = $summaryFormatter;
		$this->user = $user;
		$this->redirectInteractor = $redirectInteractor;
		$this->entityTitleLookup = $entityTitleLookup;
		$this->watchedItemStore = $watchedItemStore;
	}

	/**
	 * @param LexemeId $sourceId
	 * @param LexemeId $targetId
	 * @param string|null $summary - only relevant when called through the API
	 * @param bool $isBotEdit - only relevant when called through the API
	 *
	 * @return array A list of exactly two EntityRevision objects. The first
	 * EntityRevision object represents the modified source lexeme, the second one represents
	 * the modified target lexeme.
	 *
	 * @throws MergingException
	 */
	public function mergeLexemes(
		LexemeId $sourceId,
		LexemeId $targetId,
		$summary = null,
		$isBotEdit = false
	) {
		$this->checkPermissions( $sourceId );
		$this->checkPermissions( $targetId );

		/**
		 * @var Lexeme $source
		 * @var Lexeme $target
		 */
		$source = $this->loadEntity( $sourceId );
		$target = $this->loadEntity( $targetId );

		$this->validateEntities( $source, $target );

		$this->lexemeMerger->merge( $source, $target );

		$result = $this->attemptSaveMerge( $source, $target, $summary, $isBotEdit );
		$this->updateWatchlistEntries( $sourceId, $targetId );

		$this->redirectInteractor->createRedirect( $sourceId, $targetId, $isBotEdit );

		return $result;
	}

	/**
	 * Check user's permissions for the given entity ID.
	 *
	 * @param EntityId $entityId
	 *
	 * @throws MergingException if the permission check fails
	 */
	private function checkPermissions( EntityId $entityId ) {
		$status = $this->permissionChecker->getPermissionStatusForEntityId(
			$this->user,
			EntityPermissionChecker::ACTION_MERGE,
			$entityId
		);

		if ( !$status->isOK() ) {
			throw new PermissionDeniedException();
		}
	}

	/**
	 * Either throws an exception or returns an EntityDocument object.
	 *
	 * @param LexemeId $lexemeId
	 *
	 * @return EntityDocument
	 *
	 * @throws MergingException
	 */
	private function loadEntity( LexemeId $lexemeId ): EntityDocument {
		try {
			$revision = $this->entityRevisionLookup->getEntityRevision(
				$lexemeId,
				0,
				EntityRevisionLookup::LATEST_FROM_MASTER
			);

			if ( !$revision ) {
				throw new LexemeNotFoundException();
			}

			return $revision->getEntity();
		} catch ( StorageException $ex ) {
			throw new LexemeLoadingException();
		} catch ( RevisionedUnresolvedRedirectException $ex ) {
			throw new LexemeLoadingException();
		}
	}

	/**
	 * @param EntityDocument $fromEntity
	 * @param EntityDocument $toEntity
	 *
	 * @throws ReferenceSameLexemeException
	 */
	private function validateEntities( EntityDocument $fromEntity, EntityDocument $toEntity ) {
		if ( $toEntity->getId()->equals( $fromEntity->getId() ) ) {
			throw new ReferenceSameLexemeException();
		}
	}

	/**
	 * @param string $direction either 'from' or 'to'
	 * @param LexemeId $id
	 * @param string|null $customSummary
	 *
	 * @return Summary
	 */
	private function getSummary( $direction, LexemeId $id, $customSummary = null ) {
		$summary = new Summary( 'wblmergelexemes', $direction, null, [ $id->getSerialization() ] );
		$summary->setUserSummary( $customSummary );

		return $summary;
	}

	/**
	 * @param Lexeme $source
	 * @param Lexeme $target
	 * @param string|null $summary
	 * @param bool $bot
	 *
	 * @return array A list of exactly two EntityRevision objects. The first one represents the
	 *  modified source lexeme, the second one represents the modified target lexeme.
	 */
	private function attemptSaveMerge( Lexeme $source, Lexeme $target, $summary, $bot ) {
		$toSummary = $this->getSummary( 'to', $target->getId(), $summary );
		$fromRev = $this->saveLexeme( $source, $toSummary, $bot );

		$fromSummary = $this->getSummary( 'from', $source->getId(), $summary );
		$toRev = $this->saveLexeme( $target, $fromSummary, $bot );

		return [ $fromRev, $toRev ];
	}

	private function saveLexeme( Lexeme $lexeme, FormatableSummary $summary, $bot ): EntityRevision {
		$flags = EDIT_UPDATE | EntityContent::EDIT_IGNORE_CONSTRAINTS;
		if ( $bot && $this->user->isAllowed( 'bot' ) ) {
			$flags |= EDIT_FORCE_BOT;
		}

		try {
			return $this->entityStore->saveEntity(
				$lexeme,
				$this->summaryFormatter->formatSummary( $summary ),
				$this->user,
				$flags
			);
		} catch ( StorageException $ex ) {
			throw new LexemeSaveFailedException();
		}
	}

	private function updateWatchlistEntries( LexemeId $fromId, LexemeId $toId ) {
		$fromTitle = $this->entityTitleLookup->getTitleForId( $fromId );
		$toTitle = $this->entityTitleLookup->getTitleForId( $toId );

		$this->watchedItemStore->duplicateAllAssociatedEntries( $fromTitle, $toTitle );
	}

}
