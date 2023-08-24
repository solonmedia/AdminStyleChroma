# Admin Style Chroma

This module provides a user interface to control the colors and typography of the AdminThemeUIKit backend theme for ProcessWire 3.0.

The requirements are:

- PHP >= 7.x
- ProcessWire >= 3.0.179
- AdminThemeUikit >= 0.3.3
- Less >= 4
- InputfieldColor >= 1.1.6

# Installation

The module can be installed from the Modules Directory or from the zip file archive from the main branch.

When you first install the module, you will be taken to the configuration page that consists of four panes:
## Chroma Scheme Colors

Using the color selectors, you can select the first color - your main color - and a second color if you wish - your accent color. Only the first color is required. The default color scheme installed by the module is a grayscale dark mode theme.

Your main color does not got modified and gets applied to principal interface elements. If you are currently using the rock.less style as your admin style, this color gets applied to the @rock-primary LESS variable.

Your second color gets desaturated.
### Current Palette Results

In the background, depending on your mixer type either one or both of your color selections will be calculated and applied to eight master colors. These colors are displayed here.

Your first color choice is applied without any modifications to palette color 3.

Your second color choice (when applicable) is desaturated and treated according to your mixer type selection and applied to palette color 6.

In general, colors 1-4 are applied to interface elements and their hover states. Colors 5-8 are applied to backgrounds and muted states.

In UIKit parlance:

    Primary color = @chroma-lum-sat-3
    Secondary color = @chroma-kum-sat-1
    Muted color = @chroma-lum-sat-7
    Default color = @chroma-lum-sat-6

Contrast rules are them applied to these colors to get regular and strong labels that are used to assure correct contrast to applied.

**Please Be Aware:** The accessibility contrast threshold of 43% (the LESS default) is applied, but it is still possible to select color combinations that will evade readability scores from Google Lighthouse.

Below the current palette dots you will se sample swatches and their hover states can be activated.

## Chroma Scheme Options

The selections made here will alter the LESS files imported into the final admin.css and will either calculate a second color from the first one your select or use the second color.

### Color Mixer Type

There are several mixers included. I'm always interested in other viable additions. Future versions of this module will likely include an ability to add your own custom select options to the interface to reference your own LESS include files.

**Single**
: This mixer mode takes your first color, and desaturates it in order to get the second color needed to build the theme palette.

**Contrast**
: This mixer mode takes your first color, negates it and desaturates it in order to get the second color needed to build the theme palette.

**Duotone**
: This mixer mode takes your first color and uses it to build out the top half of the palette, and takes your second color, desaturates it slightly, and uses it to build out the bottom half of the palette.

**Cool Harmony**
: This mixer takes your first color and spins its hue counterclockwise on the color wheel to get the second color and uses it to build out your color palette.

**Warm Harmony**
: This mixer takes your first color and spins its hue clockwise on the color wheel to get the second color and uses it to build out your color palette.
### Luminance Direction

**Light to Dark (Dark Mode)**
: This mode sets the palette to run from light colors at 1 to dark colors at 8. When using duotone, the light to dark ordering applies to each 4-color block individually. When using single color mode, the secondary color is a darkened version of the main color.

**Dark to Light (Light Mode)**
: This mode sets the palette to run from dark colors at 1 to light colors at 8. When using duotone, the dark to light ordering applies to each 4-color block individually. When using single color mode, the secondary color is a lightened version of the main color.

### Vibrance Level

While the secondary color is always somewhat desaturated, you may wish to dial down or dial up the saturation depending on the text contrast requirements of your color theme.

**Subdued**
: The most aggressive desaturation level.

**Standard**
: Reasonable desaturated for most applications.

**Vibrant**
: The least desaturated settings, though still slightly desaturated.

## Chroma Scheme Fonts

The drop down selectors here will detect css stylesheets found in your ste/assets/fonts or site/templates directory. If you use RockFrontend to download your Google Fonts, it will detect these fonts as well.

If you select "No Custom Font" for either the Header Font or the Body Text Font, the default AdminThemeUiKit font rules will apply.

## Add Google Fonts

This feature makes use of a modified version of [Bernhard Baumrock](https://github.com/baumrock/AdminStyleRock)'s method (found in RockFrontend) for procuring Google Font files and saving them on your server. After looking at his references on CSSTricks is was pretty clear that the header manipulation approach was going to be the best one.

When first installed and run, the Admin Style Chroma module will download json lists of Google Font options and cache them in your site/assets directory. There is currently no method in place to check for new fonts, so if for some reason you are not seeing a Google Font you want to use, deleting this file should force the module to repopulate it:

    /site/assets/AdminStyleChroma/google_fonts.json

The Google Fonts are downloaded by individual family and saved along with their CSS file in:

    /site/assets/fonts/{family}/{family}.css

If you select font variants beneath the dropdowns, these values will be passed to the request. If you do not specify which font variants you want, Google will return the defaults for that font family.

If you which to include special italic/oblique variants for each weight, set the option appropriately.

If a variant does not exist for a given weight, Google will attempt to serve the closest weights available.

If you make selections (or don't) and select a font that you have already downloaded before, the previous family files will be overwritten.

**PLEASE NOTE**
: If you download a lot of fonts, this process could take some time.

## Style Compatability

A lot of styles have already been corrected. A number of styles within the ProcessWire core that use plain css or scss have been overwritten via specificity. A 'chroma' class is also added to the body tag, which drives many of the newly inherited classes, but due to the design of some features of certain modules there are other classes defined outside of the heirs of this class.

I'm not always happy with how warnings appear. Future versions will address these issues.

I've included many rules to provide support for the following areas:

* Tab Wrappers
* Page lists and actions
* Radios, Checkboxes and Selection Colors
* Selections, Marked text
* Panels and widgets
* Image related popups
* Awesomeplete
* RepeaterMatrix
* TinyMCE Interface
* Tracy Debugger
* Page Hit Counter
* Release Note Changes
* Admin On Steroids
* Admin Helper
* Search Engine
* Color Spectrum
* Easy Repeater Sort
* Page View Statistics
* Nette Tests

All changes here are entirely superficial quality of life style improvements. The functionality is not altered. Depending on your TinyMCE settings you may see these improvements but you may not. I've made changes that address quirks that I have personally seen.

I am always open to adding rules for other modules where the styles are off or assume a white background.

I hope one day we will have a proper discussion of less/css normalization for module authors, but even when that occurs it is hard to say how we will retrofit older modules, etc.

For now, this is a patchwork process.
