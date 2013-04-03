<?php


class TaxPayer {
	
	const MARRIED_JOINTLY = 0;
	const SINGLE_HEAD_OF_HOUSE = 1;
	const MARRIED_FILE_SEPERATE = 2;

	var $taxPayerId;
	var $valueStoreMap = array();
	var $currentStepId;
	//var $completedSteps = array();

	var $prefillBoxOne;
	var $prefillBoxThree;
	var $prefillBoxFour;
	var $prefillBoxSix;
	var $maritalStatus;

	function __construct($taxPayerId, $prefillBoxOne, $prefillBoxThree, $prefillBoxFour, $prefillBoxSix, $maritalStatus) {
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

		if(!empty($step->stepDependencies)) { 		//validate transition step dependencies are met
 			if(!count(array_intersect($step->stepDependencies, array_keys($taxPayer->valueStoreMap))) == count($step->stepDependencies)) {
 				throw new Exception("one or step dependencies are missing, use should verify input data is good");
 			}
 		}

		$closure = $step->stepClosure;
		$closure($taxPayer);
		$taxPayer->currentStepId =  $step->nextStepId;  ///move to next step
	}

	public function executeStepSequence(&$taxPayer) {
		while($taxPayer->currentStepId != null) { //loop is complete when there is no next step (null)
			$currentStepId = $taxPayer->currentStepId;

			//validate step being executed is defined
			if(!isset($this->stepSequence[$currentStepId])) {
				throw new Exception("step: " . $currentStepId . " isn't defined");
			}

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

		$taxPayers[] = new TaxPayer('marc', 22.5, 120000, 0, 4000, TaxPayer::SINGLE_HEAD_OF_HOUSE);
		//$taxPayers[] = new TaxPayer('bob');
		//$taxPayers[] = new TaxPayer('steve');
		
		/* initialize work sheet steps */

		$step = new Step(1, 2, null);
		$step->stepClosure = function(&$taxPayer) use ($step) {
			$taxPayer->valueStoreMap[1] = $taxPayer->prefillBoxOne;

      	};
      	$workSheet->stepSequence[1] = $step;

      	$step = new Step(2, 3, array_keys($workSheet->stepSequence));
     	$step->stepClosure = function(&$taxPayer) use ($step) {
      		if(!empty($taxPayer->valueStoreMap[1])) {
      			$taxPayer->valueStoreMap[2] = ($taxPayer->valueStoreMap[1] / 2.0);
      		} else {
      			$taxPayer->valueStoreMap[2] = 0;
      		}
      	};
      	$workSheet->stepSequence[2] = $step;

		$step = new Step(3, 4, array_keys($workSheet->stepSequence));
		$step->stepClosure = function(&$taxPayer) use ($step) {
			if(!empty($taxPayer->prefillBoxThree)) {
      			$taxPayer->valueStoreMap[3] = $taxPayer->prefillBoxThree;
      		} else {
      			$taxPayer->valueStoreMap[3] =0;
      		}
      	};
      	$workSheet->stepSequence[3] = $step;

      	$step = new Step(4, 5, array_keys($workSheet->stepSequence));
		$step->stepClosure = function(&$taxPayer) use ($step) {
			if(!empty($taxPayer->prefillBoxFour)) {
      			$taxPayer->valueStoreMap[4] = $taxPayer->prefillBoxFour;
      		} else {
      			$taxPayer->valueStoreMap[4] = 0;
      		}
      	};
      	$workSheet->stepSequence[4] = $step;

      	$step = new Step(5, 6, array_keys($workSheet->stepSequence));
     	$step->stepClosure = function(&$taxPayer) use ($step) {
      			$taxPayer->valueStoreMap[5] = $taxPayer->valueStoreMap[2] + $taxPayer->valueStoreMap[3] + $taxPayer->valueStoreMap[4];
      	};
      	$workSheet->stepSequence[5] = $step;


      	$step = new Step(6, null, array_keys($workSheet->stepSequence));
		$step->stepClosure = function(&$taxPayer) use ($step) {
			if(!empty($taxPayer->prefillBoxSix)) {
      			$taxPayer->valueStoreMap[6] = $taxPayer->prefillBoxSix;
      		} else {
      			$taxPayer->valueStoreMap[6] = 0;
      		}
      	};
      	$workSheet->stepSequence[6] = $step;



		/* let's do some taxes */
	
		foreach($taxPayers as $taxPayer) {
			$taxPayer->currentStepId = 1; // queue up each tax payers first step 
			$workSheet->executeStepSequence($taxPayer);
			$taxPayer->displayCompletedSteps();
		}
	}
	

	//TODO: some I/O methods

	//method: slurp tax payer data stored on file system

	//method: slurp step sequence from file system






}

$driver = new Driver();
$driver->run();



?>