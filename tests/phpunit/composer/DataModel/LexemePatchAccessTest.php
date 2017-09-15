<?php

namespace Wikibase\Lexeme\Tests\DataModel;

use Wikibase\Lexeme\DataModel\FormSet;
use Wikibase\Lexeme\DataModel\LexemePatchAccess;

class LexemePatchAccessTest extends \PHPUnit_Framework_TestCase {

	public function testCanAddAForm() {
		$forms = new FormSet( [] );
		$lexemePatchAccess = new LexemePatchAccess( 1, $forms );
		$form = NewForm::any()->build();

		$lexemePatchAccess->addForm( $form );

		$this->assertEquals( [ $form ], $lexemePatchAccess->getForms() );
	}

	public function testCanNotAddAFormIfPatchAccessIsClosed() {
		$forms = new FormSet( [] );
		$lexemePatchAccess = new LexemePatchAccess( 1,  $forms );
		$form = NewForm::any()->build();
		$lexemePatchAccess->close();

		$this->setExpectedException( \Exception::class );
		$lexemePatchAccess->addForm( $form );
	}

	public function testDoesNotModifyTheOriginalFormSet() {
		$initialFormList = [];
		$forms = new FormSet( $initialFormList );
		$lexemePatchAccess = new LexemePatchAccess( 1, $forms );
		$form = NewForm::any()->build();

		$lexemePatchAccess->addForm( $form );

		$this->assertEquals( $initialFormList, $forms->toArray() );
	}

	public function testCanNotCreateWithNextFromIdWhichIsNotAPositiveInteger() {
		$this->setExpectedException( \Exception::class );
		new LexemePatchAccess( 0, new FormSet( [] ) );
	}

	public function testIncreaseNextFormIdTo_GivenLexemWithGreaterId_Increases() {
		$lexemePatchAccess = new LexemePatchAccess( 1, new FormSet( [] ) );

		$lexemePatchAccess->increaseNextFormIdTo( 2 );

		$this->assertEquals( 2, $lexemePatchAccess->getNextFormId() );
	}

	public function testIncreaseNextFormIdTo_GivenLexemeSmallerInitialId_ThrowsAnException() {
		$lexemePatchAccess = new LexemePatchAccess( 2, new FormSet( [] ) );

		$this->setExpectedException( \Exception::class );
		$lexemePatchAccess->increaseNextFormIdTo( 1 );
	}

	public function testIncreaseNextFormIdTo_GivenNonInteger_ThrowsAnException() {
		$lexemePatchAccess = new LexemePatchAccess( 1, new FormSet( [] ) );

		$this->setExpectedException( \Exception::class );
		$lexemePatchAccess->increaseNextFormIdTo( 2.0 );
	}

}