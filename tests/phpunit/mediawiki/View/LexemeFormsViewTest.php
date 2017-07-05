<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\View;

use PHPUnit_Framework_TestCase;
use Prophecy\Argument;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\Lexeme\DataModel\Form;
use Wikibase\Lexeme\DataModel\FormId;
use Wikibase\Lexeme\Tests\DataModel\NewForm;
use Wikibase\Lexeme\View\LexemeFormsView;
use Wikibase\Lexeme\View\Template\LexemeTemplateFactory;
use Wikibase\Lib\EntityIdHtmlLinkFormatter;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\View\DummyLocalizedTextProvider;
use Wikibase\View\StatementSectionsView;

/**
 * @covers Wikibase\Lexeme\View\LexemeFormsView
 *
 * @group WikibaseLexeme
 *
 * @license GPL-2.0+
 * @author Thiemo Mättig
 */
class LexemeFormsViewTest extends PHPUnit_Framework_TestCase {

	const STATEMENT_SECTION_HTML = '<div class="statement-section"></div>';

	public function testHtmlContainsTheFormsHeadline() {
		$view = $this->newFormsView();
		$html = $view->getHtml( [] );

		assertThat(
			$html,
			is( htmlPiece( havingChild(
				both( withTagName( 'h2' ) )
					->andAlso( havingChild(
						both( withAttribute( 'id' )->havingValue( 'forms' ) )
							->andAlso( havingTextContents( '(wikibase-lexeme-view-forms)' ) )
					) )
			) ) )
		);
	}

	public function testHtmlContainsFormsContainer() {
		$view = $this->newFormsView();
		$html = $view->getHtml( [] );

		assertThat(
			$html,
			is( htmlPiece( havingChild( tagMatchingOutline(
				'<div class="wikibase-lexeme-forms">'
			) ) ) )
		);
	}

	public function testHtmlContainsFormRepresentationWithIdAndLanguage() {
		$view = $this->newFormsView();
		$html = $view->getHtml( [
			NewForm::havingId( 'F1' )
				->andRepresentation( 'en', 'FORM_REPRESENTATION' )
				->build()
		] );

		assertThat(
			$html,
			is( htmlPiece(
				both( havingChild(
					allOf(
						withClass( 'representation-widget_representation-value' ),
						havingTextContents( containsString( 'FORM_REPRESENTATION' ) )
					) ) )
				->andAlso( havingChild(
					allOf(
						withClass( 'representation-widget_representation-language' ),
						havingTextContents( containsString( 'en' ) )
					)
				) ) ) )
		);
	}

	public function testHtmlContainsFormId() {
		$view = $this->newFormsView();
		$html = $view->getHtml( [
			NewForm::havingId( 'F1' )->build()
		] );

		assertThat(
			$html,
			is( htmlPiece(
				havingChild(
					havingTextContents( containsString( 'F1' ) )
			) ) )
		);
	}

	public function testHtmlContainsFormGrammaticalFeatures() {
		$view = $this->newFormsView();

		$html = $view->getHtml( [
			NewForm::havingGrammaticalFeature( 'Q1' )->build()
		] );

		assertThat(
			$html,
			is( htmlPiece( havingChild( havingTextContents( containsString( 'Q1' ) ) ) ) )
		);
	}

	public function testHtmlContainsStatementSection() {
		$view = $this->newFormsView();
		$html = $view->getHtml( [
			NewForm::any()->build()
		] );

		assertThat(
			$html,
			is( htmlPiece( havingChild( tagMatchingOutline( self::STATEMENT_SECTION_HTML ) ) ) )
		);
	}

	private function newFormsView() {
		$statementSectionView = $this->prophesize( StatementSectionsView::class );
		$statementSectionView->getHtml( Argument::any() )->willReturn( self::STATEMENT_SECTION_HTML );

		return new LexemeFormsView(
			new DummyLocalizedTextProvider(),
			new LexemeTemplateFactory( [
				'wikibase-lexeme-form' => '
					<div class="wikibase-lexeme-form">
						<div class="wikibase-lexeme-form-header">
							<div class="wikibase-lexeme-form-id">$1</div>
							<div class="form-representations">$2</div>
						</div>
						$3
						$4
					</div>',
				'wikibase-lexeme-form-grammatical-features' => '<div>$1</div>'
			] ),
			new EntityIdHtmlLinkFormatter(
				$this->getMock( LabelDescriptionLookup::class ),
				$this->getMock( EntityTitleLookup::class ),
				$this->getMock( LanguageNameLookup::class )
			),
			$statementSectionView->reveal()
		);
	}

}
