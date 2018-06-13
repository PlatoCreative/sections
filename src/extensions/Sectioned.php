<?php

namespace Sections\Extensions;

use SilverStripe\Forms\CheckboxField;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\ClassInfo;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Versioned\VersionedGridFieldItemRequest;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\Forms\GridField\GridFieldPageCount;
use SilverStripe\Forms\GridField\GridFieldPaginator;
use SilverStripe\Forms\GridField\GridFieldSortableHeader;
use Symbiote\GridFieldExtensions\GridFieldAddNewMultiClass;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;
use Symbiote\GridFieldExtensions\GridFieldEditableColumns;
use Sections\Models\SectionArea;
use UncleCheese\DisplayLogic\Forms\Wrapper;

/**
 * @package silverstripe-sections
 */
class Sectioned extends DataExtension
{
    /**
     * Ensures that the methods are wrapped in the correct type and
     * values are safely escaped while rendering in the template.
     * @var array
     */
    private static $casting = [
        'Area' => 'HTMLFragment'
    ];

    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'SectionIndex' => 'HTMLText'
    ];

    /**
     * Has_many relationship
     * @var array
     */
    private static $has_many = [
        'SectionAreas' => SectionArea::class
    ];

    /**
     * @var array
     */
    private static $owns = [
        'SectionAreas'
    ];

    /**
     * CMS Fields
     * @return FieldList
     */
    public function updateCMSFields(FieldList $fields)
    {
        $owner = $this->owner;

        foreach ($owner->Areas as $slug => $title) {
            if ($area = $owner->SectionAreas()->Find('Slug', $slug)) {
                $GridFieldAddNewMultiClass = new GridFieldAddNewMultiClass();
                $GridFieldAddNewMultiClass->setClasses($owner->AllowedSections);

                $config = GridFieldConfig_RelationEditor::create()
                    ->removeComponentsByType([
                        GridFieldAddNewButton::class,
                        GridFieldSortableHeader::class,
                        GridFieldDeleteAction::class,
                        GridFieldPaginator::class,
                        GridFieldPageCount::class,
                        GridFieldVersionedState::class,
                        VersionedGridFieldState::class,
                        GridFieldAddExistingAutocompleter::class
                    ])
                    ->addComponent(new GridFieldOrderableRows('Sort'))
                    ->addComponent($GridFieldAddNewMultiClass)
                    ->addComponent(new GridFieldEditableColumns());

                $config->getComponentByType(GridFieldEditableColumns::class)->setDisplayFields([
                    'ShowForDesktop' => [
                        'title' => _t(__CLASS__ . '.SHOWFORDESKTOP', 'Show for desktop'),
                        'callback' => function($record, $column, $grid) {
                            return CheckboxField::create(
                                'ShowForDesktop',
                                _t(__CLASS__ . '.SHOWFORDESKTOP', 'Show for desktop')
                            );
                        }
                    ]
                ]);

                $mobileConfig = GridFieldConfig_RelationEditor::create()
                    ->removeComponentsByType([
                        GridFieldAddNewButton::class,
                        GridFieldSortableHeader::class,
                        GridFieldDeleteAction::class,
                        GridFieldPaginator::class,
                        GridFieldPageCount::class,
                        GridFieldVersionedState::class,
                        VersionedGridFieldState::class,
                        GridFieldAddExistingAutocompleter::class
                    ])
                    ->addComponent(new GridFieldOrderableRows('MobileSort'))
                    ->addComponent(new GridFieldEditableColumns());

                $mobileConfig->getComponentByType(GridFieldEditableColumns::class)->setDisplayFields([
                    'ShowForMobile' => [
                        'title' => _t(__CLASS__ . '.SHOWFORMOBILE', 'Show for mobile'),
                        'callback' => function($record, $column, $grid) {
                            return CheckboxField::create(
                                'ShowForMobile',
                                _t(__CLASS__ . '.SHOWFORMOBILE', 'Show for mobile')
                            );
                        }
                    ]
                ]);

                $fields->addFieldsToTab(
                    'Root.' . $title,
                    [
                        GridField::create(
                            $area->Slug,
                            '',
                            $area->Sections(),
                            $config
                        ),
                        OptionsetField::create(
                            $area->Slug . '__AllowMobileSort',
                            _t('Section.MOBILESORT', 'Allow mobile section order'),
                            [
                                true => _t('Section.MOBILESORTYES', 'Yes'),
                                false => _t('Section.MOBILESORTNO', 'No')
                            ],
                            $area->AllowMobileSort
                        ),
                        Wrapper::create(
                            GridField::create(
                                $area->Slug .  'Mobile',
                                _t('Section.MOBILESORTGRID', 'Mobile section order'),
                                $area->Sections(),
                                $mobileConfig
                            )
                        )
                        ->displayIf($area->Slug . '__AllowMobileSort')->isEqualTo(true)->end()
                    ]

                );
            }
        }

        return $fields;
    }

    public function getAllowedSections()
    {
        $owner = $this->owner;
        $config = $owner->Config();
        $sections = array_values(ClassInfo::subclassesFor('Section'));
        $allowed_sections = $config->get('allowed_sections');
        $excluded_sections = $config->get('excluded_sections');
        foreach ($sections as $key => $value) {
            if ($value == 'Section') {
                unset($sections[$key]);
            }
            if (isset($allowed_sections) && !in_array($value, $allowed_sections)) {
                unset($sections[$key]);
            }
            if (isset($excluded_sections) && in_array($value, $excluded_sections)) {
                unset($sections[$key]);
            }
        }

        return $sections;
    }

    public function getAreas()
    {
        $owner = $this->owner;
        if (!$areas = $owner->config()->areas) {
            // Assign default area if none exist
            $areas = [
                'Sections' => 'Main'
            ];
        }
        return $areas;
    }

    public function Area($slug = 'Sections', $offset = 0)
    {
        $owner = $this->owner;
        if (array_key_exists($slug, $owner->Areas) && $area = $owner->SectionAreas()->Find('Slug', $slug)) {
            $sections = [];
            $pos = 0;
            $total = $area->Sections()->Count();
            foreach ($area->Sections()->Sort('Sort ASC') as $section) {
                $pos++;
                $controller = $section->Controller;
                $controller->Pos = $pos + $offset;
                $controller->TotalItems = $total + $offset;
                $sections[] = $section->render();
            }
            return '<div class="sections">' . implode('', $sections) . '</div>';
        }
    }

    /**
     * Event handler called before writing to the database.
     */
    public function onBeforeWrite()
    {
        $owner = $this->owner;
        $owner->buildAreas();
        foreach ($owner->Areas as $slug => $title) {
            if ($area = $owner->SectionAreas()->Find('Slug', $slug)) {
                if (isset($_POST[$area->Slug . '__AllowMobileSort'])) {
                    $area->AllowMobileSort = $_POST[$area->Slug . '__AllowMobileSort'];
                    $area->Write();
                }
            }
        }
        parent::onBeforeWrite();
    }

    /**
     * Create areas on dev build
     */
    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();
        foreach (SiteTree::get() as $sitetree) {
            if ($sitetree->hasMethod('buildAreas')) {
                $sitetree->buildAreas();
            }
        }
    }

    public function buildAreas()
    {
        $owner = $this->owner;
        foreach ($owner->Areas as $slug => $title) {
            if (!$area = $owner->SectionAreas()->Find('Slug', $slug)) {
                $area = SectionArea::create();
            }
            $area->Title = $title;
            $area->Slug = $slug;
            $area->PageID = $owner->ID;
            $area->write();
        }
    }
}
