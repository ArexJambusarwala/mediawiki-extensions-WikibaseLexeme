<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Hooks\Formatters;

use Language;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Lexeme\Tests\DataModel\NewLexeme;
use Wikibase\Lexeme\Tests\MediaWiki\WikibaseLexemeIntegrationTestCase;
use Wikibase\Repo\Hooks\Formatters\EntityLinkFormatter;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Lexeme\Hooks\Formatters\LexemeLinkFormatter
 *
 * @license GPL-2.0-or-later
 */
class LexemeLinkFormatterIntegrationTest extends WikibaseLexemeIntegrationTestCase {

	public function testLexemeLinkFormatter() {
		$lemma = 'potato';
		$lexemeId = 'L123';
		$lemmaLanguage = 'en';
		$lexeme = NewLexeme::havingId( $lexemeId )
			->withLemma( $lemmaLanguage, $lemma )
			->build();
		$this->saveEntity( $lexeme );

		$this->assertThatHamcrest(
			$this->getLinkFormatter( $lexeme->getType() )->getHtml( $lexeme->getId() ),
			is( htmlPiece( both( havingChild( allOf(
				withTagName( 'span' ),
				withAttribute( 'lang' )->havingValue( $lemmaLanguage ),
				withAttribute( 'dir' )->havingValue( 'ltr' ),
				havingTextContents( $lemma )
			) ) )->andAlso(
				havingChild( havingTextContents( containsString( $lexemeId ) ) )
			) ) )
		);
	}

	private function getLinkFormatter( $entityType ): EntityLinkFormatter {
		$factory = WikibaseRepo::getDefaultInstance()
			->getEntityLinkFormatterFactory( Language::factory( 'en' ) );

		return $factory->getLinkFormatter( $entityType );
	}

	private function saveEntity( EntityDocument $entity ) {
		$this->getEntityStore()->saveEntity(
			$entity,
			static::class,
			$this->getTestUser()->getUser()
		);
	}

}
