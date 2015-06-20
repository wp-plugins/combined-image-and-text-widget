=== Combined Image and Text Widget ===

Contributors: Nadav Rotchild
Author URI: http://www.nadavr.com/
Plugin URI: http://www.nadavr.com/
Tags: widget, links, admin, plugin, images, wpml
License: GPLv2 or later
Requires at least: 2.8
Tested up to: 3.8.1
Stable tag: 1

A widget plugin for text and image combinations, with multilingual support.

== Description ==

Combined Image and Text Widget is a plugin that allows you to effortlessly add text and images to your sidebars, with or without links.

= Features =

* Easily add images to your sidebar using the native Wordpress media uploader.
* Add classes, an id and a link to your sidebar widget for maximum development ease.
* Supports WPML multilanguage capabilities.

== Installation ==

1. Download a copy of the Combined Image and Text Widget plugin.
2. Move the combined-image-text-widget folder to your /wp-content/plugins/ directory.
3. Activate the plugin.
4. Drag and drop the Combined Image and Text widget to the desired sidebar in the Widgets page.
5. Enjoy.

== Frequently Asked Questions ==

= Does the plugin support multilingual websites? =

Yes, the plugin can be used in conjunction with the WPML plugin. The plugin's multilingual capabilities will automatically activate if WPML is present and active.

= What are the multilingual capabilities of the plugin? =
You can define the title, image, text and link for each enabled language. In addition the plugin can detect if your link leads to an inner page within your website, and attach the correct language code depending on the end user's selected language.

= Can I define a unique link for the widget in each language? =

Yes. By default the plugin will only provide you with one link input, and that link will be converted to the end user's language. However, you can disable this feature and instead manually define the url link for each language individually. To do that follow these steps:
1. Access your Wordpress admin panel.
2. Go to Settings->CITW (note that this page is only available if WPML is already enabled).
3. Under Multilingual Url Schema choose "Allow me to manually input the url for each language" and save.
4. Go to the Widgets page. You should now have url link inputs for each of your active languages. 

= Can I add language-specific classes to the widget? =

No. The widget does not accept language-specific classes. However, the plugin dynamically creates a language class for each of your widgets, and you can use that class to define language-specific CSS rules. The aforementioned language class will be a combination of "citw_" and your active language code. For example if your widget is currently displaying on a page in French it will have the "citw_fr" class.

== Screenshots ==

1. The Combined Image and Text widget placed inside a sidebar.
2. Example of the widget on a front-end sidebar with a title, image, text and link.