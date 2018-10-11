<?php

namespace Wikibase\Lexeme\Presentation\View;

use Language;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\LanguageFallbackChain;
use Wikibase\Lexeme\Presentation\Formatters\LexemeTermFormatter;
use Wikibase\Lexeme\Presentation\View\Template\LexemeTemplateFactory;
use Wikibase\Repo\MediaWikiLanguageDirectionalityLookup;
use Wikibase\Repo\MediaWikiLocalizedTextProvider;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\View\EditSectionGenerator;
use Wikibase\View\EntityIdFormatterFactory;
use Wikibase\View\Template\TemplateFactory;

/**
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class LexemeViewFactory {

	/**
	 * @var string
	 */
	private $languageCode;

	/**
	 * @var LabelDescriptionLookup
	 */
	private $labelDescriptionLookup;

	/**
	 * @var LanguageFallbackChain
	 */
	private $fallbackChain;

	/**
	 * @var EditSectionGenerator
	 */
	private $editSectionGenerator;

	/**
	 * @var EntityIdFormatterFactory
	 */
	private $entityIdFormatterFactory;

	/**
	 * @param string $languageCode
	 * @param LabelDescriptionLookup $labelDescriptionLookup
	 * @param LanguageFallbackChain $fallbackChain
	 * @param EditSectionGenerator $editSectionGenerator
	 * @param EntityIdFormatterFactory $entityIdFormatterFactory
	 */
	public function __construct(
		$languageCode,
		LabelDescriptionLookup $labelDescriptionLookup,
		LanguageFallbackChain $fallbackChain,
		EditSectionGenerator $editSectionGenerator,
		EntityIdFormatterFactory $entityIdFormatterFactory
	) {
		$this->languageCode = $languageCode;
		$this->labelDescriptionLookup = $labelDescriptionLookup;
		$this->fallbackChain = $fallbackChain;
		$this->editSectionGenerator = $editSectionGenerator;
		$this->entityIdFormatterFactory = $entityIdFormatterFactory;
	}

	public function newLexemeView() {
		$templates = include __DIR__ . '/../../../resources/templates.php';
		$templateFactory = new LexemeTemplateFactory( $templates );

		$languageDirectionalityLookup = new MediaWikiLanguageDirectionalityLookup();
		$localizedTextProvider = new MediaWikiLocalizedTextProvider( $this->languageCode );

		$language = Language::factory( $this->languageCode );

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		// TODO: $this->labelDescriptionLookup is an EntityInfo based lookup that only knows
		// entities processed via EntityParserOutputDataUpdater first, which processes statements
		// and sitelinks only and does not know about Lexeme-specific concepts like lexical category
		// and language.
		$retrievingLabelDescriptionLookup = $wikibaseRepo
			->getLanguageFallbackLabelDescriptionLookupFactory()
			->newLabelDescriptionLookup( $language );

		$statementSectionsView = $wikibaseRepo->getViewFactory()->newStatementSectionsView(
			$this->languageCode,
			$this->labelDescriptionLookup,
			$this->fallbackChain,
			$this->editSectionGenerator
		);

		$statementGroupListView = $wikibaseRepo->getViewFactory()->newStatementGroupListView(
			$this->languageCode,
			$retrievingLabelDescriptionLookup,
			$this->fallbackChain,
			$this->editSectionGenerator
		);

		$idLinkFormatter = $this->entityIdFormatterFactory->getEntityIdFormatter( $language );

		$formsView = new FormsView(
			$localizedTextProvider,
			$templateFactory,
			$idLinkFormatter,
			$statementGroupListView
		);

		$sensesView = new SensesView(
			$localizedTextProvider,
			$languageDirectionalityLookup,
			$templateFactory,
			$statementGroupListView,
			$this->languageCode
		);

		return new LexemeView(
			TemplateFactory::getDefaultInstance(),
			$languageDirectionalityLookup,
			$this->languageCode,
			$formsView,
			$sensesView,
			$statementSectionsView,
			new LexemeTermFormatter(
				$localizedTextProvider
					->get( 'wikibaselexeme-presentation-lexeme-display-label-separator-multiple-lemma' )
			),
			$idLinkFormatter
		);
	}

}