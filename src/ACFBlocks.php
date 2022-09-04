<?php

namespace AGdev\Sage\ACFBlocks;

use Illuminate\Contracts\Container\Container as ContainerContract;
use Roots\Acorn\Sage\ViewFinder;
use Roots\Acorn\View\FileViewFinder;
use Illuminate\Support\Str;

use function Roots\view;

class ACFBlocks
{
    public function __construct(
        ViewFinder $sageFinder,
        FileViewFinder $fileFinder,
        ContainerContract $app
    ) {
        $this->app = $app;
        $this->fileFinder = $fileFinder;
        $this->sageFinder = $sageFinder;
    } 

    public function isSage10()
    {
        return class_exists('Roots\Acorn\Application');
    }

    /**
     * Checks asset path for specified asset.
     *
     * @param string &$path
     *
     * @return void
     */
    public function checkAssetPath(&$path)
    {
        if (preg_match("/^(styles|scripts)/", $path)) {
            $path = $this->isSage10() ? \Roots\asset($path)->uri() : \App\asset_path($path);
        }
    }

    public function blockDirectories() : array
    {
        return ['block-views'];
    }

    public function createBlocks() : void 
    {

        // Global $sage_error so we can throw errors in the typical sage manner

        // Get an array of directories containing blocks
        $directories = apply_filters('sage-acf-gutenberg-blocks-templates', []);
    
        // Check whether ACF exists before continuing
        foreach ($directories as $directory) {
            $dir = $this->isSage10() ? \Roots\resource_path($directory) : \locate_template($directory);
    
            // Sanity check whether the directory we're iterating over exists first
            if (!file_exists($dir)) {
                return;
            }
    
            // Iterate over the directories provided and look for templates
            $template_directory = new \DirectoryIterator($dir);
    
            foreach ($template_directory as $template) {
                if (!$template->isDot() && !$template->isDir()) {
                    // Strip the file extension to get the slug
                    $slug = $this->removeBladeExtension(str_replace('acf-', '', strtolower($template->getFilename())));
                    $fn = $template->getFilename();

                    // If there is no slug (most likely because the filename does
                    // not end with ".blade.php", move on to the next file.
                    if (!$slug) {
                        continue;
                    }
    
                    // Get header info from the found template file(s)
                    $file = str_replace("acf/", "", "${dir}/${fn}");
                    $file_path = file_exists($file) ? $file : '';

                    $file_headers = \get_file_data($file_path, [
                        'title' => 'Title',
                        'description' => 'Description',
                        'category' => 'Category',
                        'icon' => 'Icon',
                        'keywords' => 'Keywords',
                        'mode' => 'Mode',
                        'align' => 'Align',
                        'post_types' => 'PostTypes',
                        'supports_align' => 'SupportsAlign',
                        'supports_anchor' => 'SupportsAnchor',
                        'supports_mode' => 'SupportsMode',
                        'supports_jsx' => 'SupportsInnerBlocks',
                        'supports_align_text' => 'SupportsAlignText',
                        'supports_align_content' => 'SupportsAlignContent',
                        'supports_multiple' => 'SupportsMultiple',
                        'enqueue_style'     => 'EnqueueStyle',
                        'enqueue_script'    => 'EnqueueScript',
                        'enqueue_assets'    => 'EnqueueAssets',
                        'custom_fields' => 'CustomFields',
                        'disabled' => 'Disabled'
                    ]);

    
                    if (empty($file_headers['title'])) {
                        die(__('This block needs a title: ' . $dir . '/' . $template->getFilename(), 'sage'));
                    }
    
                    if (empty($file_headers['category'])) {
                        die(__('This block needs a category: ' . $dir . '/' . $template->getFilename(), 'sage'));
                    }
    
                    // Checks if dist contains this asset, then enqueues the dist version.
                    if (!empty($file_headers['enqueue_style'])) {
                        $this->checkAssetPath($file_headers['enqueue_style']);
                    }
    
                    if (!empty($file_headers['enqueue_script'])) {
                        $this->checkAssetPath($file_headers['enqueue_script']);
                    }
    
                    // Set up block data for registration
                    $data = [
                        'name' => str_replace('acf-', '', $slug),
                        'title' => $file_headers['title'],
                        'description' => $file_headers['description'],
                        'category' => $file_headers['category'],
                        'icon' => $file_headers['icon'],
                        'keywords' => explode(' ', $file_headers['keywords']),
                        'mode' => $file_headers['mode'] ? $file_headers['mode'] : 'preview',
                        'align' => $file_headers['align'],
                        'render_callback'  => [ $this->app['acfblocks'], 'renderBlock' ],
                        'enqueue_style'   => $file_headers['enqueue_style'],
                        'enqueue_script'  => $file_headers['enqueue_script'],
                        'enqueue_assets'  => $file_headers['enqueue_assets'],
                        'example'  => array(
                            'attributes' => array(
                                'mode' => 'preview',
                            )
                        )
                    ];

    
                    // If the PostTypes header is set in the template, restrict this block to those types
                    if (!empty($file_headers['post_types'])) {
                        $data['post_types'] = explode(' ', $file_headers['post_types']);
                    }
    
                    // If the SupportsAlign header is set in the template, restrict this block to those aligns
                    if (!empty($file_headers['supports_align'])) {
                        $data['supports']['align'] = in_array($file_headers['supports_align'], array('true', 'false'), true) ? filter_var($file_headers['supports_align'], FILTER_VALIDATE_BOOLEAN) : explode(' ', $file_headers['supports_align']);
                    }
    
                    // If the SupportsMode header is set in the template, restrict this block mode feature
                    if (!empty($file_headers['supports_anchor'])) {
                        $data['supports']['anchor'] = $file_headers['supports_anchor'] === 'true' ? true : false;
                    }
    
                    // If the SupportsMode header is set in the template, restrict this block mode feature
                    if (!empty($file_headers['supports_mode'])) {
                        $data['supports']['mode'] = $file_headers['supports_mode'] === 'true' ? true : false;
                    }
    
                    // If the SupportsInnerBlocks header is set in the template, restrict this block mode feature
                    if (!empty($file_headers['supports_jsx'])) {
                        $data['supports']['jsx'] = $file_headers['supports_jsx'] === 'true' ? true : false;
                    }
    
                    // If the SupportsAlignText header is set in the template, restrict this block mode feature
                    if (!empty($file_headers['supports_align_text'])) {
                        $data['supports']['align_text'] = $file_headers['supports_align_text'] === 'true' ? true : false;
                    }
    
                    // If the SupportsAlignContent header is set in the template, restrict this block mode feature
                    if (!empty($file_headers['supports_align_text'])) {
                        $data['supports']['align_content'] = $file_headers['supports_align_content'] === 'true' ? true : false;
                    }
    
                    // If the SupportsMultiple header is set in the template, restrict this block multiple feature
                    if (!empty($file_headers['supports_multiple'])) {
                        $data['supports']['multiple'] = $file_headers['supports_multiple'] === 'true' ? true : false;
                    }
    
                    // Register the block with ACF
                    \acf_register_block_type(apply_filters("tone/blocks/register-data", apply_filters("tone/blocks/$slug/register-data", $data)));                    
                }
            }
        }
    }

