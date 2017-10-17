<?php

namespace Wikibase\Lexeme\Tests\ErisGenerators;

use Eris\Generator;
use Eris\Generator\ConstantGenerator;
use Eris\Generator\GeneratedValueSingle;
use Eris\Generator\MapGenerator;
use Eris\Generator\SetGenerator;
use Wikibase\Lexeme\DataModel\Form;
use Wikibase\Lexeme\DataModel\FormId;

/**
 * @license GPL-2.0+
 */
class FormGenerator implements Generator {

	const MAX_FORM_ID = 100;

	/**
	 * @var TermListGenerator
	 */
	private $representationGenerator;

	/**
	 * @var SetGenerator
	 */
	private $grammaticalFeaturesGenerator;

	/**
	 * @var Generator
	 */
	private $formIdGenerator;

	public function __construct( FormId $formId = null ) {
		$this->representationGenerator = new TermListGenerator( 1 );
		$this->grammaticalFeaturesGenerator = new SetGenerator( new ItemIdGenerator( 50 ) );
		if ( $formId ) {
			$this->formIdGenerator = ConstantGenerator::box( $formId );
		} else {
			$this->formIdGenerator = new MapGenerator(
				function ( $number ) {
					return new FormId( 'F' . $number );
				},
				new Generator\ChooseGenerator( 1, self::MAX_FORM_ID )
			);
		}
	}

	public function __invoke( $size, $rand ) {
		$generateRepresentations = $this->representationGenerator;
		$generateGrammaticalFeatures = $this->grammaticalFeaturesGenerator;
		$generateFormId = $this->formIdGenerator;

		$formId = $generateFormId( $size, $rand )->unbox();
		$representations = $generateRepresentations( $size, $rand )->unbox();
		$grammaticalFeatures = $generateGrammaticalFeatures( $size, $rand )->unbox();
		$statementList = null;

		$form = new Form( $formId, $representations, $grammaticalFeatures, $statementList );
		return GeneratedValueSingle::fromJustValue( $form, 'form' );
	}

	public function shrink( GeneratedValueSingle $element ) {
		return $element;
	}

}
