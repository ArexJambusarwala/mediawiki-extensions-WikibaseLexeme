<?php

namespace Wikibase\Lexeme\Api;

use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Api\Error\ApiError;
use Wikibase\Lexeme\Api\Error\FormMustHaveAtLeastOneRepresentation;
use Wikibase\Lexeme\Api\Error\JsonFieldHasWrongType;
use Wikibase\Lexeme\Api\Error\JsonFieldIsNotAnItemId;
use Wikibase\Lexeme\Api\Error\JsonFieldIsRequired;
use Wikibase\Lexeme\Api\Error\ParameterIsNotAJsonObject;
use Wikibase\Lexeme\Api\Error\ParameterIsNotLexemeId;
use Wikibase\Lexeme\Api\Error\ParameterIsRequired;
use Wikibase\Lexeme\Api\Error\RepresentationLanguageCanNotBeEmpty;
use Wikibase\Lexeme\Api\Error\RepresentationLanguageInconsistent;
use Wikibase\Lexeme\Api\Error\RepresentationsMustHaveUniqueLanguage;
use Wikibase\Lexeme\Api\Error\RepresentationTextCanNotBeEmpty;
use Wikibase\Lexeme\DataModel\LexemeId;

/**
 * @license GPL-2.0-or-later
 */
class AddFormRequestParser {

	const PARAM_DATA = 'data';
	const PARAM_LEXEME_ID = 'lexemeId';
	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	/**
	 * @var ItemIdParser
	 */
	private $itemIdParser;

	public function __construct( EntityIdParser $entityIdParser ) {
		$this->entityIdParser = $entityIdParser;
		$this->itemIdParser = new ItemIdParser();
	}

	/**
	 * @param array $params
	 * @return AddFormRequestParserResult
	 */
	public function parse( array $params ) {
		//TODO: validate language. How?
		//TODO: validate if all grammatical features exist
		$errors = $this->validateRequiredFieldsPresent( $params );
		if ( $errors ) {
			return AddFormRequestParserResult::newWithErrors( $errors );
		}

		$data = json_decode( $params[self::PARAM_DATA], true );
		if ( !is_array( $data ) || empty( $data ) ) {
			return AddFormRequestParserResult::newWithErrors(
				[
					new ParameterIsNotAJsonObject( self::PARAM_DATA, $params[self::PARAM_DATA] )
				]
			);
		}

		$errors = $this->validateDataStructure( $data );
		if ( $errors ) {
			return AddFormRequestParserResult::newWithErrors( $errors );
		}

		$lexemeId = $this->parseLexemeId( $params[self::PARAM_LEXEME_ID], $errors );
		$representations = $this->parseRepresentations( $data['representations'], $errors );
		$grammaticalFeatures = $this->parseGrammaticalFeatures( $data['grammaticalFeatures'], $errors );

		if ( $errors ) {
			return AddFormRequestParserResult::newWithErrors( $errors );
		}

		return AddFormRequestParserResult::newWithRequest(
			new AddFormRequest( $lexemeId, $representations, $grammaticalFeatures )
		);
	}

	private function validateDataStructure( array $data ) {
		$errors = [];

		if ( !array_key_exists( 'representations', $data ) ) {
			$errors[] = new JsonFieldIsRequired( self::PARAM_DATA, [ 'representations' ] );
		} elseif ( !is_array( $data['representations'] ) ) {
			$errors[] = new JsonFieldHasWrongType(
				self::PARAM_DATA,
				[ 'representations' ],
				'array', // TODO What would be a sane expected type (plain array is odd)
				gettype( $data['representations'] )
			);
		}

		if ( !array_key_exists( 'grammaticalFeatures', $data ) ) {
			$errors[] = new JsonFieldIsRequired( self::PARAM_DATA, [ 'grammaticalFeatures' ] );
		} elseif ( !is_array( $data['grammaticalFeatures' ] ) ) {
			$errors[] = new JsonFieldHasWrongType(
				self::PARAM_DATA,
				[ 'grammaticalFeatures' ],
				'array',
				gettype( $data['grammaticalFeatures'] )
			);
		}

		return $errors;
	}

