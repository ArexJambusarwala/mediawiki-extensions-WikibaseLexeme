<?php


namespace Wikibase\Lexeme\Tests\MediaWiki\View;

use RawMessage;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Presentation\View\LexemeMetaTagsCreator;
use Wikibase\View\Tests\EntityMetaTagsCreatorTestCase;

/**
 * @license GPL-2.0-or-later
 * @covers \Wikibase\Lexeme\Presentation\View\LexemeMetaTagsCreator
 */
class LexemeMetaTagsCreatorTest extends EntityMetaTagsCreatorTestCase {

	public function provideTestGetMetaTags() {
		$lexemeMetaTags = new LexemeMetaTagsCreator( '/' );

		return [
			[
				$lexemeMetaTags,
				new Lexeme( new LexemeId( 'L84384' ) ),
				[ 'title' => 'L84384' ]
			],
			[
				$lexemeMetaTags,
				new Lexeme(
					new LexemeId( 'L84389' ),
					new TermList( [ new Term( 'en', 'goat' ), new Term( 'fr', 'taog' ) ] ) ),
				[ 'title' => 'goat/taog' ]
			]
		];
	}

	/**
	 * @dataProvider nonStringProvider
	 * @expectedException \InvalidArgumentException
	 */
	public function testGivenNotAString_constructorThrowsException( $input ) {
		new LexemeMetaTagsCreator( $input );
	}

	public function nonStringProvider() {
		yield [ false ];
		yield [ 123 ];
		yield [ new RawMessage( 'potato' ) ];
	}

}
