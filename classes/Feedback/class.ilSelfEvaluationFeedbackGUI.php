<?php
require_once('./Services/Form/classes/class.ilPropertyFormGUI.php');
require_once(dirname(__FILE__) . '/../class.ilObjSelfEvaluationGUI.php');
require_once('class.ilSelfEvaluationFeedback.php');
require_once('class.ilSelfEvaluationFeedbackTableGUI.php');
require_once(dirname(__FILE__) . '/../Form/class.ilSliderInputGUI.php');
require_once('./Services/Chart/classes/class.ilChart.php');
require_once('./Services/Utilities/classes/class.ilConfirmationGUI.php');
/**
 * GUI-Class ilSelfEvaluationFeedbackGUI
 *
 * @author            Fabian Schmid <fabian.schmid@ilub.unibe.ch>
 * @version           $Id:
 *
 * @ilCtrl_Calls      ilSelfEvaluationFeedbackGUI:
 * @ilCtrl_IsCalledBy ilSelfEvaluationFeedbackGUI:
 */
class ilSelfEvaluationFeedbackGUI {

	const WIDTH = 900;
	/**
	 * @var ilTabsGUI
	 */
	protected $tabs_gui;
	/**
	 * @var ilPropertyFormGUI
	 */
	protected $form;


	function __construct(ilObjSelfEvaluationGUI $parent) {
		global $tpl, $ilCtrl, $ilToolbar;
		/**
		 * @var $tpl       ilTemplate
		 * @var $ilCtrl    ilCtrl
		 * @var $ilToolbar ilToolbarGUI
		 */
		$this->tpl = $tpl;
		$this->ctrl = $ilCtrl;
		$this->parent = $parent;
		$this->toolbar = $ilToolbar;
		$this->tabs_gui = $this->parent->tabs_gui;
		$this->pl = new ilSelfEvaluationPlugin();
		$this->block = new ilSelfEvaluationBlock($_GET['block_id']);
		if ($_GET['feedback_id']) {
			$this->object = new ilSelfEvaluationFeedback($_GET['feedback_id']);
		} else {
			$this->object = ilSelfEvaluationFeedback::_getNewInstanceByParentId($this->block->getId());
		}
		// jQuery
		$this->tpl->addCss($this->pl->getDirectory()
		. '/templates/jquery-ui-1.10.3.custom/css/smoothness/jquery-ui-1.10.3.custom.min.css');
		//		$this->tpl->addJavaScript($this->pl->getDirectory() . '/templates/jquery-ui-1.10.3.custom/js/jquery-1.9.1.js');
		$this->tpl->addJavaScript($this->pl->getDirectory()
		. '/templates/jquery-ui-1.10.3.custom/js/jquery-ui-1.10.3.custom.min.js');
	}


	public function executeCommand() {
		$this->tabs_gui->setTabActive('administration');
		$this->ctrl->saveParameter($this, 'block_id');
		$this->ctrl->saveParameter($this, 'feedback_id');
		$this->ctrl->saveParameterByClass('ilSelfEvaluationBlockGUI', 'block_id');
		$cmd = ($this->ctrl->getCmd()) ? $this->ctrl->getCmd() : $this->getStandardCommand();
		switch ($cmd) {
			default:
				$this->performCommand($cmd);
				break;
		}

		return true;
	}


	/**
	 * @return string
	 */
	public function getStandardCommand() {
		return 'listObjects';
	}


	/**
	 * @param $cmd
	 */
	function performCommand($cmd) {
		switch ($cmd) {
			case 'listObjects':
			case 'addNew':
			case 'cancel':
			case 'createObject':
			case 'updateObject':
			case 'editFeedback':
			case 'checkNextValue':
			case 'deleteFeedback':
			case 'deleteObject':
				//				$this->checkPermission('read'); FSX
				$this->$cmd();
				break;
		}
	}


	public function cancel() {
		$this->ctrl->setParameter($this, 'feedback_id', '');
		$this->ctrl->redirect($this);
	}


	public function listObjects() {
		$this->toolbar->addButton($this->pl->txt('back_to_blocks'), $this->ctrl->getLinkTargetByClass('ilSelfEvaluationBlockGUI', 'showContent'));
		$ov = $this->getOverview();
		$table = new ilSelfEvaluationFeedbackTableGUI($this, 'listObjects');
		$this->tpl->setContent($ov->get() . $table->getHTML());
	}


	public function addNew() {
		$this->initForm();
		$this->object->setStartValue(ilSelfEvaluationFeedback::_getNextMinValueForParentId($this->block->getId(), $_GET['start_value'] ? $_GET['start_value'] : 0));
		$this->object->setEndValue(ilSelfEvaluationFeedback::_getNextMaxValueForParentId($this->block->getId(), $this->object->getStartValue()));
		$this->setValues();
		$this->tpl->setContent($this->form->getHTML());
	}


