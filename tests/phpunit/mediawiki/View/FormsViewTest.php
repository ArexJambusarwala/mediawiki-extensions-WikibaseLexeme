<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\View;

use PHPUnit_Framework_TestCase;
use Prophecy\Argument;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\Lexeme\Tests\DataModel\NewForm;
use Wikibase\Lexeme\View\FormsView;
use Wikibase\Lexeme\View\Template\LexemeTemplateFactory;
use Wikibase\Lib\EntityIdHtmlLinkFormatter;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\View\DummyLocalizedTextProvider;
use Wikibase\View\StatementGroupListView;

/**
 * @covers Wikibase\Lexeme\View\FormsView
 *
 * @group WikibaseLexeme
 *
 * @license GPL-2.0+
 * @author Thiemo Mättig
 */
class FormsViewTest extends PHPUnit_Framework_TestCase {

	const STATEMENT_LIST_HTML = '<div class="statement-list"></div>';

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
			is( htmlPiece( havingChild( tagMatchingOutline( self::STATEMENT_LIST_HTML ) ) ) )
		);
	}

	private function newFormsView() {
		$statementSectionView = $this->prophesize( StatementGroupListView::class );
		$statementSectionView->getHtml( Argument::any() )->willReturn( self::STATEMENT_LIST_HTML );

		return new FormsView(
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