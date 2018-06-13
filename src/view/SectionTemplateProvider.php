<?php

use SilverStripe\View\TemplateGlobalProvider;
use SilverStripe\Control\Director;
use gorriecoe\HTMLTag\View\HTMLTag;

/**
 * Adds Section template variables for one off use on a page.
 * This differs from <% include Section %> by providing default data to the section.
 *
 * @package silverstripe-sections
 */
class SectionTemplateProvider implements TemplateGlobalProvider
{
    /**
     * @return array|void
     */
    public static function get_template_global_variables()
    {
        return array(
            'Section' => 'Section'
        );
    }

    public static function Section($section, $datastring = '')
    {
        $section = singleton($section);
        $page = Director::get_current_page();
        $data = [];
        foreach (explode(',', $datastring) as $bit) {
            list($key, $value) = explode('=', $bit);
            $key = trim($key);
            $value = trim($value);
            switch ($key) {
                case 'Title':
                    $data[$key] = HTMLTag::create($page->obj($value), 'h2');
                    break;
                default:
                    $data[$key] = trim($value);
                    break;
            }
        }
        return $section->customise($data)->render();
    }
}
