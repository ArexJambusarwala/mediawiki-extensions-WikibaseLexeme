<?php

namespace Wikibase\Lexeme\Tests\DummyObjects;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Model\Sense;
use Wikibase\Lexeme\Domain\Model\SenseId;
use Wikibase\Lexeme\Domain\DummyObjects\BlankSense;
use Wikibase\Lexeme\Domain\DummyObjects\DummySenseId;
use Wikibase\Lexeme\Domain\DummyObjects\NullSenseId;
use Wikibase\Lexeme\Tests\DataModel\NewLexeme;

/**
 * @covers \Wikibase\Lexeme\Domain\DummyObjects\BlankSense
 *
 * @license GPL-2.0-or-later
 */
class BlankSenseTest extends TestCase {

	public function testGetIdWithoutConnectedLexeme_yieldsNullSenseId() {
		$blankSense = new BlankSense();
		$this->assertInstanceOf( NullSenseId::class, $blankSense->getId() );
	}

	public function testGetIdWithConnectedLexeme_yieldsDummySenseId() {
		$lexemeId = new LexemeId( 'L7' );
		$blankSense = new BlankSense();
		$blankSense->setLexeme( NewLexeme::havingId( $lexemeId )->build() );

		$id = $blankSense->getId();
		$this->assertInstanceOf( DummySenseId::class, $id );
		$this->assertSame( $lexemeId, $id->getLexemeId() );
	}

	public function testGetIdAfterGetRealSense_yieldsRealSenseId() {
		$blankSense = new BlankSense();
		$senseId = new SenseId( 'L1-S4' );

		$blankSense->getRealSense( $senseId );

		$this->assertSame( $senseId, $blankSense->getId() );
	}

	/**
	 * @expectedException \Wikimedia\Assert\ParameterAssertionException
	 * @expectedExceptionMessage Sense must have at least one gloss
	 */
	public function testGetRealSenseOnIncompleteData_throwsSenseConstructionExceptions() {
		$this->markTestSkipped( 'Sense constructor does not yet verify this' ); // TODO
		$blankSense = new BlankSense();
		$blankSense->getRealSense( new SenseId( 'L1-S4' ) );
	}

	public function testGetRealSenseOnMinimalData_yieldsSenseWithData() {
		$glossList = new TermList( [ new Term( 'de', 'Tier' ) ] );

		$blankSense = new BlankSense();
		$blankSense->setGlosses( $glossList );

		$sense = $blankSense->getRealSense( new SenseId( 'L1-S4' ) );

		$this->assertInstanceOf( Sense::class, $sense );
		$this->assertSame( $glossList, $sense->getGlosses() );
		$this->assertEquals( new StatementList(), $sense->getStatements() );
	}

}
