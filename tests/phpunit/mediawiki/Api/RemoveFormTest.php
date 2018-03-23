<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Api;

use MediaWiki\MediaWikiServices;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Lexeme\Tests\DataModel\NewLexeme;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Repo\Tests\Api\WikibaseApiTestCase;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Lexeme\Api\RemoveForm
 *
 * @license GPL-2.0-or-later
 *
 * @group Database
 * @group medium
 */
class RemoveFormTest extends WikibaseApiTestCase {

	/**
	 * @dataProvider provideInvalidParams
	 */
	public function testGivenInvalidParameter_errorIsReturned(
		array $params,
		array $expectedError
	) {
		$this->setContentLang( 'qqq' );
		$params = array_merge(
			[ 'action' => 'wblremoveform' ],
			$params
		);

		try {
			$this->doApiRequestWithToken( $params );
			$this->fail( 'No API error was raised' );
		} catch ( \ApiUsageException $e ) {
			/** @var \ApiMessage $message */
			$message = $e->getMessageObject();

			$this->assertInstanceOf( \ApiMessage::class, $message );
			$this->assertEquals( $expectedError['message-key'], $message->getKey(), 'Wrong message codes' );
			$this->assertEquals(
				$expectedError['message-parameters'],
				$message->getParams(),
				'Wrong message parameters'
			);
			$this->assertEquals(
				$expectedError['api-error-code'],
				$message->getApiCode(),
				'Wrong api code'
			);
			$this->assertEquals(
				$expectedError['api-error-data'],
				$message->getApiData(),
				'Wrong api data'
			);
		}
	}

	public function provideInvalidParams() {
		return [
			'no formId param' => [
				[],
				[
					'message-key' => 'apierror-missingparam',
					'message-parameters' => [ 'formId' ],
					'api-error-code' => 'noformId',
					'api-error-data' => []
				],
			],
			'invalid formId (random string not ID)' => [
				[ 'formId' => 'foo' ],
				[
					'message-key' => 'wikibaselexeme-api-error-parameter-not-form-id',
					'message-parameters' => [ 'formId', 'foo' ],
					'api-error-code' => 'bad-request',
					'api-error-data' => []
				]
			],
			'Lexeme is not found' => [
				[ 'formId' => 'L999-F1' ],
				[
					'message-key' => 'wikibaselexeme-api-error-lexeme-not-found',
					'message-parameters' => [ 'L999' ],
					'api-error-code' => 'not-found',
					'api-error-data' => []
				],
			],
		];
	}

	public function testGivenValidData_removesForm() {
		$lexeme = NewLexeme::havingId( 'L1' )->build();
		$form = $lexeme->addForm( new TermList( [ new Term( 'fr', 'goat' ) ] ), [] );
		$this->saveLexeme( $lexeme );

		$this->doApiRequestWithToken( [
			'action' => 'wblremoveform',
			'formId' => $form->getId()->getSerialization(),
		] );

		$this->assertSame( 0, $this->getLexeme( 'L1' )->getForms()->count() );
	}

	public function testGivenValidData_responseContainsSuccessMarker() {
		$lexeme = NewLexeme::havingId( 'L1' )->build();
		$form = $lexeme->addForm( new TermList( [ new Term( 'fr', 'goat' ) ] ), [] );
		$this->saveLexeme( $lexeme );

		list( $result, ) = $this->doApiRequestWithToken( [
			'action' => 'wblremoveform',
			'formId' => $form->getId()->getSerialization(),
		] );

		$this->assertSame( 1, $result['success'] );
	}

	public function testSetsTheSummaryOfRevision() {
		$lexeme = NewLexeme::havingId( 'L1' )->build();
		$form = $lexeme->addForm( new TermList( [ new Term( 'fr', 'goat' ) ] ), [] );
		$this->saveLexeme( $lexeme );

		$this->doApiRequestWithToken( [
			'action' => 'wblremoveform',
			'formId' => $form->getId()->getSerialization(),
		] );

		$lexemeRevision = $this->getCurrentRevisionForLexeme( 'L1' );

		$revision = MediaWikiServices::getInstance()->getRevisionStore()->getRevisionById(
			$lexemeRevision->getRevisionId()
		);

		$this->assertEquals(
			'/* remove-form:1||' . $form->getId()->getSerialization() . ' */ goat',
			$revision->getComment()->text
		);
	}

	public function testResponseContainsRevisionId() {
		$lexeme = NewLexeme::havingId( 'L1' )->build();
		$form = $lexeme->addForm( new TermList( [ new Term( 'fr', 'goat' ) ] ), [] );
		$this->saveLexeme( $lexeme );

		list( $result, ) = $this->doApiRequestWithToken( [
			'action' => 'wblremoveform',
			'formId' => $form->getId()->getSerialization(),
		] );

		$lexemeRevision = $this->getCurrentRevisionForLexeme( 'L1' );
		$this->assertEquals( $lexemeRevision->getRevisionId(), $result['lastrevid'] );
	}

	private function saveLexeme( Lexeme $lexeme ) {
		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();

		$store->saveEntity( $lexeme, self::class, $this->getMock( \User::class ) );
	}

	/**
	 * @param string $id
	 *
	 * @return Lexeme|null
	 */
	private function getLexeme( $id ) {
		$lookup = WikibaseRepo::getDefaultInstance()->getEntityLookup();
		return $lookup->getEntity( new LexemeId( $id ) );
	}

	/**
	 * @param string $id
	 *
	 * @return EntityRevision|null
	 */
	private function getCurrentRevisionForLexeme( $id ) {
		$lookup = WikibaseRepo::getDefaultInstance()->getEntityRevisionLookup();

		return $lookup->getEntityRevision( new LexemeId( $id ) );
	}

}