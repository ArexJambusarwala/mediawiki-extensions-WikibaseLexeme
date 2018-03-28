<?php

namespace Wikibase\Lexeme\Tests\Diff;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\Lexeme\Diff\ItemReferenceDifferenceVisualizer;

/**
 * @covers \Wikibase\Lexeme\Diff\ItemReferenceDifferenceVisualizer
 */
class ItemReferenceDifferenceVisualizerTest extends TestCase {

	public function testGivenItemReferenceChanged_oldAndNewItemsAreDisplayed() {
		$visualizer = new ItemReferenceDifferenceVisualizer( $this->getIdFormatter() );

		$diffHtml = $visualizer->visualize(
			'header text',
			new Diff(
				[ 'id' => new DiffOpChange( 'Q2', 'Q3' ) ],
				true
			)
		);

		assertThat( $diffHtml, is( htmlPiece(
			havingChild( allOf(
				withTagName( 'tr' ),
				havingChild(
					both( tagMatchingOutline( '<td class="diff-deletedline"/>' ) )->andAlso(
						havingChild( both( withTagName( 'del' ) )->andAlso( havingTextContents( 'formatted Q2' ) ) )
					)
				),
				havingChild(
					both( tagMatchingOutline( '<td class="diff-addedline"/>' ) )->andAlso(
						havingChild( both( withTagName( 'ins' ) )->andAlso( havingTextContents( 'formatted Q3' ) ) )
					)
				)
			) )
		) ) );
	}

	public function testGivenItemReferenceAdded_newItemIsDisplayedAsAdded() {
		$visualizer = new ItemReferenceDifferenceVisualizer( $this->getIdFormatter() );

		$diffHtml = $visualizer->visualize(
			'header text',
			new Diff(
				[ 'id' => new DiffOpAdd( 'Q2' ) ],
				true
			)
		);

		assertThat( $diffHtml, is( htmlPiece(
			havingChild( allOf(
				withTagName( 'tr' ),
				havingChild(
					both( tagMatchingOutline( '<td class="diff-addedline"/>' ) )->andAlso(
						havingChild( both( withTagName( 'ins' ) )->andAlso( havingTextContents( 'formatted Q2' ) ) )
					)
				)
				) )
		) ) );
	}

	public function testGivenItemReferenceRemoved_oldItemIsDisplayedAsDeleted() {
		$visualizer = new ItemReferenceDifferenceVisualizer( $this->getIdFormatter() );

		$diffHtml = $visualizer->visualize(
			'header text',
			new Diff(
				[ 'id' => new DiffOpRemove( 'Q2' ) ],
				true
			)
		);

		assertThat( $diffHtml, is( htmlPiece(
			havingChild( allOf(
				withTagName( 'tr' ),
				havingChild(
					both( tagMatchingOutline( '<td class="diff-deletedline"/>' ) )->andAlso(
						havingChild( both( withTagName( 'del' ) )->andAlso( havingTextContents( 'formatted Q2' ) ) )
					)
				)
			) )
		) ) );
	}

	public function testGivenItemReferenceAdded_onlyHeaderOfAddedColumnsIsDisplayed() {
		$visualizer = new ItemReferenceDifferenceVisualizer( $this->getIdFormatter() );

		$diffHtml = $visualizer->visualize(
			'header text',
			new Diff(
				[ 'id' => new DiffOpAdd( 'Q2' ) ],
				true
			)
		);

		$this->assertRegExp(
			'/<tr><td[^<>]*><\/td><td[^>]*>header text<\/td><\/tr>/', $diffHtml
		);
	}

	public function testGivenItemReferenceChanged_headerOfBothColumnsIsDisplayed() {
		$visualizer = new ItemReferenceDifferenceVisualizer( $this->getIdFormatter() );

		$diffHtml = $visualizer->visualize(
			'header text',
			new Diff(
				[ 'id' => new DiffOpChange( 'Q2', 'Q3' ) ],
				true
			)
		);

		$this->assertRegExp(
			'/<tr><td[^<>]*>header text<\/td><td[^>]*>header text<\/td><\/tr>/', $diffHtml
		);
	}

	public function testGivenItemReferenceRemoved_onlyHeaderOfDeletedColumnsIsDisplayed() {
		$visualizer = new ItemReferenceDifferenceVisualizer( $this->getIdFormatter() );

		$diffHtml = $visualizer->visualize(
			'header text',
			new Diff(
				[ 'id' => new DiffOpRemove( 'Q2' ) ],
				true
			)
		);

		$this->assertRegExp(
			'/<tr><td[^<>]*>header text<\/td><td[^>]*><\/td><\/tr>/', $diffHtml
		);
	}

	private function getIdFormatter() {
		$formatter = $this->getMock( EntityIdFormatter::class );
		$formatter->method( $this->anything() )
			->willReturnCallback( function ( EntityId $entityId ) {
				$id = $entityId->getSerialization();
				return 'formatted ' . $id;
			} );
		return $formatter;
	}

}
