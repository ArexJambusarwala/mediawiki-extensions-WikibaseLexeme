/**
 * @license GPL-2.0-or-later
 */
( function ( wb ) {
	'use strict';

	/**
	 * @constructor
	 *
	 * @param {wikibase.api.RepoApi} api
	 * @param {wikibase.lexeme.RevisionStore} revisionStore
	 * @param {string} lexemeId
	 * @param {Object} formData
	 */
	var SELF = wb.lexeme.entityChangers.FormChanger = function WbLexemeFormChanger(
		api,
		revisionStore,
		lexemeId,
		formData
	) {
		this.api = api;
		this.revisionStore = revisionStore;
		this.lexemeId = lexemeId;
		this.formData = formData;
		this.lexemeDeserializer = new wb.lexeme.serialization.LexemeDeserializer();
		this.formSerializer = new wb.lexeme.serialization.FormSerializer();
	};

	/**
	 * A service to save changes to a form.
	 * Note that statements are not supported, only changes to the form elements.
	 * A FormChanger should only be used for changes to the same form,
	 * not shared between several forms.
	 *
	 * @class wikibase.lexeme.entityChangers.FormChanger
	 */
	$.extend( SELF.prototype, {

		/**
		 * @type {wikibase.api.RepoApi}
		 * @private
		 */
		api: null,

		/**
		 * @type {wikibase.lexeme.RevisionStore}
		 * @private
		 */
		revisionStore: null,

		/**
		 * @type {string}
		 * @private
		 */
		lexemeId: null,

		/**
		 * @type {Object}
		 * @private
		 */
		formData: null,

		/**
		 * @type {wikibase.lexeme.serialization.LexemeDeserializer}
		 * @private
		 */
		lexemeDeserializer: null,

		/**
		 * Save the changes for the given form.
		 * Statements are ignored.
		 *
		 * @param {wikibase.lexeme.datamodel.Form} form
		 * @return {jQuery.Promise}
		 */
		save: function ( form ) {
			var formSerializer = new wb.lexeme.serialization.FormSerializer();

			var serializedForm = formSerializer.serialize( form );

			if ( form.getId() ) {
				return this.saveChangedFormData( form.getId(), serializedForm.representations, serializedForm.grammaticalFeatures );
			}

			return this.saveNewFormData( serializedForm.representations, serializedForm.grammaticalFeatures );
		},

		saveChangedFormData: function ( formId, representations, grammaticalFeatures ) {
			var self = this;

			var requestRepresentations =
				this.getRepresentationDataForApiRequest( this.formData.representations, representations );

			return this.api.post( {
				action: 'wbleditformelements',
				formId: formId,
				data: JSON.stringify( {
					representations: requestRepresentations,
					grammaticalFeatures: grammaticalFeatures
				} ),
				errorformat: 'plaintext',
				bot: 0
			} ).then( function ( data ) {
				var form = self.lexemeDeserializer.deserializeForm( data.form );
				self.formData = self.formSerializer.serialize( form );
				return form;
			} ).catch( function ( code, response ) {
				throw convertPlainTextErrorsToRepoApiError( response.errors, 'save' );
			} );
		},

		saveNewFormData: function ( representations, grammaticalFeatures ) {
			var self = this;

			return this.api.post( {
				action: 'wbladdform',
				lexemeId: this.lexemeId,
				data: JSON.stringify( {
					representations: representations,
					grammaticalFeatures: grammaticalFeatures
				} ),
				errorformat: 'plaintext',
				bot: 0
			} ).then( function ( data ) {
				var form = self.lexemeDeserializer.deserializeForm( data.form );
				self.revisionStore.setFormRevision( data.lastrevid, form.getId() );
				self.formData = self.formSerializer.serialize( form );
				return form;
			} ).catch( function ( code, response ) {
				throw convertPlainTextErrorsToRepoApiError( response.errors, 'save' );
			} );
		},

		remove: function ( form ) {
			var deferred = $.Deferred();

			this.api.post( {
				action: 'wblremoveform',
				id: form.getId(),
				errorformat: 'plaintext',
				bot: 0
			} )
				.then( deferred.resolve )
				.fail( function ( code, response ) {
					deferred.reject( convertPlainTextErrorsToRepoApiError( response.errors, 'remove' ) );
				} );

			return deferred;
		},

		getRepresentationDataForApiRequest: function ( oldRepresentations, newRepresentations ) {
			var result = {};

			for ( var language in newRepresentations ) {
				var newRepresentation = newRepresentations[ language ].value;

				if ( ( !( language in oldRepresentations ) )
					|| ( oldRepresentations[ language ].value !== newRepresentation )
				) {
					result[ language ] = {
						language: language,
						value: newRepresentation
					};
				}
			}
			for ( language in oldRepresentations ) {
				if ( !( language in newRepresentations ) ) {
					result[ language ] = {
						language: language,
						remove: ''
					};
				}
			}

			return result;
		}
	} );

	function convertPlainTextErrorsToRepoApiError( errors, action ) {
		var $ul = $( '<ul>' );

		var code = '';
		errors.forEach( function ( e ) {
			if ( !code ) {
				code = e.code;
			}

			var $li = $( '<li>' ).text( e[ '*' ] );
			$ul.append( $li );
		} );

		return new wb.api.RepoApiError(
			code,
			$ul.html(),
			[],
			action
		);
	}

}( wikibase ) );
