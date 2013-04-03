<?php


class TaxPayer {
	var $taxPayerId;
	var $valueStoreMap = array();
	var $currentStepId = 1;
	var $completedSteps = array();

	function __construct($taxPayerId) {
		$this->taxPayerId = $taxPayerId;
	}
}

class WorkSheet {
	//variable: step sequence --  collection of steps
	var $stepSequence = array();

	function __construct() {

  	 }

	public function executeStep($step, &$taxPayer) {
		$closure = $step->stepClosure;
		$closure($taxPayer);
	}

	public function executeStepSequence(&$taxPayer) {
		

		while($taxPayer->currentStepId != null) {

			$currentStepId = $taxPayer->currentStepId;

			$currentStepObj = $this->stepSequence[$currentStepId];

			//TODO validate transition step dependencies are met

			$this->executeStep($currentStepObj, $taxPayer);

			$taxPayer->currentStepId = $currentStepObj->nextStepId; //move to next step
		}
	}
}

class Step {
	//variable: step identifier: each step has an identifier, this is string describing it's purpose, it is unique
	var $stepId;
	//variable: step dependencies:   each step has dependencies, that other steps were invoked previously to this step
	var $stepDependencies;
	//variable: step closure: each step has an invokable closure which can peform a calculation on tax payer data
	var $stepClosure;
	//variable: next step
	var $nextStepId;
}



class Driver {

	//variable: worksheet
	public function run() {
		
		$workSheet = new WorkSheet();
	 	$taxPayers = array();
	
		//variable: tax payer data list: -- values for data box: 1, 3, 4, 6, tax marital status: (single, married, seperated)

		$taxPayers[] = new TaxPayer('marc');
		$taxPayers[] = new TaxPayer('bob');
		$taxPayers[] = new TaxPayer('steve');

	
		 $step = new Step();
      
     	 $step->stepId = 1;
     	 $step->nextStepId = 2;
     	 $step->stepClosure = function(&$taxPayer) use ($step) {
      		echo $step->stepId . ' hello ' . $taxPayer->taxPayerId . "\n";

      	};


      	$workSheet->stepSequence[$step->stepId] = $step;

      	$step = new Step();

      	 $step->stepId = 2;
      	 $step->nextStepId = 3;
     	 $step->stepClosure = function(&$taxPayer) use ($step) {
      		echo $step->stepId . ' hello ' . $taxPayer->taxPayerId . "\n";

      	};

      	$workSheet->stepSequence[$step->stepId] = $step;

      	$step = new Step();
      	
      	 $step->stepId = 3;
      	 $step->nextStepId = null;
     	 $step->stepClosure = function(&$taxPayer) use ($step) {
      		echo $step->stepId . ' hello ' . $taxPayer->taxPayerId . "\n";

      	};

      	$workSheet->stepSequence[$step->stepId] = $step;


		foreach($taxPayers as $taxPayer) {
			$workSheet->executeStepSequence($taxPayer);
		}

	


	}
	
	//method: slurp tax payer data stored on file system

	//method: slurp step sequence from file system

	//method: initialize tax payer data

	//method: initialize step sequence 

	//method: execute worksheet

	//method: display value store map


}

$driver = new Driver();
$driver->run();



?>