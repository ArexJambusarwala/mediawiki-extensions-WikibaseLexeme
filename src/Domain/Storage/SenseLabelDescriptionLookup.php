<?php

namespace Wikibase\Lexeme\Domain\Storage;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Term\Term;
use Wikibase\LanguageFallbackChain;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\Sense;
use Wikibase\Lexeme\Domain\Model\SenseId;
use Wikibase\View\LocalizedTextProvider;
use Wikimedia\Assert\Assert;

/**
 * A {@link LabelDescriptionLookup} for {@link Sense}s
 * which returns the lemmas of the sense’s lexeme as the “label”
 * and the gloss as the “description”.
 *
 * @license GPL-2.0-or-later
 */
class SenseLabelDescriptionLookup implements LabelDescriptionLookup {

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @var LanguageFallbackChain
	 */
	private $languageFallbackChain;

	/**
	 * @var LocalizedTextProvider
	 */
	private $localizedTextProvider;

	public function __construct(
		EntityLookup $entityLookup,
		LanguageFallbackChain $languageFallbackChain,
		LocalizedTextProvider $localizedTextProvider
	) {
		$this->entityLookup = $entityLookup;
		$this->languageFallbackChain = $languageFallbackChain;
		$this->localizedTextProvider = $localizedTextProvider;
	}

	/**
	 * @param SenseId $entityId
	 * @return Term|null
	 */
	public function getLabel( EntityId $entityId ) {
		Assert::parameterType( SenseId::class, $entityId, '$entityId' );
		$lexemeId = $entityId->getLexemeId();

		if ( ! $this->entityLookup->hasEntity( $lexemeId ) ) {
			return null;
		}

		/** @var Lexeme $lexeme */
		$lexeme = $this->entityLookup->getEntity( $lexemeId );
		$lemmas = $lexeme->getLemmas()->toTextArray();
		$separator = $this->localizedTextProvider->get(
			'wikibaselexeme-presentation-lexeme-display-label-separator-multiple-lemma'
		);

		// we have to pretend the result is a single term with a single language code :/
		return new Term( key( $lemmas ), implode( $separator, $lemmas ) );
	}

	/**
	 * @param SenseId $entityId
	 * @return Term|null
	 */
	public function getDescription( EntityId $entityId ) {
		Assert::parameterType( SenseId::class, $entityId, '$entityId' );

		if ( ! $this->entityLookup->hasEntity( $entityId ) ) {
			return null;
		}

		/** @var Sense $sense */
		$sense = $this->entityLookup->getEntity( $entityId );
		$glosses = $sense->getGlosses()->toTextArray();

		$value = $this->languageFallbackChain->extractPreferredValue( $glosses );
		if ( $value === null ) {
			return null;
		}
		return new Term( $value['language'], $value['value'] );
	}

}
