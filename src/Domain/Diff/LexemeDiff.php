<?php

namespace Wikibase\Lexeme\Domain\Diff;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOp;
use Wikibase\DataModel\Services\Diff\EntityDiff;

/**
 * Represents a diff between two lexemes.
 *
 * @since 1.0
 *
 * @license GPL-2.0-or-later
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class LexemeDiff extends EntityDiff {

	/**
	 * @param DiffOp[] $operations
	 */
	public function __construct( array $operations = [] ) {
		//TODO Probably can be removed. Does it do anything useful?
		$this->fixSubstructureDiff( $operations, 'lemmas' );
		$this->fixSubstructureDiff( $operations, 'lexicalCategory' );
		$this->fixSubstructureDiff( $operations, 'language' );
		$this->fixSubstructureDiff( $operations, 'forms' );
		$this->fixSubstructureDiff( $operations, 'senses' );
		$this->fixSubstructureDiff( $operations, 'nextFormId' );
		$this->fixSubstructureDiff( $operations, 'nextSenseId' );

		parent::__construct( $operations );
	}

	/**
	 * Returns a Diff object with the lemma differences.
	 *
	 * @return Diff
	 */
	public function getLemmasDiff() {
		return isset( $this['lemmas'] ) ? $this['lemmas'] : new Diff( [], true );
	}

	/**
	 * Returns a Diff object with the lexical category differences.
	 *
	 * @return Diff
	 */
	public function getLexicalCategoryDiff() {
		return isset( $this['lexicalCategory'] ) ? $this['lexicalCategory'] : new Diff( [], true );
	}

	/**
	 * Returns a Diff object with the language differences.
	 *
	 * @return Diff
	 */
	public function getLanguageDiff() {
		return isset( $this['language'] ) ? $this['language'] : new Diff( [], true );
	}

	/**
	 * @return Diff
	 */
	public function getFormsDiff() {
		return isset( $this['forms'] ) ? $this['forms'] : new Diff( [], true );
	}

	/**
	 * @return Diff
	 */
	public function getSensesDiff() {
		return isset( $this['senses'] ) ? $this['senses'] : new Diff( [], true );
	}

	/**
	 * Returns if there are any changes (equivalent to: any differences between the entities).
	 *
	 * @return bool
	 */
	public function isEmpty() {
		//FIXME: Needs to be fixed, otherwise conflict resolution may lead to unexpected results
		return $this->getLemmasDiff()->isEmpty()
			&& $this->getLexicalCategoryDiff()->isEmpty()
			&& $this->getLanguageDiff()->isEmpty()
			&& $this->getClaimsDiff()->isEmpty()
			&& $this->getFormsDiff()->isEmpty()
			&& $this->getSensesDiff()->isEmpty()
			&& $this->getNextFormIdDiff()->isEmpty()
			&& $this->getNextSenseIdDiff()->isEmpty();
	}

	public function getNextFormIdDiff() {
		return isset( $this['nextFormId'] ) ? $this['nextFormId'] : new Diff( [], true );
	}

	public function getNextSenseIdDiff() {
		return isset( $this['nextSenseId'] ) ? $this['nextSenseId'] : new Diff( [], true );
	}

	public function toArray( $valueConverter = null ) {
		throw new \LogicException( 'toArray() is not implemented' );
	}

}
