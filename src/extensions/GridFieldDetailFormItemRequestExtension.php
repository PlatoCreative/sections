<?php

namespace Sections\Extensions;

use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\Core\Extension;
use SilverStripe\Control\Controller;
use SilverStripe\ORM\CMSPreviewable;
use SilverStripe\CMS\Controllers\SilverStripeNavigator;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormAction;

class GridFieldDetailFormItemRequestExtension extends Extension
{
    /**
     * Updates the edit form to inject the preview panel controls if needed
     * i.e. if the class being edited implements CMSPreviewable
     *
     * @param SilverStripe\Forms\Form $form to be modified by reference
     */
    public function updateItemEditForm(&$form)
    {
        $owner = $this->owner;
        $fields = $form->Fields();
        if ($owner->getRecord() instanceof CMSPreviewable && !$fields->fieldByName(SilverStripeNavigator::class)) {
            $template = Controller::curr()
                ->getTemplatesWithSuffix('_SilverStripeNavigator');
            $navigator = SilverStripeNavigator::create($owner->record);
            $field = LiteralField::create(
                SilverStripeNavigator::class,
                $navigator->renderWith($template)
            )
            ->setAllowHTML(true);
            $fields->push($field);
            $form->addExtraClass('cms-previewable')
                ->removeExtraClass('cms-panel-padded center');
        }
    }
}