	/**
	 * @param string $id
	 * @return LexemeId|null
	 */
	private function parseLexemeId( $id, array &$errors ) {
		try {
			$lexemeId = $this->entityIdParser->parse( $id );
		} catch ( EntityIdParsingException $e ) {
			$errors[] = new ParameterIsNotLexemeId( self::PARAM_LEXEME_ID, $id );
			return null;
		}

		if ( $lexemeId->getEntityType() !== 'lexeme' ) {
			$errors[] = new ParameterIsNotLexemeId( self::PARAM_LEXEME_ID, $id );
			return null;
		}

		return $lexemeId;
	}

	/**
	 * @param array[] $givenRepresentations
	 * @param ApiError[] $errors
	 * @return TermList
	 */
	private function parseRepresentations( array $givenRepresentations, array &$errors ) {
		if ( empty( $givenRepresentations ) ) {
			$errors[] = new FormMustHaveAtLeastOneRepresentation( self::PARAM_DATA, [ 'representations' ] );
		}

		//FIXME: Array may contain representation with empty text (or untrimmed) which won't be added
		$result = [];

		foreach ( $givenRepresentations as $index => $el ) {
			$incomplete = false;

			if ( !array_key_exists( 'value', $el ) ) {
				$errors[] = new JsonFieldIsRequired(
					self::PARAM_DATA,
					[ 'representations', $index, 'value' ]
				);
				$incomplete = true;
			} elseif ( empty( $el['value'] ) ) {
				$errors[] = new RepresentationTextCanNotBeEmpty(
					self::PARAM_DATA,
					[ 'representations', $index, 'value' ]
				);
				$incomplete = true;
			}

			$validLanguage = $this->validateRepresentationLanguage( $index, $el, $errors );
			if ( !$validLanguage ) {
				$incomplete = true;
			}

			if ( $incomplete ) {
				continue;
			}

			if ( isset( $result[$el['language']] ) ) {
				$errors[] = new RepresentationsMustHaveUniqueLanguage(
					self::PARAM_DATA,
					[ 'representations', $index, 'language' ],
					$el['language']
				);
			}

			$result[$el['language']] = $el['value'];
		}

		$terms = [];
		foreach ( $result as $language => $representation ) {
			$terms[] = new Term( $language, $representation );
		}

		return new TermList( $terms );
	}

	/**
	 * @param string[] $data
	 * @param ApiError[] $errors
	 * @return ItemId[]
	 */
	private function parseGrammaticalFeatures( array $data, array &$errors ) {
		$features = [];

		foreach ( $data as $index => $featureId ) {
			try {
				$id = $this->itemIdParser->parse( $featureId );
			} catch ( EntityIdParsingException $e ) {
				$errors[] = new JsonFieldIsNotAnItemId(
					self::PARAM_DATA,
					[ 'grammaticalFeatures', $index ],
					$featureId
				);
				continue;
			}

			$features[] = $id;
		}

		return $features;
	}

	private function validateRequiredFieldsPresent( array $params ) {
		$errors = [];

		if ( !array_key_exists( self::PARAM_LEXEME_ID, $params ) ) {
			$errors[] = new ParameterIsRequired( self::PARAM_LEXEME_ID );
		}

		if ( !array_key_exists( self::PARAM_DATA, $params ) ) {
			$errors[] = new ParameterIsRequired( self::PARAM_DATA );
		}

		return $errors;
	}

	/**
	 * @param $index
	 * @param $representation
	 * @param array $errors
	 * @return bool
	 */
	private function validateRepresentationLanguage( $index, $representation, array &$errors ) {
		if ( !array_key_exists( 'language', $representation ) ) {
			$errors[] = new JsonFieldIsRequired(
				self::PARAM_DATA,
				[ 'representations', $index, 'language' ]
			);
			return false;
		}
		if ( empty( $index ) || empty( $representation['language'] ) ) {
			$errors[] = new RepresentationLanguageCanNotBeEmpty(
				self::PARAM_DATA,
				[ 'representations', $index ?: $representation['language'], 'language' ]
			);
			return false;
		}
		if ( $representation['language'] !== $index ) {
			$errors[] = new RepresentationLanguageInconsistent(
				self::PARAM_DATA,
				[ 'representations', $index, 'language' ],
				$index,
				$representation['language']
			);
			return false;
		}

		return true;
	}

}
