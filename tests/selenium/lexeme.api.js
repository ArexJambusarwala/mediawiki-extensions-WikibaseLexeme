'use strict';

const MWBot = require( 'mwbot' ),
	bot = new MWBot( {
		apiUrl: browser.options.baseUrl + '/api.php'
	} ),
	Util = require( 'wdio-mediawiki/Util' );
let WikibaseApi;
try {
	WikibaseApi = require( '../../../Wikibase/repo/tests/selenium/wikibase.api' );
} catch ( e ) {
	try {
		WikibaseApi = require( 'wdio-wikibase/wikibase.api' );
	} catch ( e2 ) {
		WikibaseApi = require( '../../../Wikibase/repo/tests/selenium/wdio-wikibase/wikibase.api' );
	}
}

class LexemeApi {

	/**
	 * Create a lexeme
	 *
	 * @param {object} lexeme Optional lexeme definition to merge into default definition
	 * @return {Promise}
	 */
	create( lexeme ) {
		lexeme = Object.assign( {
			lemmas: {
				en: {
					value: Util.getTestString(),
					language: 'en'
				}
			},
			lexicalCategory: null, // if null a new lexicalCategory is created and used for the lexeme
			language: null // if null a new language is created and used for the lexeme
		}, lexeme );

		return bot.getEditToken()
			.then( () => {
				return new Promise( ( resolve, reject ) => {
					if ( lexeme.lexicalCategory !== null ) { // optionally skip creation
						resolve();
					}

					WikibaseApi.createItem()
						.then( ( itemId ) => {
							lexeme.lexicalCategory = itemId;

							resolve();
						} );
				} );
			} ).then( () => {
				return new Promise( ( resolve, reject ) => {
					if ( lexeme.language !== null ) { // optionally skip creation
						resolve();
					}

					WikibaseApi.createItem()
						.then( ( itemId ) => {
							lexeme.language = itemId;

							resolve();
						} );
				} );
			} ).then( () => {
				return new Promise( ( resolve, reject ) => {
					bot.request( {
						action: 'wbeditentity',
						'new': 'lexeme',
						data: JSON.stringify( lexeme ),
						token: bot.editToken
					} ).then( ( payload ) => {
						resolve( payload.entity );
					}, reject );
				} );
			} );
	}

	/**
	 * Get information about a lexeme
	 *
	 * @param {string} lexemeId
	 * @return {Promise}
	 */
	get( lexemeId ) {
		return new Promise( ( resolve, reject ) => {
			bot.request( {
				action: 'wbgetentities',
				ids: lexemeId
			} ).then( ( response ) => {
				resolve( response.entities[ lexemeId ] );
			}, reject );
		} );
	}

	/**
	 * Add a new form to a lexeme
	 *
	 * @param {string} lexemeId
	 * @param {object} form
	 * @return {Promise}
	 */
	addForm( lexemeId, form ) {
		return bot.request( {
			action: 'wbladdform',
			lexemeId: lexemeId,
			data: JSON.stringify( form ),
			token: bot.editToken
		} );
	}

	/**
	 * Add a new sense to a lexeme
	 *
	 * @param {string} lexemeId
	 * @param {object} sense
	 * @return {Promise}
	 */
	addSense( lexemeId, sense ) {
		return bot.request( {
			action: 'wbladdsense',
			lexemeId: lexemeId,
			data: JSON.stringify( sense ),
			token: bot.editToken
		} );
	}

	/**
	 * Changes representation and grammatical features of the form
	 *
	 * @param {string} formId
	 * @param {object} formData
	 * @return {Promise}
	 */
	editForm( formId, formData ) {
		return bot.request( {
			action: 'wbleditformelements',
			formId: formId,
			data: JSON.stringify( formData ),
			token: bot.editToken
		} );
	}

}

module.exports = new LexemeApi();
