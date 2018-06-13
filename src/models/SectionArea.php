<?php

namespace Sections\Models;

use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;
use SilverStripe\CMS\Model\SiteTree;
use Sections\Models\SectionObject;

/**
 * @package silverstripe-sections
 */
class SectionArea extends DataObject
{
    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'SectionArea';

    /**
     * Singular name for CMS
     * @var string
     */
    private static $singular_name = 'Area';

    /**
     * Plural name for CMS
     * @var string
     */
    private static $plural_name = 'Areas';

    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'Title' => 'Varchar(255)',
        'Slug' => 'Varchar(255)',
        'SearchIndex' => 'HTMLText',
        'AllowMobileSort' => 'Boolean'
    ];

    /**
     * Has_one relationship
     * @var array
     */
    private static $has_one = [
        'Page' => SiteTree::class,
    ];

    /**
     * Has_many relationship
     * @var array
     */
    private static $has_many = [
        'Sections' => SectionObject::class
    ];

    /**
     * @var array
     */
    private static $owns = [
        'Sections'
    ];

    /**
     * @var array
     */
    private static $cascade_deletes = [
        'Sections'
    ];

    /**
     * @var array
     */
    private static $extensions = [
        Versioned::class
    ];

    /**
     * Event handler called before writing to the database.
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        $this->saveSearchIndex();
    }

    public function saveSearchIndex()
    {
        $sectionIndex = array();
        foreach ($this->Sections() as $section) {
            $searchablefields = $section->SiteSearchableFields;
            foreach ($searchablefields as $searchablefield) {
                $sectionIndex[] = $section->{$searchablefield};
            }
        }
        $this->SearchIndex = implode(' ', $sectionIndex);
    }
}
