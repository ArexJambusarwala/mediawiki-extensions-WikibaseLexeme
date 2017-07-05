<?php

namespace Wikibase\Lexeme\View;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lexeme\DataModel\Form;
use Wikibase\Lexeme\View\Template\LexemeTemplateFactory;
use Wikibase\Lib\EntityIdHtmlLinkFormatter;
use Wikibase\View\LocalizedTextProvider;
use Wikibase\View\StatementSectionsView;
use WMDE\VueJsTemplating\Templating;

/**
 * @license GPL-2.0+
 * @author Thiemo Mättig
 */
class LexemeFormsView {

	/**
	 * @var LocalizedTextProvider
	 */
	private $textProvider;

	/**
	 * @var LexemeTemplateFactory
	 */
	private $templateFactory;

	/**
	 * @var EntityIdHtmlLinkFormatter
	 */
	private $entityIdHtmlFormatter;

	/**
	 * @var StatementSectionsView
	 */
	private $statementSectionView;

	/**
	 * @var string
	 */
	private $languageCode;

	public function __construct(
		LocalizedTextProvider $textProvider,
		LexemeTemplateFactory $templateFactory,
		EntityIdHtmlLinkFormatter $entityIdHtmlFormatter,
		StatementSectionsView $statementSectionView
	) {
		$this->textProvider = $textProvider;
		$this->templateFactory = $templateFactory;
		$this->entityIdHtmlFormatter = $entityIdHtmlFormatter;
		$this->statementSectionView = $statementSectionView;
	}

	/**
	 * @param Form[] $forms
	 *
	 * @return string HTML
	 */
	public function getHtml( array $forms ) {
		$html = '<div class="wikibase-lexeme-forms-section">';
		$html .= '<h2 class="wb-section-heading section-heading">'
			. '<span class="mw-headline" id="forms">'
			. htmlspecialchars( $this->textProvider->get( 'wikibase-lexeme-view-forms' ) )
			. '</span>'
			. '</h2>';

		$html .= '<div class="wikibase-lexeme-forms ">';
		foreach ( $forms as $form ) {
			$html .= $this->getFormHtml( $form );
		}
		$html .= '</div>';
		$html .= '</div>';
		$html .= $this->getRepresentationsVueTemplate();

		return $html;
	}

	/**
	 * @param Form $form
	 *
	 * @return string HTML
	 */
	private function getFormHtml( Form $form ) {
		$grammaticalFeaturesHtml = $this->templateFactory->render(
			'wikibase-lexeme-form-grammatical-features',
			[ implode(
				$this->textProvider->get( 'comma-separator' ),
				array_map(
					function ( ItemId $id ) {
						return $this->getGrammaticalFeatureHtml( $id );
					},
					$form->getGrammaticalFeatures()
				)
			) ]
		);

		return $this->templateFactory->render( 'wikibase-lexeme-form', [
			htmlspecialchars( $form->getId()->getSerialization() ),
			$this->renderRepresentationWidget( $form ),
			$grammaticalFeaturesHtml,
			$this->statementSectionView->getHtml( $form->getStatements() )
		] );
	}

	/**
	 * @return string
	 */
	private function renderRepresentationWidget( Form $form ) {
		$templating = new Templating();

		$representations = array_map(
			function ( Term $r ) {
				return [ 'value' => $r->getText(), 'language' => $r->getLanguageCode() ];
			},
			iterator_to_array( $form->getRepresentations() )
		);

		$result = $templating->render(
			$this->getRawRepresentationVueTemplate(),
			[
				'inEditMode' => false,
				'representations' => $representations
			],
			[
				'message' => function ( $key ) {
					return $this->textProvider->get( $key );
				}
			]
		);

		return '<div class="form-representations">'
			. $result
			. '</div>';
	}

	/**
	 * @param ItemId $id
	 * @return string
	 */
	private function getGrammaticalFeatureHtml( ItemId $id ) {
		return $this->entityIdHtmlFormatter->formatEntityId( $id );
	}

	private function getRepresentationsVueTemplate() {
		return <<<HTML
<script id="representation-widget-vue-template" type="x-template">
	{$this->getRawRepresentationVueTemplate()}
</script>
HTML;
	}

	private function getRawRepresentationVueTemplate() {
		return <<<'HTML'
<div class="representation-widget">
	<ul v-if="!inEditMode" class="representation-widget_representation-list">
		<li v-for="representation in representations" class="representation-widget_representation">
			<span class="representation-widget_representation-value">{{representation.value}}</span>
			<span class="representation-widget_representation-language">
				{{representation.language}}
			</span>
		</li>
	</ul>
	<div v-else>
		<div class="representation-widget_edit-area">
			<ul class="representation-widget_representation-list">
				<li v-for="representation in representations" 
					class="representation-widget_representation-edit-box">
					<input size="1" class="representation-widget_representation-value-input" 
						v-model="representation.value">
					<input size="1" class="representation-widget_representation-language-input" 
						v-model="representation.language">
					<button class="representation-widget_representation-remove" 
						v-on:click="remove(representation)" 
						:title="'wikibase-remove'|message">
						&times;
					</button>
				</li>
				<li>
					<button type="button" class="representation-widget_add" v-on:click="add" 
						:title="'wikibase-add'|message">+</button>
				</li>
			</ul>
		</div>
	</div>
</div>
HTML;
	}

}
