<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ChangeOp;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpLemmaRemove;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Summary;

/**
 * @covers \Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpLemmaRemove
 *
 * @license GPL-2.0-or-later
 */
class ChangeOpLemmaRemoveTest extends TestCase {

	use PHPUnit4And6Compat;

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testGivenInvalidArguments_constructorThrowsException() {
		new ChangeOpLemmaRemove( null );
	}

	public function testGivenALemmasProvider_validateReturnsSuccess() {
		$changeOp = new ChangeOpLemmaRemove( 'en' );

		$result = $changeOp->validate( new Lexeme() );

		$this->assertInstanceOf( Result::class, $result );
		$this->assertTrue( $result->isValid() );
	}

	/**
	 * @dataProvider invalidEntityProvider
	 * @expectedException InvalidArgumentException
	 */
	public function testGivenNotALemmasProvider_validateThrowsException( EntityDocument $entity ) {
		$changeOp = new ChangeOpLemmaRemove( 'en' );
		$changeOp->validate( $entity );
	}

	/**
	 * @dataProvider invalidEntityProvider
	 * @expectedException InvalidArgumentException
	 */
	public function testGivenNotALemmasProvider_applyThrowsException( EntityDocument $entity ) {
		$changeOp = new ChangeOpLemmaRemove( 'en' );
		$changeOp->apply( $entity );
	}

	public function invalidEntityProvider() {
		return [
			[ $this->getMock( EntityDocument::class ) ],
			[ new Item( new ItemId( 'Q123' ) ) ],
		];
	}

	public function testGivenRemovalLanguageAndMatchingLemmaTerm_applyRemovesLemmaAndSetsTheSummary() {
		$lemmas = new TermList( [
			new Term( 'de', 'Ente' ),
			new Term( 'en', 'duck' ),
		] );
		$lexeme = new Lexeme( null, $lemmas );
		$summary = new Summary();

		$changeOp = new ChangeOpLemmaRemove( 'de' );
		$changeOp->apply( $lexeme, $summary );

		$this->assertFalse( $lexeme->getLemmas()->hasTermForLanguage( 'de' ) );
		$this->assertTrue( $lexeme->getLemmas()->hasTermForLanguage( 'en' ) );
		$this->assertSame( 'duck', $lexeme->getLemmas()->getByLanguage( 'en' )->getText() );

		$this->assertSame( 'remove', $summary->getMessageKey() );
		$this->assertSame( 'de', $summary->getLanguageCode() );
		$this->assertSame( [ 'Ente' ], $summary->getAutoSummaryArgs() );
	}

	public function testGivenRemovalLanguageAndNoMatchingLemmaTerm_applyMakesNoChange() {
		$lexeme = new Lexeme();
		$summary = new Summary();

		$changeOp = new ChangeOpLemmaRemove( 'de' );
		$changeOp->apply( $lexeme, $summary );

		$this->assertFalse( $lexeme->getLemmas()->hasTermForLanguage( 'de' ) );
		$this->assertNull( $summary->getMessageKey() );
	}

}