	public function checkNextValue() {
		header('Cache-Control: no-cache, must-revalidate');
		header('Content-type: application/json');
		$ignore = ($_GET['feedback_id'] ? $_GET['feedback_id'] : 0);
		$next_min = ilSelfEvaluationFeedback::_getNextMinValueForParentId($_GET['block_id'], 0, $ignore);
		$next_max = ilSelfEvaluationFeedback::_getNextMaxValueForParentId($_GET['block_id'], $next_min, $ignore);
		$state = (($_GET['from'] < $next_min) OR ($_GET['to'] > $next_max)) ? false : true;
		echo json_encode(array(
			'check' => $state,
			'next_from' => $next_min,
			'next_to' => $next_max
		));
		exit;
	}


	public function initForm($mode = 'create') {
		$this->form = new  ilPropertyFormGUI();
		$this->form->setTitle($this->pl->txt($mode . '_feedback_form'));
		$this->form->setFormAction($this->ctrl->getFormAction($this));
		$this->form->addCommandButton($mode . 'Object', $this->pl->txt($mode . '_feedback_button'));
		$this->form->addCommandButton('cancel', $this->pl->txt('cancel'));
		// Block
		$te = new ilNonEditableValueGUI($this->pl->txt('block'), 'block');
		$te->setValue($this->block->getTitle());
		$this->form->addItem($te);
		// Title
		$te = new ilTextInputGUI($this->pl->txt('title'), 'title');
		$te->setRequired(true);
		$this->form->addItem($te);
		// Description
		$te = new ilTextInputGUI($this->pl->txt('description'), 'description');
		$this->form->addItem($te);
		// StartValue
		/*$se = new ilSelectInputGUI($this->pl->txt('start_value'), 'start_value');
		for ($x = $max ? $max : 1; $x <= 100; $x ++) {
			$opt[$x] = $x . '%';
		}
		$se->setRequired(true);
		$se->setOptions($opt);
		if (! $max) {
			$se->setDisabled(true);
		} else {
			$se->setValue($max);
		}
		$this->form->addItem($se);
		// EndValue
		$se = new ilSelectInputGUI($this->pl->txt('end_value'), 'end_value');
		$se->setRequired(true);
		$se->setOptions($opt);
		$this->form->addItem($se);*/
		// Slider
		$sl = new ilSliderInputGUI($this->pl->txt('slider'), 'slider', 0, 100, $this->ctrl->getLinkTarget($this, 'checkNextValue'));
		$sl->setRequired(true);
		$this->form->addItem($sl);
		// Feedbacktext
		$te = new ilTextAreaInputGUI($this->pl->txt('feedback_text'), 'feedback_text');
		$te->setUseRte(true);
		$te->setRequired(true);
		$this->form->addItem($te);
	}


	public function createObject() {
		$this->initForm();
		if ($this->form->checkInput()) {
			$obj = ilSelfEvaluationFeedback::_getNewInstanceByParentId($this->block->getId());
			$obj->setTitle($this->form->getInput('title'));
			$obj->setDescription($this->form->getInput('description'));
			$slider = $this->form->getInput('slider');
			$obj->setStartValue($slider[0]);
			$obj->setEndValue($slider[1]);
			$obj->setFeedbackText($this->form->getInput('feedback_text'));
			$obj->create();
			ilUtil::sendSuccess($this->pl->txt('msg_feedback_created'));
			$this->cancel();
		}
		$this->form->setValuesByPost();
		$this->tpl->setContent($this->form->getHTML());
	}


	public function setValues() {
		$values['title'] = $this->object->getTitle();
		$values['description'] = $this->object->getDescription();
		$values['start_value'] = $this->object->getStartValue();
		$values['end_value'] = $this->object->getEndValue();
		$values['feedback_text'] = $this->object->getFeedbackText();
		$values['slider'] = array( $this->object->getStartValue(), $this->object->getEndValue() );
		$this->form->setValuesByArray($values);
	}


	public function editFeedback() {
		$this->initForm('update');
		$this->setValues();
		$this->tpl->setContent($this->form->getHTML());
	}


	public function updateObject() {
		$this->initForm();
		if ($this->form->checkInput()) {
			$this->object->setTitle($this->form->getInput('title'));
			$this->object->setDescription($this->form->getInput('description'));
			$slider = $this->form->getInput('slider');
			$this->object->setStartValue($slider[0]);
			$this->object->setEndValue($slider[1]);
			$this->object->setFeedbackText($this->form->getInput('feedback_text'));
			$this->object->update();
			ilUtil::sendSuccess($this->pl->txt('msg_feedback_created'));
			$this->cancel();
		}
		$this->form->setValuesByPost();
		$this->tpl->setContent($this->form->getHTML());
	}


	public function deleteFeedback() {
		ilUtil::sendQuestion($this->pl->txt('qst_delete_feedback'));
		$conf = new ilConfirmationGUI();
		$conf->setFormAction($this->ctrl->getFormAction($this));
		$conf->setCancel($this->pl->txt('cancel'), 'cancel');
		$conf->setConfirm($this->pl->txt('delete_feedback'), 'deleteObject');
		$conf->addItem('feedback_id', $this->object->getId(), $this->object->getTitle());
		$this->tpl->setContent($conf->getHTML());
	}


