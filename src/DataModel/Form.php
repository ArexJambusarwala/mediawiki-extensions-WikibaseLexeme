<?php

namespace Wikibase\Lexeme\DataModel;

use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Statement\StatementListProvider;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @license GPL-2.0+
 * @author Thiemo Mättig
 */
class Form implements StatementListProvider {

	/**
	 * @var FormId|null
	 */
	private $id;

	/**
	 * @var string
	 */
	private $representation;

	/**
	 * @var ItemId[]
	 */
	private $grammaticalFeatures;

	/**
	 * @var StatementList
	 */
	private $statementList;

	/**
	 * @param FormId $id |null
	 * @param string $representation
	 * @param ItemId[] $grammaticalFeatures
	 * @param StatementList|null $statementList
	 */
	public function __construct(
		FormId $id = null,
		$representation,
		array $grammaticalFeatures,
		StatementList $statementList = null
	) {
		$this->id = $id;
		$this->representation = $representation;
		$this->grammaticalFeatures = $grammaticalFeatures;
		$this->statementList = $statementList ?: new StatementList();
	}

	/**
	 * @return FormId|null
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getRepresentation() {
		return $this->representation;
	}

	public function getGrammaticalFeatures() {
		return $this->grammaticalFeatures;
	}

	/**
	 * @see StatementListProvider::getStatements()
	 */
	public function getStatements() {
		return $this->statementList;
	}

}