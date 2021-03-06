<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Api;

use Wikibase\Lexeme\Tests\DataModel\NewForm;
use Wikibase\Lexeme\Tests\DataModel\NewLexeme;
use Wikibase\Lexeme\Tests\MediaWiki\WikibaseLexemeApiTestCase;

/**
 * @covers \Wikibase\Repo\Api\SearchEntities
 *
 * @license GPL-2.0-or-later
 *
 * @group API
 * @group WikibaseAPI
 * @group Database
 * @group medium
 */
class SearchEntitiesIntegrationTest extends WikibaseLexemeApiTestCase {

	const LEXEME_ID = 'L100';
	const FORM_ID = 'F1';
	const FULL_FORM_ID = 'L100-F1';

	public function testLexemeIsFoundWhenIdGivenAsSearchTerm() {
		$this->saveLexeme();

		$params = [
			'action' => 'wbsearchentities',
			'search' => self::LEXEME_ID,
			'type' => 'lexeme',
			'language' => 'en',
		];

		list( $result, ) = $this->doApiRequest( $params );

		$this->assertCount( 1, $result['search'] );
		$this->assertSame( self::LEXEME_ID, $result['search'][0]['id'] );
		$this->assertSame(
			[ 'type' => 'entityId', 'text' => self::LEXEME_ID ],
			$result['search'][0]['match']
		);
	}

	public function testGivenIdOfNonExistingLexeme_noResults() {
		$this->saveLexeme();

		$params = [
			'action' => 'wbsearchentities',
			'search' => 'L21323232',
			'type' => 'lexeme',
			'language' => 'en',
		];

		list( $result, ) = $this->doApiRequest( $params );

		$this->assertEmpty( $result['search'] );
	}

	public function testFormIsFoundWhenIdGivenAsSearchTerm() {
		$this->saveLexeme();

		$params = [
			'action' => 'wbsearchentities',
			'search' => self::FULL_FORM_ID,
			'type' => 'form',
			'language' => 'en',
		];

		list( $result, ) = $this->doApiRequest( $params );

		$this->assertCount( 1, $result['search'] );
		$this->assertSame( self::FULL_FORM_ID, $result['search'][0]['id'] );
		$this->assertSame(
			[ 'type' => 'entityId', 'text' => self::FULL_FORM_ID ],
			$result['search'][0]['match']
		);
	}

	public function testGivenIdOfNonExistingForm_noSearchResults() {
		$this->saveLexeme();

		$params = [
			'action' => 'wbsearchentities',
			'search' => self::LEXEME_ID . '-F200',
			'type' => 'form',
			'language' => 'en',
		];

		list( $result, ) = $this->doApiRequest( $params );

		$this->assertEmpty( $result['search'] );
	}

	public function testGivenIdOfNonExistingLexeme_noFormSearchResults() {
		$this->saveLexeme();

		$params = [
			'action' => 'wbsearchentities',
			'search' => 'L21323232-F1',
			'type' => 'form',
			'language' => 'en',
		];

		list( $result, ) = $this->doApiRequest( $params );

		$this->assertEmpty( $result['search'] );
	}

	private function saveLexeme() {
		$this->saveEntity( $this->getDummyLexeme() );
	}

	private function getDummyLexeme() {
		return NewLexeme::havingId( self::LEXEME_ID )
			->withForm(
				NewForm::havingLexeme( self::LEXEME_ID )->andId( self::FORM_ID )->build()
			)
			->build();
	}

}
