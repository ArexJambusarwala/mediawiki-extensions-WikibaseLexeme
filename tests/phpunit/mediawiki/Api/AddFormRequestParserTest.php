<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Api;

use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use Wikibase\DataModel\Deserializers\TermDeserializer;
use Wikibase\DataModel\Entity\DispatchingEntityIdParser;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lexeme\MediaWiki\Api\AddFormRequest;
use Wikibase\Lexeme\MediaWiki\Api\AddFormRequestParser;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpFormAdd;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpFormEdit;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpGrammaticalFeatures;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpRepresentation;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpRepresentationList;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\EditFormChangeOpDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\ItemIdListDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\RepresentationsChangeOpDeserializer;
use Wikibase\Lexeme\DataAccess\ChangeOp\Validation\LexemeTermLanguageValidator;
use Wikibase\Lexeme\DataAccess\ChangeOp\Validation\LexemeTermSerializationValidator;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Repo\ChangeOp\Deserialization\ClaimsChangeOpDeserializer;
use Wikibase\Repo\Validators\CompositeValidator;

/**
 * @covers \Wikibase\Lexeme\MediaWiki\Api\AddFormRequestParser
 *
 * @license GPL-2.0-or-later
 */
class AddFormRequestParserTest extends TestCase {

	use PHPUnit4And6Compat;

	public function testGivenValidData_parseReturnsRequest() {
		$parser = $this->newAddFormRequestParser();

		$request = $parser->parse( [ 'lexemeId' => 'L1', 'data' => $this->getDataParam() ] );

		$this->assertInstanceOf( AddFormRequest::class, $request );
	}

	public function testLexemeIdPassedToRequestObject() {
		$parser = $this->newAddFormRequestParser();

		$request = $parser->parse( [ 'lexemeId' => 'L1', 'data' => $this->getDataParam() ] );

		$this->assertEquals( new LexemeId( 'L1' ), $request->getLexemeId() );
	}

	public function testFormDataPassedToRequestObject() {
		$parser = $this->newAddFormRequestParser();

		$request = $parser->parse( [ 'lexemeId' => 'L1', 'data' => $this->getDataParam() ] );

		$this->assertEquals(
			new ChangeOpFormAdd(
				new ChangeOpFormEdit( [
					new ChangeOpRepresentationList( [ new ChangeOpRepresentation( new Term( 'en', 'goat' ) ) ] ),
					new ChangeOpGrammaticalFeatures( [ new ItemId( 'Q17' ) ] )
				] )
			),
			$request->getChangeOp()
		);
	}

	private function getDataParam( array $dataToUse = [] ) {
		$simpleData = [
			'representations' => [
				'en' => [
					'language' => 'en',
					'value' => 'goat'
				]
			],
			'grammaticalFeatures' => [ 'Q17' ],
		];

		return json_encode( array_merge( $simpleData, $dataToUse ) );
	}

	private function newAddFormRequestParser() {
		$idParser = new DispatchingEntityIdParser( [
			ItemId::PATTERN => function ( $id ) {
				return new ItemId( $id );
			},
			LexemeId::PATTERN => function ( $id ) {
				return new LexemeId( $id );
			}
		] );

		$editFormChangeOpDeserializer = new EditFormChangeOpDeserializer(
			new RepresentationsChangeOpDeserializer(
				new TermDeserializer(),
				new LexemeTermSerializationValidator(
					new LexemeTermLanguageValidator( new StaticContentLanguages( [ 'en', 'de' ] ) )
				)
			),
			new ItemIdListDeserializer( new ItemIdParser() ),
			$this->createMock( ClaimsChangeOpDeserializer::class ),
			new CompositeValidator( [] )
		);

		return new AddFormRequestParser(
			$idParser,
			$editFormChangeOpDeserializer
		);
	}

}
