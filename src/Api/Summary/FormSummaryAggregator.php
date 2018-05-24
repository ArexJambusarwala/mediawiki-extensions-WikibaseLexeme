<?php

namespace Wikibase\Lexeme\Api\Summary;

use Wikibase\Summary;

/**
 * @license GPL-2.0-or-later
 */
class FormSummaryAggregator {

	/**
	 * @var string
	 */
	private $aggregateAction;

	/**
	 * @param string $aggregateAction The name to use to describe the aggregation action in Summary
	 */
	public function __construct( $aggregateAction ) {
		$this->aggregateAction = $aggregateAction;
	}

	/**
	 * Change the $summary to reflect the aggregation result of $summary and $subSummary
	 *
	 * Helps if you have a reference to an existing object
	 * http://php.net/manual/en/language.oop5.references.php
	 *
	 * @param Summary $summary
	 * @param Summary $subSummary
	 */
	public function overrideSummary( Summary $summary, Summary $subSummary ) {
		$aggregateSummary = $this->aggregate( $summary, $subSummary );

		$summary->setAction( $aggregateSummary->getMessageKey() );
		// only atomic summaries of representation changes reveal language, need to be bubbled
		$summary->setLanguage( $aggregateSummary->getLanguageCode() );
		$summary->setAutoSummaryArgs( $aggregateSummary->getAutoSummaryArgs() );
		$summary->setAutoCommentArgs( $aggregateSummary->getCommentArgs() );
	}

	/**
	 * Get a Summary that contains the aggregation result of $summary and $subSummary
	 *
	 * @param Summary $summary
	 * @param Summary $subSummary
	 *
	 * @return Summary
	 */
	public function aggregate( Summary $summary, Summary $subSummary ) {
		if ( $this->hasNothingToMerge( $subSummary ) ) {
			return $summary;
		}

		if ( $this->isAlreadyAggregate( $summary ) ) {
			return $summary;
		}

		if ( $this->hasNoExistingSummary( $summary ) ) {
			return $subSummary;
		}

		if ( $this->haveDifferentActions( $summary, $subSummary ) ) {
			return $this->createDifferentActionAggregation( $summary, $subSummary );
		}

		return $this->createSameActionAggregation( $summary, $subSummary );
	}

	private function hasNothingToMerge( Summary $summary ) {
		return $summary->getMessageKey() === null;
	}

	private function isAlreadyAggregate( Summary $summary ) {
		return $summary->getMessageKey() === $this->aggregateAction;
	}

	private function hasNoExistingSummary( Summary $summary ) {
		return $summary->getMessageKey() === null;
	}

	private function haveDifferentActions( Summary $summary, Summary $subSummary ) {
		return $summary->getMessageKey() !== $subSummary->getMessageKey();
	}

	private function createDifferentActionAggregation( Summary $summary, Summary $subSummary ) {
		$newSummary = new Summary();
		$newSummary->setAction( $this->aggregateAction );
		$newSummary->setLanguage( null );
		$newSummary->setAutoCommentArgs(
			array_unique(
				array_merge(
					$summary->getCommentArgs(),
					$subSummary->getCommentArgs()
				)
			)
		);
		// Summary::addAutoSummaryArgs in Form context contains atomic info not to be part of aggregate
		return $newSummary;
	}

	private function createSameActionAggregation( Summary $summary, Summary $subSummary ) {
		$newSummary = new Summary();
		$newSummary->setAction( $summary->getMessageKey() );
		// only atomic summaries of representation changes reveal language, need to be bubbled
		$newSummary->setLanguage( null );
		$newSummary->setAutoSummaryArgs(
			array_merge(
				$summary->getAutoSummaryArgs(),
				$subSummary->getAutoSummaryArgs()
			)
		);
		$newSummary->setAutoCommentArgs(
			array_unique(
				array_merge(
					$summary->getCommentArgs(),
					$subSummary->getCommentArgs()
				)
			)
		);
		return $newSummary;
	}

}