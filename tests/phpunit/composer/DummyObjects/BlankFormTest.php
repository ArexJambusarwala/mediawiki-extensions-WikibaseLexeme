<?php

namespace Wikibase\Lexeme\Tests\DummyObjects;

use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\Domain\Model\FormId;
use Wikibase\Lexeme\Domain\DummyObjects\BlankForm;
use Wikibase\Lexeme\Domain\DummyObjects\DummyFormId;
use Wikibase\Lexeme\Domain\DummyObjects\NullFormId;

/**
 * @covers \Wikibase\Lexeme\Domain\DummyObjects\BlankForm
 *
 * @license GPL-2.0-or-later
 */
class BlankFormTest extends TestCase {

	public function testConstructedWithNullFormId() {
		$this->assertInstanceOf( NullFormId::class, ( new BlankForm() )->getId() );
	}

	public function testSetsDummyIdFromFormId() {
		$blankForm = new BlankForm();
		$formId = new FormId( 'L1-F2' );
		$blankForm->setId( $formId );

		$this->assertInstanceOf( DummyFormId::class, $blankForm->getId() );
		$this->assertSame( $formId->getSerialization(), $blankForm->getId()->getSerialization() );
	}

}