	public function deleteObject() {
		ilUtil::sendSuccess($this->pl->txt('msg_feedback_deleted'), true);
		$this->object->delete();
		$this->cancel();
	}


	/**
	 * @return ilTemplate
	 */
	public function getOverview() {
		$this->getMesurement();
		$min = ilSelfEvaluationFeedback::_getNextMinValueForParentId($this->block->getId());
		$feedbacks = ilSelfEvaluationFeedback::_getAllInstancesForParentId($this->block->getId());
		if (count($feedbacks) == 0) {
			$this->parseOverviewBlock('blank', 100, 0);

			return $this->ov;
		}
		foreach ($feedbacks as $fb) {
			if ($min !== false AND $min <= $fb->getStartValue()) {
				$this->parseOverviewBlock('blank', $fb->getStartValue() - $min, $min);
			}
			$this->parseOverviewBlock('fb', $fb->getEndValue() - $fb->getStartValue(), $fb->getId(), $fb->getTitle());
			$min = ilSelfEvaluationFeedback::_getNextMinValueForParentId($this->block->getId(), $fb->getEndValue());
		}
		if ($min != 100 AND is_object($fb)) {
			$this->parseOverviewBlock('blank', 99 - $min, $min);
		}

		return $this->ov;
	}


	public function getMesurement() {
		$this->ov = $this->pl->getTemplate('default/tpl.feedback_overview.html');
		for ($x = 1; $x <= 100; $x += 1) {
			$this->ov->setCurrentBlock('line');
			if ($x % 5 == 0) {
				$this->ov->setVariable('INT', $x . '&nbsp;');
				$this->ov->setVariable('LINE_CSS', '_double');
			} else {
				$this->ov->setVariable('INT', '');
			}
			$this->ov->setVariable('WIDTH', 10);
			$this->ov->parseCurrentBlock();
		}
	}


	/**
	 * @param        $type
	 * @param        $width
	 * @param        $value
	 * @param string $title
	 */
	public function parseOverviewBlock($type, $width, $value, $title = '') { //$width, $title, $href, $css = '', $start_value = NULL) {
		switch ($type) {
			case 'blank':
				$this->ctrl->setParameter($this, 'feedback_id', NULL);
				$this->ctrl->setParameter($this, 'start_value', $value);
				$href = $this->ctrl->getLinkTarget($this, 'addNew');
				$css = '_blank';
				$title = $this->pl->txt('insert_feedback');
				break;
			case 'fb':
				$this->ctrl->setParameter($this, 'feedback_id', $value);
				$href = $this->ctrl->getLinkTarget($this, 'editFeedback');
				break;
		}
		$this->total += $width;
		$this->ov->setCurrentBlock('fb');
		$this->ov->setVariable('FEEDBACK', $title);
		$this->ov->setVariable('HREF', $href);
		$this->ov->setVariable('WIDTH', $width);
		$this->ov->setVariable('CSS', $css);
		$this->ov->parseCurrentBlock();
	}


	/**
	 * @param ilSelfEvaluationDataset $dataset
	 * @param bool                    $show_charts
	 *
	 * @return string
	 */
	public static function _getPresentationOfFeedback(ilSelfEvaluationDataset $dataset, $show_charts = true) {
		$pl = new ilSelfEvaluationPlugin();
		$pl->updateLanguages();
		$tpl = $pl->getTemplate('default/tpl.feedback.html');
		foreach ($dataset->getFeedbacksPerBlock() as $block_id => $fb) {
			// Chart
			$tpl->setCurrentBlock('feedback');
			if ($show_charts) {
				$chart = new ilChart('fb_' . $block_id, self::WIDTH - 15, round((self::WIDTH - 50) / 2, 0));
				$legend = new ilChartLegend();
				$chart->setLegend($legend);
				$chart->setYAxisToInteger(true);
				$data = new ilChartData('bars');
				$data->setBarOptions(0.8, 'center');
				$ticks = array();
				$x = 1;
				foreach ($dataset->getDataPerBlock($block_id) as $qst_id => $value) {
					$qst = new ilSelfEvaluationQuestion($qst_id);
					$data->addPoint($qst_id, $value);
					$ticks[$qst_id] = $qst->getTitle() ? $qst->getTitle() : $pl->txt('question') . ' ' . $x;
					$x ++;
				}
				$chart->setTicks($ticks, false, true);
				$chart->addData($data);
				$tpl->setVariable('CHART', $chart->getHTML());
			}
			// Template
			$block = new ilSelfEvaluationBlock($block_id);
			$tpl->setVariable('BLOCK_TITLE', $block->getTitle());
			$tpl->setVariable('WIDTH', self::WIDTH);
			$tpl->setVariable('FEEDBACK_TITLE', $fb->getTitle());
			$tpl->setVariable('FEEDBACK_BODY', $fb->getFeedbackText());
			$tpl->parseCurrentBlock();
		}

		return $tpl->get();
	}
}

?>