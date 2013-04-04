<?php


class TaxPayer {
	
	const MARRIED_JOINTLY = 0;
	const SINGLE_HEAD_OF_HOUSE = 1;
	const MARRIED_FILE_SEPERATE = 2;

	var $taxPayerId;
	var $valueStoreMap = array();
	var $currentStepId;

	var $prefillBoxOne;
	var $prefillBoxThree;
	var $prefillBoxFour;
	var $prefillBoxSix;
	var $maritalStatus;
	var $hitStopCondition = false;

	function __construct($taxPayerId, $prefillBoxOne, $prefillBoxThree, $prefillBoxFour, $prefillBoxSix, $maritalStatus) {
		$this->taxPayerId = $taxPayerId;
		$this->prefillBoxOne = $prefillBoxOne;
		$this->prefillBoxThree = $prefillBoxThree;
		$this->prefillBoxFour = $prefillBoxFour;
		$this->prefillBoxSix = $prefillBoxSix;
		$this->maritalStatus = $maritalStatus;
	}

	function displayCompletedSteps() {
		echo "displaying social security benefits worksheet -- lines 20a and 20b for: " . $this->taxPayerId . "\n";
		foreach($this->valueStoreMap as $stepId => $value) {
			echo "step: [" . $stepId . "]: " . $value . "\n"; 
		}

		if($this->hitStopCondition) {
			echo "apparantly, none of your social security benefits are taxable, sorry: " . $this->taxPayerId . "\n";
		} else {
			//display something useful?
		}

	}

