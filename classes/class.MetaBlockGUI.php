<?php

declare(strict_types=1);
use ilub\plugin\SelfEvaluation\Block\BlockGUI;
use ilub\plugin\SelfEvaluation\Block\Meta\MetaBlock;

class MetaBlockGUI extends BlockGUI
{
    /**
     * @var MetaBlock
     */
    protected \ilub\plugin\SelfEvaluation\Block\Matrix\QuestionBlock|MetaBlock $object;

    public function __construct(
        ilDBInterface $db,
        ilGlobalTemplateInterface $tpl,
        ilCtrl $ilCtrl,
        ilAccessHandler $access,
        ilSelfEvaluationPlugin $plugin,
        ilObjSelfEvaluationGUI $parent
    ) {
        parent::__construct($db, $tpl, $ilCtrl, $access, $plugin, $parent);
        $this->object = new MetaBlock($this->db, $parent->http->query()->retrieve('block_id', $parent->refinery->kindlyTo()->int()));
        $this->object->setParentId($this->parent->getObjId());
    }
}
