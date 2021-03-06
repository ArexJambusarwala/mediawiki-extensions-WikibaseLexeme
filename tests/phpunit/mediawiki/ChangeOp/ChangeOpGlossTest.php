<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ChangeOp;

use PHPUnit4And6Compat;
use ValueValidators\Result;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpGloss;
use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\Tests\DataModel\NewSense;
use Wikibase\Lexeme\Tests\DataModel\NewLexeme;
use Wikibase\Summary;

/**
 * @covers \Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpGloss
 *
 * @license GPL-2.0-or-later
 */
class ChangeOpGlossTest extends TestCase {
	use PHPUnit4And6Compat;

	public function testAction_isEdit() {
		$changeOp = new ChangeOpGloss( new Term( 'en', 'furry animal' ) );
		$this->assertSame( [ 'edit' ], $changeOp->getActions() );
	}

	/**
	 * @expectedException \Wikimedia\Assert\ParameterTypeException
	 * @expectedExceptionMessage Bad value for parameter $entity
	 */
	public function testValidateNonSense_yieldsAssertionProblem() {
		$changeOp = new ChangeOpGloss( new Term( 'en', 'furry animal' ) );
		$changeOp->validate( NewLexeme::create()->build() );
	}

	public function testValidateAnySense_yieldsSuccess() {
		$changeOp = new ChangeOpGloss( new Term( 'en', 'furry animal' ) );
		$result = $changeOp->validate( NewSense::havingId( 'S1' )->build() );

		$this->assertInstanceOf( Result::class, $result );
		$this->assertTrue( $result->isValid() );
	}

	/**
	 * @expectedException \Wikimedia\Assert\ParameterTypeException
	 * @expectedExceptionMessage Bad value for parameter $entity
	 */
	public function testApplyNonSense_yieldsAssertionProblem() {
		$changeOp = new ChangeOpGloss( new Term( 'en', 'furry animal' ) );
		$changeOp->apply( NewLexeme::create()->build() );
	}

	public function testApply_addsGlossInNewLanguage() {
		$sense = NewSense::havingId( 'S3' )
			->withGloss( 'de', 'pelziges Tier' )
			->build();
		$summary = new Summary();

		$changeOp = new ChangeOpGloss( new Term( 'en', 'furry animal' ) );
		$changeOp->apply( $sense, $summary );

		$this->assertCount( 2, $sense->getGlosses() );
		$this->assertSame( 'add-sense-glosses', $summary->getMessageKey() );
		$this->assertSame( 'en', $summary->getLanguageCode() );
		$this->assertSame( [ 'L1-S3' ], $summary->getCommentArgs() );
		$this->assertSame( [ 'en' => 'furry animal' ], $summary->getAutoSummaryArgs() );
	}

	public function testApply_replacesGlossInPreexistingLanguage() {
		$sense = NewSense::havingId( 'S3' )
			->withGloss( 'de', 'pelziges Tier' )
			->build();
		$summary = new Summary();

		$changeOp = new ChangeOpGloss( new Term( 'de', 'Tier mit Pelz' ) );
		$changeOp->apply( $sense, $summary );

		$this->assertCount( 1, $sense->getGlosses() );
		$this->assertSame( 'set-sense-glosses', $summary->getMessageKey() );
		$this->assertSame( 'de', $summary->getLanguageCode() );
		$this->assertSame( [ 'L1-S3' ], $summary->getCommentArgs() );
		$this->assertSame( [ 'de' => 'Tier mit Pelz' ], $summary->getAutoSummaryArgs() );
	}

	public function testApply_noSummaryForSameTermInPreexistingLanguage() {
		$sense = NewSense::havingId( 'S3' )
			->withGloss( 'de', 'pelziges Tier' )
			->build();
		$summary = new Summary();

		$changeOp = new ChangeOpGloss( new Term( 'de', 'pelziges Tier' ) );
		$changeOp->apply( $sense, $summary );

		$this->assertCount( 1, $sense->getGlosses() );
		$this->assertNull( $summary->getMessageKey() );
		$this->assertNull( $summary->getLanguageCode() );
		$this->assertSame( [], $summary->getCommentArgs() );
		$this->assertSame( [], $summary->getAutoSummaryArgs() );
	}

}
