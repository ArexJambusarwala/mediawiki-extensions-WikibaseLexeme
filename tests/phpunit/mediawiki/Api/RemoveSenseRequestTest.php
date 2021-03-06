<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Api;

use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\MediaWiki\Api\RemoveSenseRequest;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpRemoveSense;
use Wikibase\Lexeme\Domain\Model\SenseId;

/**
 * @covers \Wikibase\Lexeme\MediaWiki\Api\RemoveSenseRequest
 *
 * @license GPL-2.0-or-later
 */
class RemoveSenseRequestTest extends TestCase {

	public function testReturnsCorrectChangeOp() {
		$request = new RemoveSenseRequest( new SenseId( 'L1-S1' ) );

		$changeOp = $request->getChangeOp();
		$this->assertTrue( $changeOp instanceof ChangeOpRemoveSense );
	}

	public function testGetLexemeId() {
		$request = new RemoveSenseRequest( new SenseId( 'L1-S1' ) );

		$this->assertSame( 'L1-S1', $request->getSenseId()->getSerialization() );
	}

}
