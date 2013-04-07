<?php


class TaxPayer {

	/* I shortened these constant names to the first phrase in their description on the form -- they can be pigeon holed into these 3 groups */

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
	var $stepQueue;

	function __construct($taxPayerId, $prefillBoxOne, $prefillBoxThree, $prefillBoxFour, $prefillBoxSix, $maritalStatus) {
		$this->taxPayerId = $taxPayerId;
		$this->prefillBoxOne = $prefillBoxOne;
		$this->prefillBoxThree = $prefillBoxThree;
		$this->prefillBoxFour = $prefillBoxFour;
		$this->prefillBoxSix = $prefillBoxSix;
		$this->maritalStatus = $maritalStatus;
	}

	function assignStepQueue($stepQueue) {
		$this->stepQueue = $stepQueue;
		$this->currentStepId = $stepQueue[0];
	}

	function displayCompletedSteps() {
		echo "displaying social security benefits worksheet -- lines 20a and 20b for: " . $this->taxPayerId . "\n";
		foreach($this->valueStoreMap as $stepId => $value) {
			echo "step: [" . $stepId . "]: " . $value . "\n";
		}

		if($this->hitStopCondition) {
			echo "apparantly, none of your social security benefits are taxable, sorry: " . $this->taxPayerId . "\n";
		}
	}

	public function nextStep($targetStep = null) {
		if(isset($targetStep) ) {
			if(in_array($targetStep, $this->stepQueue)) {
				if($targetStep !=  $this->stepQueue[0]) {
					while($targetStep !=  $this->stepQueue[0] ) {
						 array_shift($this->stepQueue);
					}
					$this->currentStepId =  $this->stepQueue[0];
				}
			} else  {
				throw new Exception("target step: " . $targetStep . " is not in this tax payer's queue");
			}
		} else {
			if(array_shift($this->stepQueue) != null  ) {
				if(isset( $this->stepQueue[0]) ) {
					$this->currentStepId =  $this->stepQueue[0];
				} else {
					$this->currentStepId  = null;
				}
			}
		}
	}

	function isValidMaritalStatus() {
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

	var $stepCollection = array(); //this is a map, an associative array of step id -> step object

	function __construct() {

  	 }

	public function executeStep($step, &$taxPayer) {

		//deprecated -- the following block, it dosen't seem useful anymore for this project, I will hang on to it for now though.
		/*
		if(!empty($step->stepDependencies)) { 		//validate transition step dependencies are met
 			if(!count(array_intersect($step->stepDependencies, array_keys($taxPayer->valueStoreMap))) == count($step->stepDependencies)) {
 				throw new Exception("one or step dependencies are missing, use should verify input data is good");
 			}
 		}*/

		$closure = $step->stepClosure;
		$val = $closure($taxPayer); //when a value is returned from closure, we must skip to this step
		$taxPayer->nextStep($val);
	}

	public function executeStepSequence(&$taxPayer) {
		while($taxPayer->currentStepId != null && !$taxPayer->hitStopCondition) { //loop is complete when there is no next step (null), and there is no stop condition (ex steps:7, 8 from 2012 form)

			$currentStepId = $taxPayer->currentStepId;

			//validate step being executed is defined
			if(!isset($this->stepCollection[$currentStepId])) {
				throw new Exception("step: " . $currentStepId . " isn't defined");
			}

			$currentStepObj = $this->stepCollection[$currentStepId];
			$this->executeStep($currentStepObj, $taxPayer);
		}
	}
}

class Step {

	var $stepId; //variable: step identifier: each step has an identifier, this is string describing it's purpose, it is unique
	//deprecated -- var $stepDependencies; //variable: step dependencies:   each step has dependencies, that other steps were invoked previously to this step
	var $stepClosure; //variable: step closure: each step has an invokable closure which can peform a calculation on tax payer data

	function __construct($stepId)  {
		$this->stepId = $stepId;
	}

}

class Driver {

