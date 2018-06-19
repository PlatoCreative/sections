# Sections

## Installation
Composer is the recommended way of installing SilverStripe modules.
```
composer require plato-creative/sections
```

## Requirements

- silverstripe/cms ^4.0

## Maintainers

- [Plato Creative](web@platocreative.co.nz)

## Documentation

### Add sections to a page

#### Standard section area
Add a sections area to a page type.

```php
class MyCustomPage extends Page
{
    private static $extensions = array(
        'Sectioned'
    )
}
```
By default this will add a sections area that can be accessed via page templates with `{$Area}`

#### Multiple section areas
To add multiple sections to a page type.

```php
class MyCustomPage extends Page
{
    private static $extensions = array(
        'Sectioned'
    )

    private static $areas = array(
        'Sections' => 'Sections' // make sure you add standard sections area in other it will be deleted
        'OtherSections' => 'Other sections cms title'
    )
}
```
This can be accessed in the page template with:
```
<div>
    {$Area} <%-- standard sections --%>
</div>
<div>
    {$Area('OtherSections')}
</div>
```

#### Limit section types by page type
Set allowed sections.
```php
class MyCustomPage extends Page
{
    private static $extensions = array(
        'Sectioned'
    )

    private static $allowed_sections = array(
        'ContentSection',
        'BannerSection'
    )
}
```
Set excluded sections.
```php
class MyCustomPage extends Page
{
    private static $extensions = array(
        'Sectioned'
    )

    private static $exclude_sections = array(
        'FormSection'
    )
}
```
### Create new section types

```php
class MyCustomSection extends Section
{
    // Defines the name used in the cms such as a new dropdown and gridfield type.
    private static $singular_name = 'My custom name';
    private static $plural_name = 'Sections';

    // Define db fields
    private static $db = array(
        'Content' => 'HTMLText'
    );

    // Define a list db fields that can be searched via the frontend
    private static $site_searchable_fields = array(
        'Content'
    );

    // Defines available layouts for this section selectable in the cms
    private static $layouts = array(
        'left-text' => 'Left text',
        'right-text' => 'Right text',
        'center' => 'Center',
    );

    // Defines available color schemes for this section selectable in the cms
    private static $colors = array(
        'black' => 'White text on black',
        'blue' => 'White text on blue background'
    );

    // Defines a custom css class for this section
    private static $base_class = 'my-custom-css-class';

    // Defines if the title of the section will be forced to hide from public display.
    private static $title_force_hide = true;

    public function getCMSFields()
    {
        $fields = parent::getCMSFields(); // This is required as sections will add its own fields
        $fields->addFieldsToTab(
            "Root.Main",
            array(
                HTMLEditorField::create(
                    'Content',
                    'Content'
                )
            )
        );
        $this->extend('updateCMSFields', $fields);
        return $fields;
    }
}
```

### Add a custom form to a section controller
```php
class MyCustomSectionController extends SectionController
{
    private static $allowed_actions = array(
        'Form'
    );

    public function Form(){
        $fields = FieldList::create(array(
            TextField::create('Name'),
            EmailField::create('Email'),
            TextField::create('Phone'),
            TextAreaField::create('Message')
        ));
        $actions = FieldList::create(
            FormAction::create('submit', 'Send Enquiry')
        );
        return Form::create($this, 'Form', $fields, $actions);
    }

    public function submit($data, $form){
        // process form data as usual
        // ...
        // redirect
        return $this->redirect($this->CurrentPage->Link() . '?contacted=1');
    }
}
```

### Templating
#### File names
Sections will look for the template based on the section section name in you theme template directory.  e.g. MyCustomSection will look for MyCustomSection.ss.

In addition sections will look for templates that have a specific layout appended to it.  e.g. MyCustomSection_left-text.ss

Sections will also look for templates that are specific to a page type.  e.g. MyCustomSection_homepage.ss

Finally sections will look for templates that match both specific layout and page type.  e.g. MyCustomSection_homepage_left-text.ss

##### Filename hierarchy example
Taking the following conditions page classname = HomePage, section classname = MyCustomSection, section layout = left-text and extends MyParentSection we can see the templates that are searched and their priority from first to last.

```
MyCustomSection_homepage_left-text.ss // If not found then find next template.
MyCustomSection_left-text.ss // If not found then find next template.
MyCustomSection_homepage.ss // If not found then find next template.
MyCustomSection.ss // If not found then find next template.
MyParentSection_homepage_left-text.ss // If not found then find next template.
MyParentSection_left-text.ss // If not found then find next template.
MyParentSection_homepage.ss // If not found then find next template.
MyParentSection.ss // If not found then find next template.
Section.ss // Base template.  // If not found then error out.
```

#### Sections template variables
Sections has a few useful variables to help.

`{$Class}`: Returns the class defined by the section object or layout it may have.
```
mycustomsection
```

`{$ClassAttr}`: Returns a class attribute with the class of the section.
```
class="mycustomsection"
```

`{$Color}`: Returns color defined by the section object.

`{$Anchor}`: Returns a html safe string based on the title of the current section.
```
check-out-our-features
```

`{$AnchorAttr}` or `{$TargetAttr}`: Returns a id attribute based on the title of the current section.
```
id="check-out-our-features"
```

`{$Pos}` The current integer position in the area. Will start at 1.

`{$Even}`, `{$Odd}`, `{$First}`, `{$Last}` or `{$Middle}`: Booleans about the position in the area.

`{$CurrentPage}`: Access the current page scope.
```
{$CurrentPage.Title}

<% with CurrentPage %>
    {$Title} - {$Link}
<% end_with %>
```

#### Section template title
By default `$Title` in sections uses [HTMLTag](https://github.com/gorriecoe/silverstripe-htmltag) to wrap a tag with html defined in the cms.
So your template can simplified to this.
```
{$Title}
```
Is the equivalent of
```
<% if Title %>
    <{$TitleSemantic}>
        {$Title}
    </{$TitleSemantic}>
<% end_if %>
```
And returns
```html
<h1>
    This sections title
</h1>
```
