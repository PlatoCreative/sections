<?php

namespace Sections\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\Forms\Form;

/**
 * GridFieldAddNewMultiClassHandlerExtension
 *
 * @package silverstripe
 * @subpackage mysite
 */
class GridFieldAddNewMultiClassHandlerExtension extends Extension
{
    /**
     * @param Form $form
     */
    public function updateItemEditForm(Form $form)
    {
        // NOTE: this extension is applied to new item edit form only
        $record = $form->getRecord();
        if ($record instanceof SectionObject) {
            // prevent lost changes popup message when creating a new section
            $form->addExtraClass('discardchanges');
        }
    }
}
