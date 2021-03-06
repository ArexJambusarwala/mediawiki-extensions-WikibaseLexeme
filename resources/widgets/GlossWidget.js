module.exports = ( function ( require, Vue, wb ) {
	'use strict';

	var RedundantLanguageIndicator = require( 'wikibase.lexeme.widgets.RedundantLanguageIndicator' ),
		InvalidLanguageIndicator = require( 'wikibase.lexeme.widgets.InvalidLanguageIndicator' ),
		LanguageSelectorWrapper = require( 'wikibase.lexeme.widgets.LanguageSelectorWrapper' );

	function deepClone( object ) {
		return JSON.parse( JSON.stringify( object ) ).sort( function ( a, b ) {
			return a.language > b.language;
		} );
	}

	function applyGlossWidget( widgetElement, glosses, beforeUpdate, mw, getDirectionality ) {
		var template = '#gloss-widget-vue-template';
		var messages = mw.messages;

		return new Vue( newGlossWidget( messages, widgetElement, template, glosses, beforeUpdate, getDirectionality ) );
	}

	/**
	 *
	 * @param {mw.messages} messages
	 * @param {string|HTMLElement} widgetElement
	 * @param {string} template
	 * @param {[{ value: string, language: string }]} glosses
	 * @param {function} beforeUpdate
	 * @param {function} getDirectionality
	 * @return {object}
	 */
	function newGlossWidget( messages, widgetElement, template, glosses, beforeUpdate, getDirectionality ) {
		var invalidLanguageIndicator = InvalidLanguageIndicator( 'glosses' );
		return {
			el: widgetElement,
			template: template,

			mixins: [ RedundantLanguageIndicator( 'glosses' ), invalidLanguageIndicator ],

			components: {
				'language-selector': LanguageSelectorWrapper( new wikibase.WikibaseContentLanguages() )
			},

			beforeUpdate: beforeUpdate,

			data: {
				inEditMode: false,
				glosses: deepClone( glosses )
			},
			methods: {
				add: function () {
					this.glosses.push( { value: '', language: '' } );
				},
				remove: function ( gloss ) {
					var index = this.glosses.indexOf( gloss );
					this.glosses.splice( index, 1 );
				},
				edit: function () {
					invalidLanguageIndicator.methods.getValidLanguagesPromise();
					this.inEditMode = true;
					if ( this.glosses.length === 0 ) {
						this.add();
					}
				},
				stopEditing: function () {
					this.inEditMode = false;
					this.glosses = this.glosses.filter( function ( gloss ) {
						return gloss.value.trim() !== '' && gloss.language.trim() !== '';
					} );
				}
			},
			filters: {
				message: function ( key ) {
					return messages.get( key );
				},
				directionality: function ( languageCode ) {
					return getDirectionality( languageCode );
				},
				languageName: function ( languageCode ) {
					return wb.getLanguageNameByCode( languageCode );
				}
			}
		};
	}

	return {
		applyGlossWidget: applyGlossWidget,
		newGlossWidget: newGlossWidget
	};

} )( require, Vue, wikibase );
