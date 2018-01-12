<?php

namespace Wikibase\Lexeme\Diff;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOp;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use MessageLocalizer;
use MWException;
use Wikibase\Lexeme\DataModel\Services\Diff\ChangeFormDiffOp;
use Wikibase\Repo\Diff\BasicDiffView;
use Wikibase\Repo\Diff\ClaimDiffer;
use Wikibase\Repo\Diff\ClaimDifferenceVisualizer;

/**
 * Class for generating views of DiffOp objects of forms.
 *
 * @license GPL-2.0+
 */
class FormDiffView extends BasicDiffView {

	/**
	 * @var ClaimDiffer
	 */
	private $claimDiffer;

	/**
	 * @var ClaimDifferenceVisualizer
	 */
	private $claimDiffVisualizer;

	/**
	 * @var MessageLocalizer
	 */
	private $messageLocalizer;

	/**
	 * @param string[] $path
	 * @param Diff $diff
	 * @param ClaimDiffer $claimDiffer
	 * @param ClaimDifferenceVisualizer $claimDiffVisualizer
	 * @param MessageLocalizer $messageLocalizer
	 */
	public function __construct(
		array $path,
		Diff $diff,
		ClaimDiffer $claimDiffer,
		ClaimDifferenceVisualizer $claimDiffVisualizer,
		MessageLocalizer $messageLocalizer
	) {
		parent::__construct( $path, $diff );

		$this->claimDiffer = $claimDiffer;
		$this->claimDiffVisualizer = $claimDiffVisualizer;
		$this->messageLocalizer = $messageLocalizer;
	}

	/**
	 * @param string[] $path
	 * @param DiffOp $op
	 *
	 * @return string HTML
	 */
	protected function generateOpHtml( array $path, DiffOp $op ) {
		if ( $op->isAtomic() ) {
			return parent::generateOpHtml( $path, $op );
		}

		$html = '';

		foreach ( $op as $key => $subOp ) {
			if ( $subOp instanceof ChangeFormDiffOp ) {
				$html .= $this->generateFormOpHtml( $path, $subOp, $key );
			} else {
				$html .= $this->generateOpHtml( array_merge( $path, [ $key ] ), $subOp );
			}
		}

		return $html;
	}

	private function generateFormOpHtml( array $path, ChangeFormDiffOp $op, $key ) {
		$html = '';

		foreach ( $op->getStatementsDiffOps() as $claimDiffOp ) {
			$html .= $this->getClaimDiffHtml(
				$claimDiffOp,
				array_merge( $path, [ $key ] )
			);
		}

		$html .= parent::generateOpHtml(
			array_merge(
				$path,
				[ $key, $this->messageLocalizer->msg( 'wikibaselexeme-diffview-representation' )->text() ]
			),
			$op->getRepresentationDiffOps()
		);

		$html .= parent::generateOpHtml(
			array_merge(
				$path,
				[ $key, $this->messageLocalizer->msg( 'wikibaselexeme-diffview-grammatical-feature' )->text() ]
			),
			$op->getGrammaticalFeaturesDiffOps()
		);

		return $html;
	}

	/**
	 * @param DiffOp $diffOp
	 *
	 * @return string HTML
	 * @throws MWException
	 */
	private function getClaimDiffHtml( DiffOp $diffOp, array $path ) {
		switch ( true ) {
			case $diffOp instanceof DiffOpChange:
				return $this->claimDiffVisualizer->visualizeClaimChange(
					$this->claimDiffer->diffClaims(
						$diffOp->getOldValue(),
						$diffOp->getNewValue()
					),
					$diffOp->getNewValue(),
					$path
				);

			case $diffOp instanceof DiffOpAdd:
				return $this->claimDiffVisualizer->visualizeNewClaim( $diffOp->getNewValue(), $path );

			case $diffOp instanceof DiffOpRemove:
				return $this->claimDiffVisualizer->visualizeRemovedClaim( $diffOp->getOldValue(), $path );

			default:
				throw new MWException( 'Encountered an unexpected diff operation type for a claim' );
		}
	}

}
