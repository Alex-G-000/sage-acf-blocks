# **Tone Sage ACF Blocks**

Easily add pre-built block templates, and register your own blocks in Sage 10, ACF and Wordplate Extended ACF.

**NOTE:** This repo is a little bit of a mess. Give me time and I'll tidy it up, but the important bit is it works!

# **Requirements**
- Sage 10
- ACF Pro
- WP CLI

# **Installation**
Add the repo to the "repositories" list in composer.json located in the theme.  

    {
        "type": "vcs",
        "url": "git@bitbucket.org:toneagency/sage-acf-blocks.git"
    }


Install the composer package (in the theme folder).

    composer require toneagency/sage-acf-blocks

Add the package to the cached package manifest.

    wp acorn package:discover

Create your first block in `resources/block-views`


# **Block Structure**

Blocks are registered by placing a new .blade.php file inside the block-views directory in your theme.

## Block HTML
### sage-theme/resources/block-views
Use the example below to configure your block, and provide correct paths for:

* EnqueueStyle 
    * Add any block front-end CSS to this file.
    * **Optional:** Remove this line to skip this option.
* EnqueueScript
    * Add any block front-end JS to this file.
    * **Optional:** Remove this line to skip this option.
* CustomFields
    * Add your block custom fields to this file as described below.
    * **Optional:** Remove this line to skip this option.
* Controller
    * Add your block model and logic to this file.
    * You can ignore controllers and just add get_field() directly to the view.
    * **Optional:** Remove this line to skip this option.

**Note**: Do not put spaces in the "Title" field for now, there's a bug.

#### Example (Minimal)
```
{{--
  Title: Example Block
  Category: blocks
  Description: Block description
  Keywords: example block keyword tags
  Align: wide
  EnqueueStyle: styles/blocks/example-block.css
  EnqueueScript: scripts/blocks/example-block.js
--}}
```

#### Example (Full)
```
{{--
  Title: Example Block
  Category: blocks
  Description: Block description
  Keywords: example block keyword tags
  Align: wide
  EnqueueStyle: styles/blocks/example-block.css
  EnqueueScript: scripts/blocks/example-block.js
  CustomFields: blocks/example-block.php
  Controller: example.php
--}}
```


## Block Custom Fields
### sage-theme/resources/custom-fields/blocks
WordPlate Extended ACF PHP custom field registrations can go here.

#### Example
```
<?php
use \WordPlate\Acf\Fields\Accordion;
use \WordPlate\Acf\Fields\Repeater;
use \WordPlate\Acf\Fields\Text;
use \WordPlate\Acf\Fields\WysiwygEditor;

return [
    Accordion::make('Accordion Questions', 'acc_questions')->multiExpand(),
        Repeater::make('Accordion Questions', 'accordion_questions')->fields([
            Text::make('Accordion Heading', 'accordion_heading'),
            WysiwygEditor::make('Accordion Content', 'accordion_copy')
        ])
        ->buttonLabel('Add Question')
        ->layout('block'),
    Accordion::make('EndpointOne')->multiExpand()->endpoint()
];
```


## Block JS / CSS
### resources/assets/scripts/blocks
Your custom block scripts have to go in this folder to be compiled properly.

**Note:** You are responsible for `npm install`'ing any packages that the scripts in each block might use. A complete list can be found here.

### resources/assets/styles/blocks
Your custom block styles have to go in this folder to be compiled properly.


## Block Controllers
You can optionally attach a controller to the block that you can put logic in and pass fields to the view.

#### Inside the view file:
```
Controller: block-slug.php
```

#### Example block-models/block-slug.php:
```
return [
    'title' => get_field('title')
]
```

This example will make $title available in 
