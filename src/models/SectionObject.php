<?php

namespace Sections\Models;

use SilverStripe\Core\Convert;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TabSet;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\CheckboxField_Readonly;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\TextField;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Control\Controller;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\CMSPreviewable;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Security\PermissionProvider;
use SilverStripe\Versioned\Versioned;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\CMS\Controllers\CMSPageEditController;
use SilverStripe\Control\Director;
use gorriecoe\DataObjectHistory\extensions\DataObjectHistory;
use Sections\Models\SectionArea;
use Sections\Controllers\SectionObjectController;

/**
 * @package silverstripe-sections
 */
class SectionObject extends DataObject implements
    PermissionProvider,
    CMSPreviewable
{
    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'SectionObject';

    /**
     * Singular name for CMS
     * @var string
     */
    private static $singular_name = 'Section';

    /**
     * Plural name for CMS
     * @var string
     */
    private static $plural_name = 'Sections';

    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'Title' => 'Varchar(250)',
        'TitleSemantic' => 'Enum("auto,h1,h2,h3,h4,h5,h6,p,hide","auto")',
        'TitleHide' => 'Boolean',
        'Sort' => 'Int',
        'MobileSort' => 'Int',
        'Layout' => 'Text',
        'Color' => 'Text',
        'ShowForMobile' => 'Boolean',
        'ShowForDesktop' => 'Boolean'
    ];

    /**
     * Has_one relationship
     * @var array
     */
    private static $has_one = [
        'SectionArea' => SectionArea::class,
    ];

    /**
     * Summary Fields
     * @return array
     */
    private static $summary_fields = [
        'Title' => 'Title',
        'i18n_singular_name' => 'Type'
    ];

    private static $searchable_fields = [
        'Title'
    ];
        
    /**
     * Add default values to database
     * @var array
     */
    private static $defaults = [
        'ShowForMobile' => true,
        'ShowForDesktop' => true
    ];

    /**
     * Defines extension names and parameters to be applied
     * to this object upon construction.
     * @var array
     */
    private static $extensions = [
        Versioned::class,
        DataObjectHistory::class
    ];

    /**
     * @var boolean
     */
    private static $versioned_gridfield_extensions = true;

    /**
     * Defines db fields that are translatable.
     * @var array
     */
    private static $translate = [
        'Title'
    ];

    /**
     * Defines a default list of filters for the search context
     * @var array
     */
    private static $site_searchable_fields = [
        'Title'
    ];

    /**
     * Defines if the title of the section will be forced to hide from public display.
     * @var boolean
     */
    private static $title_force_hide = false;

    /**
     * Ensures that the methods are wrapped in the correct type and
     * values are safely escaped while rendering in the template.
     * @var array
     */
    private static $casting = [
        'ClassAttr' => 'HTMLFragment',
        'AnchorAttr' => 'HTMLFragment',
        'TargetAttr' => 'HTMLFragment',
        'SourceOrderAttr' => 'HTMLFragment'
    ];

    /**
     * @var SectionObjectController
     */
    protected $controller;

    /**
     * CMS Fields
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = FieldList::create();
        $fields->push(
            TabSet::create(
                'Root',
                Tab::create('Main')
            )
            ->setTitle(_t('Section.TABMAIN', 'Content')),
            TabSet::create(
                'Root',
                Tab::create('Settings')
            )
            ->setTitle(_t('Section.TABSETTINGS', 'Settings'))
        );

        if ($this->TitleForceHide) {
            $hideTitleField = CheckboxField_Readonly::create(
                'TitleHidden',
                _t('Section.HIDE', 'Hide from public display')
            )
            ->setValue(true);
        } else {
            $hideTitleField = CheckboxField::create(
                'TitleHide',
                _t('Section.HIDE', 'Hide from public display')
            );
        }


        $fields->addFieldsToTab(
            'Root.Main',
            array(
                CompositeField::create(
                    TextareaField::create(
                        'Title',
                        ''
                    )
                    ->setRows(1),
                    $hideTitleField
                )
                ->setTitle('Title')
            )
        );


        $fields->addFieldsToTab(
            'Root.Settings',
            array(
                LiteralField::create(
                    'AdvancedWarning',
                    "<p class=\"message error\">" .
                    _t('Section.ADVANCEDWARNING', 'This area is intended for advanced usage.  AVOID modifications if you are uncertain.') .
                    "</p>"
                )
            )
        );

        if (!$this->TitleForceHide) {
            $fields->addFieldsToTab(
                'Root.Settings',
                array(
                    DropdownField::create(
                        'TitleSemantic',
                        _t('Section.TITLESEMANTIC', 'Title semantics'),
                        singleton('Section')->dbObject('TitleSemantic')->enumValues()
                    )
                )
            );
        }

        if ($this->Layouts) {
            $fields->addFieldsToTab(
                'Root.Main',
                array(
                    DropdownField::create(
                        'Layout',
                        _t('Section.LAYOUT', 'Select a layout'),
                        $this->Layouts
                    )
                    ->setEmptyString('Default')
                )
            );
        }

        if ($this->Colors) {
            $fields->addFieldsToTab(
                'Root.Main',
                array(
                    DropdownField::create(
                        'Color',
                        _t('Section.COLORSCHEME', 'Select a colour scheme'),
                        $this->Colors
                    )
                )
            );
        }

        if ($this->Title) {
            $fields->addFieldToTab(
                'Root.Main',
                TextField::create(
                    'AnchorPreview',
                    _t('Section.ANCHORPREVIEW', 'Anchor')
                )
                ->setValue('#' . $this->Anchor)
                ->setReadonly(true)
            );
        }

        $this->extend('updateCMSFields', $fields);

        return $fields;
    }

    /**
     * Gets layouts defined with in config.yml
     * @return array
     */
    public function getLayouts()
    {
        return $this->config()->get('layouts');
    }

    /**
     * Gets colors defined with in config.yml
     * @return array
     */
    public function getColors()
    {
        return $this->config()->get('colors');
    }

    /**
     * Gets defined hide_title in config.yml
     * @return array
     */
    public function getTitleForceHide()
    {
        return $this->Config()->get('title_force_hide');
    }

    /**
     * @return string
     */
    public function getMimeType()
    {
        return 'text/html';
    }

    /**
     * Gets defined site searchable fields.
     * @return array
     */
    public function getSiteSearchableFields()
    {
        $config = $this->config()->get('site_searchable_fields');
        return isset($config) ? $config : array();
    }

    /**
     * Applies class to section in template.
     *
     * @return string
     */
    public function getClass()
    {
        $class = [];
        if ($baseClass = $this->config()->get('base_class')) {
            $class[]= $baseClass;
        } else {
            $class[]= $this->obj('ClassName')->Hyphenate();
        }
        return implode('--', $class);
    }

    /**
     * Applies classes to section in template.
     *
     * @return string $classes
     */
    public function getClassAttr()
    {
        return 'class="' . $this->Class . '"';
    }

    /**
     * Applies anchor to section in template.
     *
     * @return string
     */
    public function getAnchor()
    {
        return $this->obj('Title')->LinkFriendly();
    }

    /**
     * Applies anchor to section in template.
     *
     * @return string
     */
    public function getAnchorAttr()
    {
        return 'id="' . $this->Anchor . '"';
    }

    /**
     * Returns sort ordering
     *
     * @return string
     */
    public function getSortOrder()
    {
        $mobileclass = 'small';
        $desktopclass = 'medium';
        if ($this->SectionArea()->AllowMobileSort) {
            $json = [];
            $json[$mobileclass] = $this->getField('MobileSort') + 1;
            $json[$desktopclass]= $this->getField('Sort') + 1;
            return Convert::array2json($json);
        }
    }

    /**
     * Returns sort ordering
     *
     * @return string
     */
    public function getDisplayClasses()
    {
        $class = [];
        if ($this->SectionArea()->AllowMobileSort) {
            if ($this->ShowForDesktop) {
                if (!$this->ShowForMobile) {
                    $class[] = 'show-for-medium';
                }
            } else {
                if ($this->ShowForMobile) {
                    $class[] = 'hide-for-medium';
                }
            }
        }

        return implode(' ', $class);
    }

    /**
     * Alias for AnchorAttr
     *
     * @return string
     */
    public function getTargetAttr()
    {
        return $this->AnchorAttr;
    }

    public function render()
    {
        return $this->renderWith($this->RenderTemplates, $this->Controller);
    }

    /**
     * Returns a list of rendering templates
     *
     * @return array
     */
    public function getRenderTemplates()
    {
        $class = $this->Classname;
        $page = $this->CurrentPage->Data()->ClassName;
        $templates = [
            'type' => 'Sections'
        ];
        if (is_object($class)) $class = get_class($class);
        if (!is_subclass_of($class, DataObject::class)) {
            throw new InvalidArgumentException("$class is not a subclass of DataObject");
        }
        while ($next = get_parent_class($class)) {
            if ($layout = $this->Layout) {
                $templates['templates'][] = $class . '_' . $page . '_' . $layout;
                $templates['templates'][] = $class . '_' . $layout;
            }
            $templates['templates'][] = $class . '_' . $page;
            $templates['templates'][] = $class;
            if ($next == DataObject::class) {
                return $templates;
            }

            $class = $next;
        }
    }

    /**
     * @throws Exception
     *
     * @return SectionObjectController
     */
    public function getController()
    {
        if ($this->controller) {
            return $this->controller;
        }
        foreach (array_reverse(ClassInfo::ancestry($this->ClassName)) as $sectionClass) {
            if ($sectionClass == SectionObject::class) {
                $controllerClass = SectionObjectController::class;
                break;
            }
            $controllerClass = Injectable::singleton($sectionClass)->config()->controller_class;
            if ($controllerClass && class_exists($controllerClass)) {
                break;
            }
            $controllerClass = $sectionClass . 'Controller';
            if (class_exists($controllerClass)) {
                break;
            }
        }
        if (!class_exists($controllerClass)) {
            throw new Exception("Could not find controller class for $this->classname");
        }

        $this->controller = Injector::inst()->create($controllerClass, $this);
        $this->controller->doInit();

        return $this->controller;
    }

    /**
     * @return SiteTree|null
     */
    public function getPage()
    {
        $area = $this->SectionArea();
        if ($area instanceof SectionArea && $area->exists()) {
            return $area->Page();
        }
        return Director::get_current_page();
    }

    /**
     * @return SiteTree|null
     */
    public function getCurrentPage()
    {
        return $this->Page;
    }

    public function getTitleAndClass()
    {
        return $this->Title . ' (' . $this->i18n_singular_name() . ')';
    }

    /**
     * @return null|string
     */
    public function CMSEditLink()
    {
        $slug = $this->SectionArea()->Slug;
        $page = $this->CurrentPage;
        if (!$page) {
            return null;
        }
        $editLinkPrefix = '';
        if (!$page instanceof SiteTree && method_exists($page, 'CMSEditLink')) {
            $editLinkPrefix = Controller::join_links(
                $page->CMSEditLink(),
                'ItemEditForm'
            );
        } else {
            $editLinkPrefix = Controller::join_links(
                singleton(CMSPageEditController::class)->Link('EditForm'),
                $page->ID
            );
        }
        $link = Controller::join_links(
            $editLinkPrefix,
            'field',
            $slug,
            'item',
            $this->ID,
            'edit'
        );
        $this->extend('updateCMSEditLink', $link);
        return $link;
    }

    /**
     * @param string|null $action
     * @return string
     */
    public function Link($action = null)
    {
        if ($page = $this->Page) {
            $link = $page->Link($action) . '#' . $this->getAnchor();
            $this->extend('updateLink', $link);
            return $link;
        }
        return null;
    }

    /**
     * @return null|string
     */
    public function getEditLink()
    {
        return $this->CMSEditLink();
    }

    /**
     * @param string|null $action
     * @return string
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \SilverStripe\ORM\ValidationException
     */
    public function PreviewLink($action = null)
    {
        $action = $action . '?SectionPreview=' . mt_rand();
        $link = $this->Link($action);
        $this->extend('updatePreviewLink', $link);
        return $link;
    }

    /**
     * Event handler called when the page is duplicated
     * @param Section $originalSection The original section from the original page.
     */
    public function onPageDuplicate($originalSection)
    {

    }

    /**
     * Return a map of permission codes to add to the dropdown shown in the Security section of the CMS
     * @return array
     */
    public function providePermissions()
    {
        $permissions = array();
        $sections = ClassInfo::subclassesFor('Section');
        unset($sections['Section']);
        foreach ($sections as $key => $value) {
            $classNameUpper = strtoupper($key);
            $name = singleton($key)->i18n_singular_name();
            $permissions[$classNameUpper . 'CREATE'] = array(
                'name' => 'Create any \'' . $name . '\' section',
                'category' => 'Section \'' . $name . '\' permissions'
            );
            $permissions[$classNameUpper . 'EDIT'] = array(
                'name' => 'Edit any \'' . $name . '\' section',
                'category' => 'Section \'' . $name . '\' permissions'
            );
            $permissions[$classNameUpper . 'DELETE'] = array(
                'name' => 'Delete any \'' . $name . '\' section',
                'category' => 'Section \'' . $name . '\' permissions'
            );
        }
        return $permissions;
    }

    /**
     * DataObject view permissions
     * @param Member $member
     * @return boolean
     */
    public function canView($member = null)
    {
        return true;
    }

    /**
     * DataObject create permissions
     * @param Member $member
     * @param array $context Additional context-specific data which might
     * affect whether (or where) this object could be created.
     * @return boolean
     */
    public function canCreate($member = null, $context = [])
    {
        return Permission::check(strtoupper($this->ClassName) . 'CREATE', 'any', $member);
    }

    /**
     * DataObject edit permissions
     * @param Member $member
     * @return boolean
     */
    public function canEdit($member = null)
    {
        return Permission::check(strtoupper($this->ClassName) . 'EDIT', 'any', $member);
    }

    /**
     * DataObject edit permissions
     * @param Member $member
     * @return boolean
     */
    public function canReorder($member = null)
    {
        return true;
    }

    /**
     * DataObject delete permissions
     * @param Member $member
     * @return boolean
     */
    public function canDelete($member = null)
    {
        return Permission::check(strtoupper($this->ClassName) . 'DELETE', 'any', $member);
    }
}
