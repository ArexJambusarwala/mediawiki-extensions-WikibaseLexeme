<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ChangeOp;

use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use ValueValidators\Result;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpFormEdit;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpRemoveFormRepresentation;
use Wikibase\Lexeme\Domain\Model\Form;
use Wikibase\Lexeme\Domain\Model\FormId;
use Wikibase\Lexeme\Tests\DataModel\NewForm;
use Wikibase\Lexeme\Tests\DataModel\NewLexeme;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Summary;

/**
 * @covers \Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpFormEdit
 *
 * @license GPL-2.0-or-later
 */
class ChangeOpFormEditTest extends TestCase {

	use PHPUnit4And6Compat;

	public function testAction_isEdit() {
		$changeOp = new ChangeOpFormEdit( [] );
		$this->assertSame( [ 'edit' ], $changeOp->getActions() );
	}

	/**
	 * @expectedException \Wikimedia\Assert\ParameterTypeException
	 * @expectedExceptionMessage Bad value for parameter $entity
	 */
	public function testValidateNonForm_yieldsAssertionProblem() {
		$changeOp = new ChangeOpFormEdit( [] );
		$changeOp->validate( NewLexeme::create()->build() );
	}

	public function testValidateAnyForm_yieldsSuccess() {
		$changeOp = new ChangeOpFormEdit( [] );
		$result = $changeOp->validate( NewForm::any()->build() );

		$this->assertInstanceOf( Result::class, $result );
		$this->assertTrue( $result->isValid() );
	}

	/**
	 * @expectedException \Wikimedia\Assert\ParameterTypeException
	 * @expectedExceptionMessage Bad value for parameter $entity
	 */
	public function testApplyNonForm_yieldsAssertionProblem() {
		$changeOp = new ChangeOpFormEdit( [] );
		$changeOp->apply( NewLexeme::create()->build() );
	}

	public function testApply_propagatesApplyToChangeOps() {
		$form = NewForm::any()->build();

		$op1 = $this->getMock( ChangeOp::class );
		$op1->expects( $this->once() )
			->method( 'apply' )
			->with( $form, new Summary() );
		$op2 = $this->getMock( ChangeOp::class );
		$op2->expects( $this->once() )
			->method( 'apply' )
			->with( $form, new Summary() );

		$changeOp = new ChangeOpFormEdit( [ $op1, $op2 ] );
		$changeOp->apply( $form );
	}

	public function testApply_doesNothingOnEmptyChangeOps() {
		$form = NewForm::any()->build();
		$formClone = clone $form;

		$changeOp = new ChangeOpFormEdit( [] );
		$changeOp->apply( $form );

		$this->assertTrue( $form->equals( $formClone ) );
	}

	public function testApplySameAction_atomicActionInSummary() {
		$form = NewForm::any()->build();
		$summary = new Summary();

		$op1 = $this->getMockBuilder( ChangeOp::class )
			->disableArgumentCloning()
			->getMock();
		$op1->expects( $this->once() )
			->method( 'apply' )
			->willReturnCallback( function( Form $a, Summary $b ) use ( $form ) {
				$this->assertSame( $form, $a );

				$b->setAction( 'specific-action' );
				$b->setLanguage( 'en' );
				$b->setAutoCommentArgs( [ 'f' ] );
				$b->setAutoSummaryArgs( [ 'ff' ] );
			} );
		$op2 = $this->getMockBuilder( ChangeOp::class )
			->disableArgumentCloning()
			->getMock();
		$op2->expects( $this->once() )
			->method( 'apply' )
			->willReturnCallback( function( Form $a, Summary $b ) use ( $form ) {
				$this->assertSame( $form, $a );

				$b->setAction( 'specific-action' );
				$b->setLanguage( 'en' );
				$b->setAutoCommentArgs( [ 'g' ] );
				$b->setAutoSummaryArgs( [ 'gg' ] );
			} );

		$changeOp = new ChangeOpFormEdit( [ $op1, $op2 ] );
		$changeOp->apply( $form, $summary );

		$this->assertSame( 'specific-action', $summary->getMessageKey() );
		$this->assertNull( $summary->getLanguageCode() );
		$this->assertSame( [ 'f', 'g' ], $summary->getCommentArgs() );
		$this->assertSame( [ 'ff', 'gg' ], $summary->getAutoSummaryArgs() );
	}

	public function testApplyDifferentActions_aggregateActionInSummary() {
		$form = NewForm::any()->build();
		$summary = new Summary();

		$op1 = $this->getMockBuilder( ChangeOp::class )
			->disableArgumentCloning()
			->getMock();
		$op1->expects( $this->once() )
			->method( 'apply' )
			->willReturnCallback( function( Form $a, Summary $b ) use ( $form ) {
				$this->assertSame( $form, $a );

				$b->setAction( 'specific-action' );
				$b->setLanguage( 'en' );
				$b->setAutoCommentArgs( [ 'f' ] );
				$b->setAutoSummaryArgs( [ 'ff' ] );
			} );
		$op2 = $this->getMockBuilder( ChangeOp::class )
			->disableArgumentCloning()
			->getMock();
		$op2->expects( $this->once() )
			->method( 'apply' )
			->willReturnCallback( function( Form $a, Summary $b ) use ( $form ) {
				$this->assertSame( $form, $a );

				$b->setAction( 'other-action' );
				$b->setLanguage( 'en' );
				$b->setAutoCommentArgs( [ 'g' ] );
				$b->setAutoSummaryArgs( [ 'gg' ] );
			} );

		$changeOp = new ChangeOpFormEdit( [ $op1, $op2 ] );
		$changeOp->apply( $form, $summary );

		$this->assertSame( 'update-form-elements', $summary->getMessageKey() );
		$this->assertNull( $summary->getLanguageCode() );
		$this->assertSame( [ 'f', 'g' ], $summary->getCommentArgs() );
		$this->assertSame( [], $summary->getAutoSummaryArgs() );
	}

	/**
	 * @expectedException \Wikibase\Repo\ChangeOp\ChangeOpApplyException
	 * @expectedExceptionMessage apierror-wikibaselexeme-form-must-have-at-least-one-representation
	 */
	public function testApplyRemovingOnlyRepresentations_throwsException() {
		$form = NewForm::havingRepresentation( 'en', 'goat' )
			->andId( new FormId( 'L1-F3' ) )
			->build();

		$changeOp = new ChangeOpFormEdit( [ new ChangeOpRemoveFormRepresentation( 'en' ) ] );
		$changeOp->apply( $form );
	}

	public function testGetChangeOps_yieldsConstructorParameters() {
		$op1 = new ChangeOpRemoveFormRepresentation( 'en' );
		$op2 = new ChangeOpRemoveFormRepresentation( 'de' );
		$changeOp = new ChangeOpFormEdit( [ $op1, $op2 ] );
		$this->assertSame( [ $op1, $op2 ], $changeOp->getChangeOps() );
	}

}
