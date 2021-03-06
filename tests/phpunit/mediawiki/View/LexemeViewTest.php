<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\View;

use HamcrestPHPUnitIntegration;
use InvalidArgumentException;
use PHPUnit4And6Compat;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Presentation\Formatters\LexemeTermFormatter;
use Wikibase\Lexeme\Presentation\View\FormsView;
use Wikibase\Lexeme\Presentation\View\SensesView;
use Wikibase\Lexeme\Presentation\View\LexemeView;
use Wikibase\View\EntityView;
use Wikibase\View\LanguageDirectionalityLookup;
use Wikibase\View\StatementSectionsView;
use Wikibase\View\Template\TemplateFactory;
use Wikimedia\Assert\ParameterTypeException;

/**
 * @covers \Wikibase\Lexeme\Presentation\View\LexemeView
 *
 * @license GPL-2.0-or-later
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 * @author Thiemo Kreuz
 */
class LexemeViewTest extends \MediaWikiTestCase {
	use HamcrestPHPUnitIntegration;
	use PHPUnit4And6Compat;

	/**
	 * @return FormsView
	 */
	private function newFormsViewMock() {
		$view = $this->getMockBuilder( FormsView::class )
			->disableOriginalConstructor()
			->getMock();

		$view->method( 'getHtml' )
			->will( $this->returnValue( "FormsView::getHtml\n" ) );

		return $view;
	}

	/**
	 * @return SensesView
	 */
	private function newSensesViewMock() {
		$view = $this->getMockBuilder( SensesView::class )
			->disableOriginalConstructor()
			->getMock();

		$view->method( 'getHtml' )
			->will( $this->returnValue( "SensesView::getHtml\n" ) );

		return $view;
	}

	/**
	 * @param StatementList|null $expectedStatements
	 *
	 * @return StatementSectionsView
	 */
	private function newStatementSectionsViewMock( StatementList $expectedStatements = null ) {
		$statementSectionsView = $this->getMockBuilder( StatementSectionsView::class )
			->disableOriginalConstructor()
			->getMock();

		$statementSectionsView->expects( $expectedStatements ? $this->once() : $this->never() )
			->method( 'getHtml' )
			->with( $expectedStatements )
			->will( $this->returnValue( "StatementSectionsView::getHtml\n" ) );

		return $statementSectionsView;
	}

	/**
	 * @return LanguageDirectionalityLookup
	 */
	private function newLanguageDirectionalityLookupMock() {
		$languageDirectionalityLookup = $this->getMock( LanguageDirectionalityLookup::class );
		$languageDirectionalityLookup->method( 'getDirectionality' )
			->willReturn( 'auto' );

		return $languageDirectionalityLookup;
	}

	private function newLexemeView( StatementList $expectedStatements = null ) {
		$languageDirectionalityLookup = $this->newLanguageDirectionalityLookupMock();

		$lemmaFormatter = new LexemeTermFormatter( '/' );

		$linkFormatter = $this->createMock( EntityIdFormatter::class );
		$linkFormatter->method( 'formatEntityId' )
			->willReturnCallback( function( EntityId $entityId ) {
				$id = $entityId->getSerialization();
				$label = 'LABEL OF ' . $id;
				return "<a href='foobar/$id'>$label</a>";
			} );

		return new LexemeView(
			TemplateFactory::getDefaultInstance(),
			$languageDirectionalityLookup,
			'en',
			$this->newFormsViewMock(),
			$this->newSensesViewMock(),
			$this->newStatementSectionsViewMock( $expectedStatements ),
			$lemmaFormatter,
			$linkFormatter,
			'wikibase-release-the-goats'
		);
	}

	public function testInstantiate() {
		$view = $this->newLexemeView();
		$this->assertInstanceOf( LexemeView::class, $view );
		$this->assertInstanceOf( EntityView::class, $view );
	}

	public function testGetContent_invalidEntityType() {
		$view = $this->newLexemeView();

		/** @var EntityDocument $entity */
		$entity = $this->getMock( EntityDocument::class );

		$this->setExpectedException( InvalidArgumentException::class );
		$view->getContent( $entity );
	}

	/**
	 * @dataProvider provideTestGetContent
	 */
	public function testGetContent( Lexeme $lexeme ) {
		$view = $this->newLexemeView( $lexeme->getStatements() );

		$html = $view->getContent( $lexeme )->getHtml();
		$this->assertInternalType( 'string', $html );
		$this->assertContains( 'id="wb-lexeme-' . ( $lexeme->getId() ?: 'new' ) . '"', $html );
		$this->assertContains( 'class="wikibase-entityview wb-lexeme"', $html );
		$this->assertContains( 'FormsView::getHtml', $html );
		$this->assertContains( 'SensesView::getHtml', $html );
		$this->assertContains( 'StatementSectionsView::getHtml', $html );
	}

