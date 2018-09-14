<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Hooks\Formatters;

use Language;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Lexeme\DataModel\FormId;
use Wikibase\Lexeme\Tests\DataModel\NewForm;
use Wikibase\Lexeme\Tests\DataModel\NewLexeme;
use Wikibase\Lexeme\Tests\MediaWiki\WikibaseLexemeIntegrationTestCase;
use Wikibase\Repo\Hooks\Formatters\EntityLinkFormatter;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Lexeme\Hooks\Formatters\FormLinkFormatter
 *
 * @license GPL-2.0-or-later
 */
class FormLinkFormatterIntegrationTest extends WikibaseLexemeIntegrationTestCase {

	public function testFormLinkFormatter() {
		$representation = 'Kartoffel';
		$formId = new FormId( 'L321-F1' );
		$representationLanguage = 'de';
		$lexeme = NewLexeme::havingId( 'L321' )
			->withForm( NewForm::havingId( $formId )
				->andRepresentation( $representationLanguage, $representation ) )
			->build();
		$this->saveEntity( $lexeme );

		$this->assertThatHamcrest(
			$this->getLinkFormatter( $formId->getEntityType() )->getHtml( $formId ),
			is( htmlPiece( both( havingChild( allOf(
				withTagName( 'span' ),
				withAttribute( 'lang' )->havingValue( $representationLanguage ),
				withAttribute( 'dir' )->havingValue( 'ltr' ),
				havingTextContents( $representation )
			) ) )->andAlso(
				havingChild( havingTextContents( containsString( $formId->getSerialization() ) ) )
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
