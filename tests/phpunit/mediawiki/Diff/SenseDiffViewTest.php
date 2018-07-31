<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Diff;

use Diff\Comparer\ComparableComparer;
use Diff\Differ\OrderedListDiffer;
use Diff\DiffOp\Diff\Diff;
use MessageLocalizer;
use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use RawMessage;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Lexeme\DataModel\Services\Diff\ChangeSenseDiffOp;
use Wikibase\Lexeme\DataModel\Services\Diff\SenseDiffer;
use Wikibase\Lexeme\Diff\SenseDiffView;
use Wikibase\Lexeme\Tests\DataModel\NewSense;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Repo\Diff\ClaimDiffer;
use Wikibase\Repo\Diff\ClaimDifferenceVisualizer;
use Wikibase\Repo\Diff\DifferencesSnakVisualizer;

/**
 * @covers Wikibase\Lexeme\Diff\SenseDiffView
 *
 * @license GPL-2.0-or-later
 */
class SenseDiffViewTest extends TestCase {

	use PHPUnit4And6Compat;

	/**
	 * @return ClaimDiffer
	 */
	private function getMockClaimDiffer() {
		return new ClaimDiffer( new OrderedListDiffer( new ComparableComparer() ) );
	}

	/**
	 * @param string $returnValue
	 *
	 * @return SnakFormatter
	 */
	public function newSnakFormatter( $returnValue = '<i>SNAK</i>' ) {
		$instance = $this->getMock( SnakFormatter::class );
		$instance->expects( $this->any() )
			->method( 'getFormat' )
			->will( $this->returnValue( SnakFormatter::FORMAT_HTML ) );
		$instance->expects( $this->any() )
			->method( 'formatSnak' )
			->will( $this->returnValue( $returnValue ) );
		return $instance;
	}

	/**
	 * @return EntityIdFormatter
	 */
	public function newEntityIdLabelFormatter() {
		$instance = $this->getMock( EntityIdFormatter::class );

		$instance->expects( $this->any() )
			->method( 'formatEntityId' )
			->will( $this->returnValue( '<a>PID</a>' ) );

		return $instance;
	}

	/**
	 * @return ClaimDifferenceVisualizer
	 */
	private function getMockClaimDiffVisualizer() {
		return new ClaimDifferenceVisualizer(
			new DifferencesSnakVisualizer(
				$this->newEntityIdLabelFormatter(),
				$this->newSnakFormatter( '<i>DETAILED SNAK</i>' ),
				$this->newSnakFormatter(),
				'qqx'
			),
			'qqx'
		);
	}

	/**
	 * @return MessageLocalizer
	 */
	private function getMockMessageLocalizer() {
		$mock = $this->getMock( MessageLocalizer::class );

		$mock->method( 'msg' )
			->will( $this->returnCallback( function ( $key ) {
				return new RawMessage( "($key)" );
			} ) );

		return $mock;
	}

	/**
	 * @param ChangeSenseDiffOp $diff
	 *
	 * @return SenseDiffView
	 */
	private function getDiffView( ChangeSenseDiffOp $diff ) {
		return new SenseDiffView(
			[],
			new Diff(
				[ 'sense' => new Diff( [ $diff->getSenseId()->getSerialization() => $diff ], true ) ],
				true
			),
			$this->getMockClaimDiffer(),
			$this->getMockClaimDiffVisualizer(),
			$this->getMockMessageLocalizer()
		);
	}

	public function testDiffChangedGlosses() {
		$differ = new SenseDiffer();
		$sense1 = NewSense::havingId( 'S1' )
			->withGloss( 'en', 'cat' )
			->build();
		$sense2 = NewSense::havingId( 'S1' )
			->withGloss( 'en', 'goat' )
			->build();
		$diff = $differ->diffEntities( $sense1, $sense2 );

		$senseDiffViewHeader = 'sense / L1-S1 / (wikibaselexeme-diffview-gloss) / en';
		$expected = '<tr><td colspan="2" class="diff-lineno">' . $senseDiffViewHeader .
			'</td><td colspan="2" class="diff-lineno">' . $senseDiffViewHeader . '</td></tr>' .
			'<tr><td class="diff-marker">-</td><td class="diff-deletedline"><div>' .
			'<del class="diffchange diffchange-inline">cat</del></div></td><td class="diff-marker">' .
			'+</td><td class="diff-addedline"><div><ins class="diffchange diffchange-inline">' .
			'goat</ins></div></td></tr>';
		$this->assertSame( $expected, $this->getDiffView( $diff )->getHtml() );
	}

	public function testDiffAddedGlosses() {
		$differ = new SenseDiffer();
		$sense1 = NewSense::havingId( 'S1' )
			->withGloss( 'en', 'en-value' )
			->build();
		$sense2 = NewSense::havingId( 'S1' )
			->withGloss( 'en', 'en-value' )
			->withGloss( 'fr', 'fr-value' )
			->build();

		$diff = $differ->diffEntities( $sense1, $sense2 );

		$senseDiffViewHeader = 'sense / L1-S1 / (wikibaselexeme-diffview-gloss) / fr';
		$expected = '<tr><td colspan="2" class="diff-lineno">' . $senseDiffViewHeader .
			'</td><td colspan="2" class="diff-lineno">' . $senseDiffViewHeader . '</td></tr>' .
			'<tr><td colspan="2">&nbsp;</td><td class="diff-marker">+</td><td class="diff-addedline">' .
			'<div><ins class="diffchange diffchange-inline">fr-value</ins></div></td></tr>';
		$this->assertSame( $expected, $this->getDiffView( $diff )->getHtml() );
	}

	public function testDiffChangedStatements() {
		$differ = new SenseDiffer();
		$sense1 = NewSense::havingId( 'S1' )
			->withGloss( 'en', 'en-value' )
			->withStatement( $this->someStatement( 'P1', 'guid1' ) )
			->build();
		$sense2 = NewSense::havingId( 'S1' )
			->withGloss( 'en', 'en-value' )
			->withStatement( $this->someStatement( 'P1', 'guid1' ) )
			->withStatement( $this->someStatement( 'P2', 'guid2' ) )
			->build();

		$diff = $differ->diffEntities( $sense1, $sense2 );

		$expected = '<tr><td colspan="2" class="diff-lineno"></td><td colspan="2" class="diff-lineno">' .
			'sense / L1-S1 / (wikibase-entity-property) / <a>PID</a></td></tr><tr>' .
			'<td colspan="2">&nbsp;</td><td class="diff-marker">+</td><td class="diff-addedline">' .
			'<div><ins class="diffchange diffchange-inline"><span><i>DETAILED SNAK</i></span></ins>' .
			'</div></td></tr><tr><td colspan="2" class="diff-lineno"></td><td colspan="2" ' .
			'class="diff-lineno">sense / L1-S1 / (wikibase-entity-property) / <a>PID</a>' .
			'(colon-separator)<i>SNAK</i> / (wikibase-diffview-rank)</td></tr><tr><td colspan="2">' .
			'&nbsp;</td><td class="diff-marker">+</td><td class="diff-addedline"><div>' .
			'<ins class="diffchange diffchange-inline"><span>(wikibase-diffview-rank-normal)</span>' .
			'</ins></div></td></tr>';
		$this->assertSame( $expected, $this->getDiffView( $diff )->getHtml() );
	}

	/**
	 * @return Statement
	 */
	private function someStatement( $propertyId, $guid ) {
		$statement = new Statement(
			new PropertySomeValueSnak( new PropertyId( $propertyId ) )
		);
		$statement->setGuid( $guid );
		return $statement;
	}

}
