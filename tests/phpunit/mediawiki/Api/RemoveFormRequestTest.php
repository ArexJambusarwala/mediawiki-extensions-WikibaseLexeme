<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Api;

use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\MediaWiki\Api\RemoveFormRequest;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpRemoveForm;
use Wikibase\Lexeme\Domain\Model\FormId;

/**
 * @covers \Wikibase\Lexeme\MediaWiki\Api\RemoveFormRequest
 *
 * @license GPL-2.0-or-later
 */
class RemoveFormRequestTest extends TestCase {

	public function testReturnsCorrectChangeOp() {
		$request = new RemoveFormRequest( new FormId( 'L1-F1' ) );

		$changeOp = $request->getChangeOp();
		$this->assertTrue( $changeOp instanceof ChangeOpRemoveForm );
	}

	public function testGetLexemeId() {
		$request = new RemoveFormRequest( new FormId( 'L1-F1' ) );

		$this->assertSame( 'L1-F1', $request->getFormId()->getSerialization() );
	}

}
