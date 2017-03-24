<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\View;

use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\LanguageFallbackChain;
use Wikibase\Lexeme\View\LexemeView;
use Wikibase\Lexeme\View\LexemeViewFactory;
use Wikibase\View\EditSectionGenerator;
use Wikibase\View\EntityTermsView;

/**
 * @covers Wikibase\Lexeme\View\LexemeViewFactory
 *
 * @group WikibaseLexeme
 *
 * @license GPL-2.0+
 * @author Thiemo Mättig
 */
class LexemeViewFactoryTest extends PHPUnit_Framework_TestCase {

	public function testNewLexemeView() {
		$factory = new LexemeViewFactory(
			'en',
			$this->getMock( LabelDescriptionLookup::class ),
			new LanguageFallbackChain( [] ),
			$this->getMock( EditSectionGenerator::class ),
			$this->getMock( EntityTermsView::class )
		);
		$view = $factory->newLexemeView();
		$this->assertInstanceOf( LexemeView::class, $view );
	}

}