	public function run() {

		$workSheet = new WorkSheet();
	 	$taxPayers = array();

		/* initialize tax payer data */

		$taxPayers[] = new TaxPayer('marc', 200000, 1000000, 0, 40000, TaxPayer::SINGLE_HEAD_OF_HOUSE);
		$taxPayers[] = new TaxPayer('bob', 2000, 35000, 0, 90000, TaxPayer::SINGLE_HEAD_OF_HOUSE);
		$taxPayers[] = new TaxPayer('larry', 0, 120000, 0, 700, TaxPayer::MARRIED_JOINTLY);
		$taxPayers[] = new TaxPayer('steve', 90000, 120000, 0, 300, TaxPayer::MARRIED_FILE_SEPERATE);

		/* initialize work sheet steps */
		/* please note, step id's constructor do not imply their specifec order in the worksheet, they are simply unique identifiers */

		$step = new Step(1);
		$step->stepClosure = function(&$taxPayer) use ($step) {
		$taxPayer->valueStoreMap[1] = $taxPayer->prefillBoxOne;

		};
		$workSheet->stepCollection[1] = $step;

		$step = new Step(2);
		$step->stepClosure = function(&$taxPayer) use ($step) {
			if(!empty($taxPayer->valueStoreMap[1])) {
				$taxPayer->valueStoreMap[2] = ($taxPayer->valueStoreMap[1] / 2.0);
			} else {
				$taxPayer->valueStoreMap[2] = 0;
			}
		};
		$workSheet->stepCollection[2] = $step;

		$step = new Step(3);
		$step->stepClosure = function(&$taxPayer) use ($step) {
			if(!empty($taxPayer->prefillBoxThree)) {
				$taxPayer->valueStoreMap[3] = $taxPayer->prefillBoxThree;
			} else {
				$taxPayer->valueStoreMap[3] =0;
			}
		};
		$workSheet->stepCollection[3] = $step;

		$step = new Step(4);
		$step->stepClosure = function(&$taxPayer) use ($step) {
			if(!empty($taxPayer->prefillBoxFour)) {
				$taxPayer->valueStoreMap[4] = $taxPayer->prefillBoxFour;
			} else {
			$taxPayer->valueStoreMap[4] = 0;
			}
		};
		$workSheet->stepCollection[4] = $step;

		$step = new Step(5);
		$step->stepClosure = function(&$taxPayer) use ($step) {
			$taxPayer->valueStoreMap[5] = $taxPayer->valueStoreMap[2] + $taxPayer->valueStoreMap[3] + $taxPayer->valueStoreMap[4];
		};
		$workSheet->stepCollection[5] = $step;

		$step = new Step(6);
		$step->stepClosure = function(&$taxPayer) use ($step) {
			if(!empty($taxPayer->prefillBoxSix)) {
				$taxPayer->valueStoreMap[6] = $taxPayer->prefillBoxSix;
			} else {
				$taxPayer->valueStoreMap[6] = 0;
			}
		};
		$workSheet->stepCollection[6] = $step;

		$step = new Step(7);
		$step->stepClosure = function(&$taxPayer) use ($step) {
			if($taxPayer->valueStoreMap[6] < $taxPayer->valueStoreMap[5]) {
				$taxPayer->valueStoreMap[7] = $taxPayer->valueStoreMap[5] - $taxPayer->valueStoreMap[6];
			} else {
				$taxPayer->hitStopCondition = true;
			}
		};
		$workSheet->stepCollection[7] = $step;

		$step = new Step(8);
		$step->stepClosure = function(&$taxPayer) use ($step) {
			if($taxPayer->maritalStatus == TaxPayer::MARRIED_JOINTLY) {
				$taxPayer->valueStoreMap[8] = 32000.00;
			} else if($taxPayer->maritalStatus == TaxPayer::SINGLE_HEAD_OF_HOUSE) {
				$taxPayer->valueStoreMap[8] = 25000.00;
			} else if($taxPayer->maritalStatus == TaxPayer::MARRIED_FILE_SEPERATE) {
				 return 16; //skipping to step 16
			} else {
				throw new Exception("unrecognized marriage status, have to break here sorry");
			}
		};
		$workSheet->stepCollection[8] = $step;

		$step = new Step(9);
		$step->stepClosure = function(&$taxPayer) use ($step) {
			if($taxPayer->valueStoreMap[8] < $taxPayer->valueStoreMap[7]) {
				$taxPayer->valueStoreMap[9] = $taxPayer->valueStoreMap[7] - $taxPayer->valueStoreMap[8];
			} else {
				$taxPayer->hitStopCondition = true;
			}
		};
		$workSheet->stepCollection[9] = $step;

		$step = new Step(10);
		$step->stepClosure = function(&$taxPayer) use ($step) {
			if($taxPayer->maritalStatus == TaxPayer::SINGLE_HEAD_OF_HOUSE) {
				$taxPayer->valueStoreMap[10] = 9000.00;
			}
			else {
				$taxPayer->valueStoreMap[10] = 12000.00;
			}
		};
		$workSheet->stepCollection[10] = $step;

		$step = new Step(11);
		$step->stepClosure = function(&$taxPayer) use ($step) {
			if( ($taxPayer->valueStoreMap[9] - $taxPayer->valueStoreMap[10]) > 0) {
				$taxPayer->valueStoreMap[11] = $taxPayer->valueStoreMap[9] - $taxPayer->valueStoreMap[10];
			} else {
				$taxPayer->valueStoreMap[11] = 0;
			}
		};
		$workSheet->stepCollection[11] = $step;

		$step = new Step(12);
		$step->stepClosure = function(&$taxPayer) use ($step) {
			$taxPayer->valueStoreMap[12] = min ($taxPayer->valueStoreMap[10],$taxPayer->valueStoreMap[11]);
		};
		$workSheet->stepCollection[12] = $step;

		$step = new Step(13);
		$step->stepClosure = function(&$taxPayer) use ($step) {
			$taxPayer->valueStoreMap[13] = ($taxPayer->valueStoreMap[12] / 2.0);
		};
		$workSheet->stepCollection[13] = $step;

		$step = new Step(14);
		$step->stepClosure = function(&$taxPayer) use ($step) {
			$taxPayer->valueStoreMap[14] = min($taxPayer->valueStoreMap[2],$taxPayer->valueStoreMap[13]);
		};
		$workSheet->stepCollection[14] = $step;

		$step = new Step(15);
		$step->stepClosure = function(&$taxPayer) use ($step) {
			if($taxPayer->valueStoreMap[11] == 0) {
				$taxPayer->valueStoreMap[15] = 0;
			} else {
				$taxPayer->valueStoreMap[15] = $taxPayer->valueStoreMap[11] * 0.85;
			}
		};
		$workSheet->stepCollection[15] = $step;

		$step = new Step(16);
		$step->stepClosure = function(&$taxPayer) use ($step) {
			if($taxPayer->maritalStatus == TaxPayer::MARRIED_FILE_SEPERATE)  {
				$taxPayer->valueStoreMap[16] = $taxPayer->valueStoreMap[7] * 0.85;
			} else {
				$taxPayer->valueStoreMap[16] = $taxPayer->valueStoreMap[14] + $taxPayer->valueStoreMap[15];
			}
		};
		$workSheet->stepCollection[16] = $step;

		$step = new Step(17);
		$step->stepClosure = function(&$taxPayer) use ($step) {
			$taxPayer->valueStoreMap[17] = $taxPayer->valueStoreMap[1] * 0.85;
		};
		$workSheet->stepCollection[17] = $step;

		$step = new Step(18);
		$step->stepClosure = function(&$taxPayer) use ($step) {
		$taxPayer->valueStoreMap[18] = min($taxPayer->valueStoreMap[16],$taxPayer->valueStoreMap[17]);
		};
		$workSheet->stepCollection[18] = $step;

		/* fifo queue defining the steps to be executed and their order, this will be managed in the state of tax payer objects  */

		$stepQueue = [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18];

		/* let's do some taxes */

		foreach($taxPayers as $taxPayer) {
			try {
				$taxPayer->assignStepQueue($stepQueue);
				$workSheet->executeStepSequence($taxPayer);
				$taxPayer->displayCompletedSteps();
			} catch (Exception $ex) {
				echo "exception encountered while processing form for: " . $taxPayer->taxPayerId . " -- ";
				echo  "message: " . $ex->getMessage() . "\n";
				}
			}
		}
}

$driver = new Driver();
$driver->run();



?>