    /**
     * Function to strip the `.blade.php` from a blade filename
     */
    public function removeBladeExtension($filename)
    {
        // Filename must end with ".blade.php". Parenthetical captures the slug.
        $blade_pattern = '/(.*)\.blade\.php$/';
        $matches = [];
        // If the filename matches the pattern, return the slug.
        if (preg_match($blade_pattern, $filename, $matches)) {
            return str_replace('-', '', $matches[1]);
        }
        // Return FALSE if the filename doesn't match the pattern.
        return false;
    }

    public function getSpacingConfig() {        
        $list = \apply_filters('tone/acf-blocks/band/spacing', ["default" => "Default"]);
        
        return $list;
    }


    public function renderBlock($block, $content = '', $is_preview = false, $post_id = 0) {
        // Set up the slug to be useful
        $slug  = str_replace('acf-', '', str_replace('acf/', '', $block['name']));
        $block = array_merge(['className' => ''], $block);

        // Set up the block data
        $block['post_id'] = $post_id;
        $block['is_preview'] = $is_preview;
        $block['content'] = $content;
        $block['slug'] = $slug;
        $block['anchor'] = isset($block['anchor']) ? $block['anchor'] : '';
        // Send classes as array to filter for easy manipulation.
        $block['classes'] = [
            $slug,
            $block['className'],
            $block['is_preview'] ? 'is-preview' : null,
            'align'.$block['align'],
            // somehow need to get padding and possibly interface classes here
        ];

        // Get block model if set
        $fieldModelFilePath = \Roots\resource_path("block-controllers/${slug}.php");
        $blockModel = [];
        if( file_exists($fieldModelFilePath) ){
            $blockModel = require $fieldModelFilePath;
        }

        // Filter the block data.
        $block = apply_filters("tone/blocks/data", apply_filters("tone/blocks/$slug/data", $block));

        // Join up the classes.
        $block['classes'] = implode(' ', array_filter($block['classes']));

        // Get the template directories.
        $directories = apply_filters('sage-acf-gutenberg-blocks-templates', []);

        foreach ($directories as $directory) {
            $view = 'ThemeBlock::'.$slug;

            if ($this->isSage10()) {
                if (\Roots\view()->exists($view)) {
                    // Render inline styles if needed
                    $inlineStyles = $block['styles'];
                    echo "
                    <style>
                        ${inlineStyles}
                    </style>
                    ";

                    // Render main block template
                    echo \Roots\view($view, array_merge([ 
                            'block' => $block, 
                            'bandSpacing' => $this->getSpacingConfig()
                        ], $blockModel));
                }
            } else {
                echo \App\template(locate_template("${directory}/${slug}"), ['block' => $block]);
            }
        }
    }

    /**
     * renderPartial
     */
    public static function renderPartial(string $templateName, $data) {
        echo \Roots\view()->first(['ToneThemePartial::'.$templateName, 'TonePartial::'.$templateName], $data);
    }


    /**
     * Filter a template path, taking into account theme templates and creating
     * blade loaders as needed.
     */
    public function template(string $template): string
    {
        // Locate any matching template within the theme.
        $themeTemplate = $this->locateThemeTemplate($template);
        if (!$themeTemplate) {
            return $template;
        }

        // Include directly unless it's a blade file.
        if (!Str::endsWith($themeTemplate, '.blade.php')) {
            return $themeTemplate;
        }

        // We have a template, create a loader file and return it's path.
        return view(
            $this->fileFinder->getPossibleViewNameFromPath(realpath($themeTemplate))
        )->makeLoader();
    }

    /**
     * Locate the theme's WooCommerce blade template when available.
     */
    protected function locateThemeTemplate(string $template): string
    {
        $themeTemplate = WC()->template_path() . str_replace(\WC_ABSPATH . 'templates/', '', $template);
        return locate_template($this->sageFinder->locate($themeTemplate));
    }
}
