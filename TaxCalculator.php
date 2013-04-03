<?php


class TaxPayer {
	
	const MARRIED_JOINTLY = 0;
	const SINGLE_HEAD_OF_HOUSE = 1;
	const MARRIED_FILE_SEPERATE = 2;

	var $taxPayerId;
	var $valueStoreMap = array();
	var $currentStepId;
	var $completedSteps = array();

	var $prefillBoxOne;
	var $prefillBoxThree;
	var $prefillBoxFour;
	var $prefillBoxSix;
	var $maritalStatus;

	function __construct($taxPayerId, $prefillBoxOne,  $prefillBoxThree, $prefillBoxFour, $prefillBoxSix, $maritalStatus) {
		$this->taxPayerId = $taxPayerId;
		$this->prefillBoxOne = $prefillBoxOne;
		$this->prefillBoxThree = $prefillBoxThree;
		$this->prefillBoxFour = $prefillBoxFour;
		$this->prefillBoxSix = $prefillBoxSix;
		$this->maritalStatus = $maritalStatus;
	}

	function displayCompletedSteps() {
		foreach($this->valueStoreMap as $stepId => $value) {
			echo "step: [" . $stepId . "]: " . $value . "\n"; 
		}
	}

}

class WorkSheet {
	
	var $stepSequence = array(); //variable: step sequence --  collection of steps

	function __construct() {

  	 }

	public function executeStep($step, &$taxPayer) {
		//TODO validate transition step dependencies are met

		$closure = $step->stepClosure;
		$closure($taxPayer);
		$taxPayer->completedSteps[] = $step->stepId; //step has been completed
		$taxPayer->currentStepId =  $step->nextStepId;  ///move to next step
	}

	public function executeStepSequence(&$taxPayer) {
		while($taxPayer->currentStepId != null) { //loop is complete when there is no next step (null)
			$currentStepId = $taxPayer->currentStepId;
			$currentStepObj = $this->stepSequence[$currentStepId];
			$this->executeStep($currentStepObj, $taxPayer);
		}
	}
}

class Step {
	
	var $stepId; //variable: step identifier: each step has an identifier, this is string describing it's purpose, it is unique
	var $stepDependencies; //variable: step dependencies:   each step has dependencies, that other steps were invoked previously to this step
	var $stepClosure; 	//variable: step closure: each step has an invokable closure which can peform a calculation on tax payer data
	var $nextStepId; //variable: next step

	function __construct($stepId, $nextStepId = null, $stepDependencies = null) {
		$this->stepId = $stepId;
		$this->nextStepId = $nextStepId;
		$this->stepDependencies = $stepDependencies;
  	 }

}



class Driver {

	public function run() {
		
		$workSheet = new WorkSheet();
	 	$taxPayers = array();

		/* initialize tax payer data */	

		$taxPayers[] = new TaxPayer('marc', 0, 120000, 0, 4000, TaxPayer::SINGLE_HEAD_OF_HOUSE);
		//$taxPayers[] = new TaxPayer('bob');
		//$taxPayers[] = new TaxPayer('steve');
		
		/* initialize work sheet steps */

		$step = new Step(1, 2, null);
		$step->stepClosure = function(&$taxPayer) use ($step) {
      		//echo $step->stepId . ' hello ' . $taxPayer->taxPayerId . "\n";
			$taxPayer->valueStoreMap[1] = $taxPayer->prefillBoxOne;

      	};

      	$workSheet->stepSequence[$step->stepId] = $step;

      	$step = new Step(2, 3, [1]);

     	$step->stepClosure = function(&$taxPayer) use ($step) {
      		if(!empty($taxPayer->prefillBoxOne)) {
      			$taxPayer->valueStoreMap[2] = ($taxPayer->prefillBoxOne / 2.0);
      		} else {
      			$taxPayer->valueStoreMap[2] = 0;
      		}
      	};

      	$workSheet->stepSequence[$step->stepId] = $step;

      	$step = new Step(3, null, [1,2]);
      	
     	$step->stepClosure = function(&$taxPayer) use ($step) {
      		//echo $step->stepId . ' hello ' . $taxPayer->taxPayerId . "\n";
      		$taxPayer->valueStoreMap[3] = $taxPayer->prefillBoxThree;

      	};

      	$workSheet->stepSequence[$step->stepId] = $step;

		/* let's do some taxes */
	
		foreach($taxPayers as $taxPayer) {
			$taxPayer->currentStepId = 1; // queue up each tax payers first step 
			$workSheet->executeStepSequence($taxPayer);
			$taxPayer->displayCompletedSteps();
			//TODO display completed work sheet
		}
	}
	

	//TODO: some I/O methods

	//method: slurp tax payer data stored on file system

	//method: slurp step sequence from file system






}

$driver = new Driver();
$driver->run();



?>