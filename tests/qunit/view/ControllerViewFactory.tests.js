( function ( wb ) {
	'use strict';

	var ControllerViewFactory = wb.lexeme.view.ControllerViewFactory;

	function newControllerViewFactory() {
		return new ControllerViewFactory(
			sinon.stub(),
			{
				getRevisionStore: sinon.stub(),
				getEntity: sinon.stub()
			},
			{
				getAdder: sinon.stub()
			},
			sinon.stub(),
			sinon.stub(),
			sinon.stub(),
			sinon.stub(),
			sinon.stub(),
			sinon.stub(),
			sinon.stub(),
			{
				getMessage: sinon.stub()
			},
			sinon.stub(),
			[],
			'vocabularyLookupApiUrl',
			'commonsApiUrl'
		);
	}

	QUnit.test( 'injects senses into SenseListView', function ( assert ) {
		var factory = newControllerViewFactory(),
			senses = [ { _id: 'L1-S5' } ],
			lexeme = {
				getSenses: sinon.stub().returns( senses )
			},
			startEditingCallback = sinon.spy();

		factory._getView = sinon.spy();

		factory.getSenseListView( lexeme, startEditingCallback );

		sinon.assert.calledWith(
			factory._getView,
			'senselistview',
			sinon.match.any,
			{
				getListItemAdapter: sinon.match.any,
				getMessage: sinon.match.any,
				getAdder: sinon.match.any,
				value: senses
			}
		);
	} );
}( wikibase ) );