	static function isValidMaritalStatus(){

		if(($taxPayer->maritalStatus == TaxPayer::MARRIED_JOINTLY || 
			 $taxPayer->maritalStatus == TaxPayer::SINGLE_HEAD_OF_HOUSE ||
			 $taxPayer->maritalStatus == TaxPayer::MARRIED_FILE_SEPERATE)) {
     			return true;
     		} else {
     			return false;
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
		while($taxPayer->currentStepId != null && !$taxPayer->hitStopCondition) { //loop is complete when there is no next step (null), and there is no stop condition (ex steps:7, 8 from 2012 form)
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


      	$step = new Step(6, 7, array_keys($workSheet->stepSequence));
		$step->stepClosure = function(&$taxPayer) use ($step) {
			if(!empty($taxPayer->prefillBoxSix)) {
      			$taxPayer->valueStoreMap[6] = $taxPayer->prefillBoxSix;
      		} else {
      			$taxPayer->valueStoreMap[6] = 0;
      		}
      	};
      	$workSheet->stepSequence[6] = $step;

      	$step = new Step(7, 8, array_keys($workSheet->stepSequence));
     	$step->stepClosure = function(&$taxPayer) use ($step) {

     			if($taxPayer->valueStoreMap[6] < $taxPayer->valueStoreMap[5]) {
     				$taxPayer->valueStoreMap[7] =  $taxPayer->valueStoreMap[5] - $taxPayer->valueStoreMap[6];
     			} else {
     				$taxPayer->hitStopCondition = true;
     			}
      	};
      	$workSheet->stepSequence[7] = $step;

      	$step = new Step(8, 9, array_keys($workSheet->stepSequence));
     	$step->stepClosure = function(&$taxPayer) use ($step) {
     			if($taxPayer->maritalStatus == TaxPayer::MARRIED_JOINTLY) {
     				$taxPayer->valueStoreMap[8] = 32000.00;
     			} else if($taxPayer->maritalStatus == TaxPayer::SINGLE_HEAD_OF_HOUSE)  {
     				$taxPayer->valueStoreMap[8] = 25000.00;
     			} else if($taxPayer->maritalStatus == TaxPayer::MARRIED_FILE_SEPERATE)  {
     				$taxPayer->valueStoreMap[8] = 0;
     			} else {
     				throw new Exception("unrecognized marriage status, have to break here sorry");
     			}
      	};
      	$workSheet->stepSequence[8] = $step;

      	$step = new Step(9, 10, array_keys($workSheet->stepSequence)); //TODO deal with undefined marrital statuses here
     	$step->stepClosure = function(&$taxPayer) use ($step) {
     		if($taxPayer->maritalStatus == TaxPayer::MARRIED_FILE_SEPERATE)  {
     			$taxPayer->valueStoreMap[9] = null; 
     		}
     		else if($taxPayer->valueStoreMap[8] < $taxPayer->valueStoreMap[7]) {
     			$taxPayer->valueStoreMap[9] =  $taxPayer->valueStoreMap[7] - $taxPayer->valueStoreMap[8];
     		} else {
     			$taxPayer->hitStopCondition = true;
     		}
     	};
     	$workSheet->stepSequence[9] = $step;

     	$step = new Step(10, 11, array_keys($workSheet->stepSequence));
     	$step->stepClosure = function(&$taxPayer) use ($step) {
     		if($taxPayer->maritalStatus == TaxPayer::MARRIED_FILE_SEPERATE)  {
     			$taxPayer->valueStoreMap[10] = null;
     		} else if($taxPayer->maritalStatus == TaxPayer::SINGLE_HEAD_OF_HOUSE) {
     			$taxPayer->valueStoreMap[10] = 9000.00;
     		} 
     		else {
     			$taxPayer->valueStoreMap[10] = 12000.00;
     		} 
     		
     	};
     	$workSheet->stepSequence[10] = $step;

     	$step = new Step(11, 12, array_keys($workSheet->stepSequence));
     	$step->stepClosure = function(&$taxPayer) use ($step) {
     		if($taxPayer->maritalStatus == TaxPayer::MARRIED_FILE_SEPERATE)  {
     			$taxPayer->valueStoreMap[11] = null; 
     		} else if( ($taxPayer->valueStoreMap[9] - $taxPayer->valueStoreMap[10]) > 0) {
     			$taxPayer->valueStoreMap[11] = $taxPayer->valueStoreMap[9] - $taxPayer->valueStoreMap[10];
     		} else {
     			$taxPayer->valueStoreMap[11] = "-0-";
     		}
     	};
     	$workSheet->stepSequence[11] = $step;

     	$step = new Step(12, 13, array_keys($workSheet->stepSequence));
     	$step->stepClosure = function(&$taxPayer) use ($step) {
     		if($taxPayer->maritalStatus == TaxPayer::MARRIED_FILE_SEPERATE)  {
     			$taxPayer->valueStoreMap[12] = null;
     		} else {
     			$taxPayer->valueStoreMap[12] = min ($taxPayer->valueStoreMap[10],$taxPayer->valueStoreMap[11]);
     		}
     	};
     	$workSheet->stepSequence[12] = $step;

     	$step = new Step(13, 14, array_keys($workSheet->stepSequence));
     	$step->stepClosure = function(&$taxPayer) use ($step) {
     		if($taxPayer->maritalStatus == TaxPayer::MARRIED_FILE_SEPERATE)  {
     			$taxPayer->valueStoreMap[13] = null;
     		} else {
     			$taxPayer->valueStoreMap[13] = ($taxPayer->valueStoreMap[12] / 2.0);
     		}
     	};
     	$workSheet->stepSequence[13] = $step;

     	$step = new Step(14, 15, array_keys($workSheet->stepSequence));
     	$step->stepClosure = function(&$taxPayer) use ($step) {
     		if($taxPayer->maritalStatus == TaxPayer::MARRIED_FILE_SEPERATE)  {
     			$taxPayer->valueStoreMap[14] = null;
     		} else {
     			$taxPayer->valueStoreMap[14] = min($taxPayer->valueStoreMap[2],$taxPayer->valueStoreMap[13]);
     		}
     	};
     	$workSheet->stepSequence[14] = $step;

     	$step = new Step(15, 16, array_keys($workSheet->stepSequence));
     	$step->stepClosure = function(&$taxPayer) use ($step) {
     		if($taxPayer->maritalStatus == TaxPayer::MARRIED_FILE_SEPERATE)  {
     			$taxPayer->valueStoreMap[15] = null;
     		} else {
     			if($taxPayer->valueStoreMap[11] == 0) {
     				$taxPayer->valueStoreMap[15] = "-0-";
     			} else {
     				$taxPayer->valueStoreMap[15] = $taxPayer->valueStoreMap[11] * 0.85;
     			}

     		}
     	};
     	$workSheet->stepSequence[15] = $step;

     	$step = new Step(16, 17, array_keys($workSheet->stepSequence));
     	$step->stepClosure = function(&$taxPayer) use ($step) {
     		if($taxPayer->maritalStatus == TaxPayer::MARRIED_FILE_SEPERATE)  {
     			$taxPayer->valueStoreMap[16] = $taxPayer->valueStoreMap[7] * 0.85;
     		} else {
     			$taxPayer->valueStoreMap[16] = $taxPayer->valueStoreMap[14] + $taxPayer->valueStoreMap[15];
     		}
     	};
     	$workSheet->stepSequence[16] = $step;

     	$step = new Step(17, 18, array_keys($workSheet->stepSequence));
     	$step->stepClosure = function(&$taxPayer) use ($step) {
     		$taxPayer->valueStoreMap[17] = $taxPayer->valueStoreMap[1] * 0.85;
     	};
     	$workSheet->stepSequence[17] = $step;

     	$step = new Step(18, null, array_keys($workSheet->stepSequence));
     	$step->stepClosure = function(&$taxPayer) use ($step) {
     		$taxPayer->valueStoreMap[18] = min($taxPayer->valueStoreMap[16],$taxPayer->valueStoreMap[17]);
     	};
     	$workSheet->stepSequence[18] = $step;



		/* let's do some taxes */
	
		foreach($taxPayers as $taxPayer) {
			try {
				$taxPayer->currentStepId = 1; // queue up each tax payers first step 
				$workSheet->executeStepSequence($taxPayer);
				$taxPayer->displayCompletedSteps();
			} catch (Exception $ex) {
				echo "exception encountered while processing form for: " . $taxPayer->taxPayerId . " -- moving along\n";
				echo "exception encountered: " . $ex->getMesssage() . "\n";
			}
		}
	}
	

	//TODO: some I/O methods

	//method: slurp tax payer data stored on file system

	//method: slurp step sequence from file system






}

$driver = new Driver();
$driver->run();



?>