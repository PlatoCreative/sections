<?php

namespace Sections\Controllers;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use gorriecoe\HTMLTag\View\HTMLTag;

/**
 * @package silverstripe-sections
 */
class SectionObjectController extends Controller
{
    /**
     * @var $section
     */
    protected $section;

    /**
     * @var $Pos
     */
    protected $Pos;

    /*
     * @param Section $section
     */
    public function __construct($section = null)
    {
        if ($section) {
            $this->section = $section;
            $this->failover = $section;
        }

        parent::__construct();
    }

    public function doInit()
    {
        parent::doInit();
    }

    public function index()
    {
        return;
    }

    /**
     * @param string $action
     *
     * @return string
     */
    public function Link($action = null)
    {
        $id = ($this->section) ? $this->section->ID : null;
        $segment = Controller::join_links('section', $id, $action);

        if ($page = Director::get_current_page()) {
            return $page->Link($segment);
        }

        return Controller::curr()->Link($segment);
    }

    /**
     * Access current page scope from section templates with $CurrentPage
     *
     * @return Controller
     */
    public function getCurrentPage()
    {
        return Controller::curr();
    }

    public function getSection()
    {
        return $this->section;
    }

    public function data()
    {
        return $this->section;
    }

    public function getPos()
    {
        return $this->Pos;
    }

    public function getFirst()
    {
        return ($this->Pos == 1);
    }

    public function First()
    {
        return ($this->Pos == 1);
    }

    public function getLast()
    {
        return ($this->Pos == $this->TotalItems);
    }

    public function Last()
    {
        return ($this->Pos == $this->TotalItems);
    }

    public function getMiddle()
    {
        return ($this->Pos != 1 && $this->Pos != $this->TotalItem);
    }

    public function Middle()
    {
        return ($this->Pos != 1 && $this->Pos != $this->TotalItem);
    }

    public function TitleSemantic()
    {
        $section = $this->section;
        if ($section->TitleHide) {
            return;
        }
        switch ($section->TitleSemantic) {
            case 'hide':
                return;
            case 'auto':
                return ($this->First()) ? 'h1' : 'h2';
            default:
                return $section->TitleSemantic;
        }
    }

    public function Title()
    {
        $section = $this->section;
        if ($this->TitleForceHide || !$this->TitleSemantic()) {
            return;
        }
        return HTMLTag::create(
            $section->obj('Title')->Highlight(),
            $this->TitleSemantic()
        )
        ->setPrefix($section->Class);
    }
}
