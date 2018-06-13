<?php

namespace Sections\Controllers;

use SilverStripe\Core\Extension;

/**
 * @package silverstripe-sections
 */
class SectionContentControllerExtension extends Extension
{
    /**
     * Defines methods that can be called directly
     * @var array
     */
    private static $allowed_actions = [
        'handleSection' => true
    ];

    /**
     * Handles section attached to a page
     * Assumes URLs in the following format: <URLSegment>/section/<section-id>.
     *
     * @return RequestHandler
     */
    public function handleSection()
    {
        $owner = $this->owner;
        $request = $owner->getRequest();
        if ($ID = $request->param('ID')) {
            $sections = $owner->data()->AllCurrentSections;
            if ($section = $sections->find('ID', $ID)) {
                return $section->getController();
            }
        }
    }
}