	public function provideTestGetContent() {
		$lexemeId = new LexemeId( 'L1' );
		$lexicalCategory = new ItemId( 'Q32' );
		$language = new ItemId( 'Q11' );
		$statements = new StatementList( [
			new Statement( new PropertyNoValueSnak( new PropertyId( 'P1' ) ) )
		] );

		return [
			[
				new Lexeme( $lexemeId, null, $lexicalCategory, $language ),
			],
			[
				new Lexeme( $lexemeId, null, $lexicalCategory, $language, $statements ),
			],
		];
	}

	public function testGetTitleHtml_invalidEntityType() {
		$view = $this->newLexemeView();

		/** @var EntityDocument $entity */
		$entity = $this->getMock( EntityDocument::class );
		$this->setExpectedException( ParameterTypeException::class );
		$view->getTitleHtml( $entity );
	}

	public function testGetContent_containsHeaderWithLemmasAndTheirLanguages() {
		$lexemeId = new LexemeId( 'L1' );
		$language = new ItemId( 'Q2' );
		$lexicalCategory = new ItemId( 'Q3' );
		$lemmas = new TermList( [ new Term( 'en', 'foo' ), new Term( 'en-GB', 'bar' ) ] );
		$lexeme = new Lexeme( $lexemeId, $lemmas, $lexicalCategory, $language );

		$view = $this->newLexemeView( $lexeme->getStatements() );
		$html = $view->getContent( $lexeme )->getHtml();

		$this->assertContains(
			'v-on:click="save">{{\'wikibase-release-the-goats\'|message}}</button>',
			$html
		);

		$this->assertInternalType( 'string', $html );
		$this->assertThatHamcrest(
			$html,
			is(
				htmlPiece(
					havingChild(
						both( withClass( 'lemma-widget_lemma-list' ) )
							->andAlso( havingChild(
								both(
									tagMatchingOutline( '<span class="lemma-widget_lemma-value" lang="en"/>' )
								)->andAlso(
									havingTextContents( containsString( 'foo' ) )
								)
							) )
							->andAlso( havingChild(
								both(
									tagMatchingOutline( '<span class="lemma-widget_lemma-value" lang="en-GB"/>' )
								)->andAlso(
									havingTextContents( containsString( 'bar' ) )
								)
							) )
					)
				)
			)
		);
	}

	public function testGetContentForLanguage() {
		$lexemeId = new LexemeId( 'L1' );
		$language = new ItemId( 'Q2' );
		$lexicalCategory = new ItemId( 'Q3' );

		$lexeme = new Lexeme( $lexemeId, null, $lexicalCategory, $language );

		$view = $this->newLexemeView( $lexeme->getStatements() );

		$html = $view->getContent( $lexeme )->getHtml();
		$this->assertInternalType( 'string', $html );
		$this->assertThatHamcrest(
			$html,
			is(
				htmlPiece(
					havingChild(
						both( withClass( 'language-lexical-category-widget_language' ) )
							->andAlso( havingChild(
								both(
									tagMatchingOutline( '<a href="foobar/Q2"/>' )
								)->andAlso(
									havingTextContents( containsString( 'LABEL OF Q2' ) )
								)
							) )
					)
				)
			)
		);
		$this->assertContains(
			'<div id="toc"></div>'
			. "StatementSectionsView::getHtml\n"
			. "SensesView::getHtml\n"
			. "FormsView::getHtml\n"
			. '</div>',
			$html
		);
	}

	public function testGetContentForLexicalCategory() {
		$lexemeId = new LexemeId( 'L1' );
		$language = new ItemId( 'Q2' );
		$lexicalCategory = new ItemId( 'Q3' );

		$lexeme = new Lexeme( $lexemeId, null, $lexicalCategory, $language );

		$view = $this->newLexemeView( $lexeme->getStatements() );

		$html = $view->getContent( $lexeme )->getHtml();
		$this->assertInternalType( 'string', $html );
		$this->assertThatHamcrest(
			$html,
			is(
				htmlPiece(
					havingChild(
						both( withClass( 'language-lexical-category-widget_lexical-category' ) )
							->andAlso( havingChild(
								both(
									tagMatchingOutline( '<a href="foobar/Q3"/>' )
								)->andAlso(
									havingTextContents( containsString( 'LABEL OF Q3' ) )
								)
							) )
					)
				)
			)
		);
		$this->assertContains(
			'<div id="toc"></div>'
			. "StatementSectionsView::getHtml\n"
			. "SensesView::getHtml\n"
			. "FormsView::getHtml\n"
			. '</div>',
			$html
		);
	}

}
