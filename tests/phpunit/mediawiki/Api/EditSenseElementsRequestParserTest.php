<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Api;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\DispatchingEntityIdParser;
use Wikibase\Lexeme\MediaWiki\Api\EditSenseElementsRequest;
use Wikibase\Lexeme\MediaWiki\Api\EditSenseElementsRequestParser;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\EditSenseChangeOpDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\SenseIdDeserializer;
use Wikibase\Lexeme\Domain\Model\SenseId;
use Wikibase\Repo\ChangeOp\ChangeOps;

/**
 * @covers \Wikibase\Lexeme\MediaWiki\Api\EditSenseElementsRequestParser
 *
 * @license GPL-2.0-or-later
 */
class EditSenseElementsRequestParserTest extends TestCase {

	const DEFAULT_GLOSS = 'furry animal';
	const DEFAULT_GLOSS_LANGUAGE = 'en';
	const DEFAULT_SENSE_ID = 'L1-S1';

	public function testSenseIdAndDataGetPassedToRequestObject() {
		$editSenseChangeOpDeserializer = $this
			->getMockBuilder( EditSenseChangeOpDeserializer::class )
			->disableOriginalConstructor()
			->getMock();
		$editSenseChangeOpDeserializer
			->method( 'createEntityChangeOp' )
			->with( $this->getDataParams() )
			->willReturn( new ChangeOps() );

		$parser = new EditSenseElementsRequestParser(
			$this->newSenseIdDeserializer(),
			$editSenseChangeOpDeserializer
		);

		$request = $parser->parse( [
			'senseId' => self::DEFAULT_SENSE_ID,
			'data' => $this->getDataAsJson()
		] );

		$this->assertInstanceOf( EditSenseElementsRequest::class, $request );
		$this->assertSame( $request->getSenseId()->serialize(), self::DEFAULT_SENSE_ID );
	}

	private function getDataParams( array $dataToUse = [] ) {
		$simpleData = [
			'glosses' => [
				self::DEFAULT_GLOSS_LANGUAGE => [
					'language' => self::DEFAULT_GLOSS_LANGUAGE,
					'value' => self::DEFAULT_GLOSS,
				]
			],
		];

		return array_merge( $simpleData, $dataToUse );
	}

	private function getDataAsJson( array $dataToUse = [] ) {
		return json_encode( $this->getDataParams( $dataToUse ) );
	}

	private function newSenseIdDeserializer() {
		$idParser = new DispatchingEntityIdParser( [
			SenseId::PATTERN => function ( $id ) {
				return new SenseId( $id );
			}
		] );
		return new SenseIdDeserializer( $idParser );
	}

}
