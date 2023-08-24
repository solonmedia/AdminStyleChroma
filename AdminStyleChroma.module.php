<?php namespace ProcessWire;

use Sabberworm\CSS\OutputFormat;
use Sabberworm\CSS\Parser;
use Sabberworm\CSS\Rule\Rule;
use Sabberworm\CSS\RuleSet\AtRuleSet;
use Sabberworm\CSS\RuleSet\RuleSet;

/**
* @author Jacob Gorny, 8/24/2023
* @license Licensed under MIT
* @link https://www.solon.media
*/

require_once wire()->config->paths->siteModules . "Less/AdminStyle.php";
class AdminStyleChroma extends WireData implements Module, ConfigurableModule
{
  use AdminStyle;

  // Module info

  public static function getModuleInfo()
  {
    return [
      'title' => 'Admin Style Chroma',
      'version' => '1.0.1',
      'summary' => 'Chroma enables multiple calculated palette options for light and dark themes from simple base color selections based on the AdminThemeUIKit administrative backend theme.',
      'author' => 'Jacob Gorny',
      'href' => 'https://github.com/solonmedia/AdminStyleChroma',
      'autoload' => true,
      'singular' => true,
      'icon' => 'magic',
      'requires' => [
        'AdminThemeUikit>=0.3.3',
        'PHP>=7.2',
        'ProcessWire>=3.0.179',
        'Less>=4',
        'InputfieldColor>=1.1.6',
      ],
    ];
  }

  public function getStyleVars()
  {

    $color1 = ($this->color1=='') ? '#777777' : $this->color1;
    $color2 = ($this->color2=='') ? '#333333' : $this->color2;

    $mixtype = $this->mixtype;
    $lumdir = $this->lumdir;
    $satlevel = $this->satlevel;

    //Relevant if you have Inputfield ColorPicker installed

    $color_field_type = '';
    $color_field_note = '';

    //Relevant if you have RockFrontend installed

    $header_font = $this->header_font;
    $bodytext_font = $this->bodytext_font;
    $header_weight = $this->header_weight;
    $header_case = $this->header_case;

    $vars = [];

    //Set color variables

    if(isset($color1) && !empty($color1))
    {
      $format_color1 = $this->checkColor($color1);
      $vars["base-color"] = $format_color1;
    }

    if(isset($color2) && !empty($color2))
    {
      $format_color2 = $this->checkColor($color2);
      $vars["base-color2"] = $format_color2;
    }

    // Set option variables

    if(isset($mixtype) && !empty($mixtype))
    {
      $vars["color-scheme"] = $mixtype;
    }

    if(isset($lumdir) && !empty($lumdir))
    {
      $vars["lum-direction"] = $lumdir;
    }

    if(isset($satlevel) && !empty($satlevel))
    {
      $vars["sat-level"] = $satlevel;
    }

    // Set font variables

    $hfd = explode('|',$header_font,3);
    $bfd = explode('|',$bodytext_font,3);

    $vars['body-font-style-path'] = '';
    $vars['heading-font-style-path'] = '';

    if(!empty($hfd[0])) {
      $vars['base-heading-font-family'] = '"' . $hfd[2] . '"';
      $vars['heading-font-style-path'] = '"' . DIRECTORY_SEPARATOR . $hfd[0] . $hfd[1] . '"';
      $vars['base-heading-font-weight'] = $header_weight;
      $vars['base-heading-text-transform'] = $header_case;
    }

    if(!empty($bfd[0])) {
      $vars['base-body-font-family'] = '"' . $bfd[2] . '"';
      $vars['body-font-style-path'] = '"' . DIRECTORY_SEPARATOR . $bfd[0] . $bfd[1] . '"';
      $vars['navbar-nav-item-font-family'] = '"' . $bfd[2] . '"';
    }

    return $vars;
  }

  public function addStylesheets() {

    $fontRoot = $this->wire->config->paths('assets').'fonts';

  }

  public function __construct() {

    /**
    * Default configuration values
    *
    * @var array
    *
    */

    $defaultConfig = array(
      'color1' => '#777777',
      'color2' => '#444444',
      'mixtype' => 'single',
      'lumdir' => 'lum-light-to-dark',
      'satlevel' => 'sat-vibrant',
      'header_font' => '|inherit',
      'header_weight' => '200',
      'header_case' => 'none',
      'bodytext_font' => '|inherit',
    );

    foreach($defaultConfig as $key => $value) {
      $this->set($key, $value);
    }

    $this->addHookAfter('AdminTheme::getExtraMarkup', function($event) {
        $theme = $event->object;
        $theme->addBodyClass("chroma");
    });

  }

  public function init() {

    $this->addHookAfter("InputfieldForm::processInput", $this, "createWebfontFiles");

  }

  public function ready()
  {

    //Load base chroma with variables

    $this->loadStyle(__DIR__ . "/style/chroma.less");

  }

/**
 * Grab json from Google Fonts API and use the json to populate progressive config fields 
 * 
 */

  private function refreshGoogleFontsList() {

    $gf_api = 'AIzaSyAPezKVvXxxyvjM81DqoMclEh2RiZG8Txs';
    $http = new WireHttp();
    $files = new WireFileTools();

    if(!$files->exists($this->wire->config->paths->assets.'AdminStyleChroma/')) {
      $files->mkdir($this->wire->config->paths->assets.'AdminStyleChroma/');
    }
    if(!$files->exists($this->wire->config->paths->assets.'AdminStyleChroma/google_fonts.json')) {
      $results = $http->download(
        "https://www.googleapis.com/webfonts/v1/webfonts?key=$gf_api",
        $this->wire->config->paths->assets."AdminStyleChroma/google_fonts.json"
      );
    }
    return $files->exists($this->wire->config->paths->assets.'AdminStyleChroma/google_fonts.json');
  }

  private function getGoogleFontsSelect($category) {

    $files = new WireFileTools();

    if(!$category) return [ 'value' => '', 'label' => 'Must Define Category'];

    $have_list = $this->refreshGoogleFontsList();

    if ($have_list) {
      $font_file = $files->fileGetContents($this->wire->config->paths->assets.'AdminStyleChroma/google_fonts.json');
      $font_list = json_decode($font_file,true);
      $font_variants = [];
      foreach(($font_list['items']) as $key => $item) {
        if(count(array_intersect(
            [
              'latin',
              'latin-ext',
              'greek',
            ],
            $item['subsets']
          )) > 0 &&
          $item['category'] == $category
        ) {
          $font_select[urlencode($item['family'])] = $item['family'];
        }
      }
    }

    return $font_select;
  }

  /**
  * Grab files from assets/fonts/*.css and *.less
  * Test for font-family options and remove duplicates
  * Create array with filenames and font-family names
  * Populate new fieldset and select for font header selection changes.
  */

  private function getFontFiles() {

    $fontPaths = [];
    $fontAssetPaths = $this->wire->files->find($this->wire->config->path('assets').'fonts', $options = [
      'extensions' => ['css', 'less'],
      'returnRelative' => true,
    ]);
    foreach($fontAssetPaths as $fap) {
      $fontPaths[] = array(
        'path' => str_replace($this->wire->config->path('root'),'',$this->wire->config->path('assets').'fonts'.DIRECTORY_SEPARATOR),
        'file' => $fap,
      );
    }
    $fontTemplatePaths = $this->wire->files->find($this->wire->config->path('templates'), $options = [
      'extensions' => ['css', 'less'],
      'returnRelative' => true,
      'excludeDirNames' => ['common'],
    ]);
    foreach($fontTemplatePaths as $ftp) {
      $fontPaths[] = array(
        'path' => str_replace($this->wire->config->path('root'),'',$this->wire->config->path('templates')),
        'file' => $ftp,
      );
    }
    if(is_array($fontPaths) && count($fontPaths)) {
      return $fontPaths;
    } else {
      return false;
    }
  }

  private function getFontFamilies() {
    $fontPaths = $this->getFontFiles();
    if($fontPaths) {
      $fontFamilies = Array();
      foreach($fontPaths as $file) {
        $regexp = "/^@font-face\s*\{\s*font-family:\s*[\'\"]?([\w\s]*\'?[\w\s]+)[\'\"]?;/im";
        preg_match_all($regexp, $this->wire->files->fileGetContents($this->wire->config->path('root').$file['path'].$file['file']), $keys, PREG_PATTERN_ORDER);
        if(!empty($keys[1])) {
          foreach(array_unique($keys[1]) as $val) {
            $fontFamilies[] = [
               $file['path'].'|'.$file['file'].'|'.$val,
                $val,
            ];
          }
        }
      }
      return $fontFamilies;
    } else {
      return false;
    }
  }

  private function getFontSelectOptions() {
    $ff = $this->getFontFamilies();
    if(!empty($ff)) {
      $fontoptions = Array();
      foreach($ff as $font) {
        $fontoptions[$font[0]] = $font[1];
      }
      asort($fontoptions);
      return $fontoptions;
    } else {
      return false;
    }
  }

/**
 * 
 *  Based on RockFrontend Google Font downloader functions
 * 
 *  MIT License
 * 
 *  Copyright (c) 2020 baumrock.com
 * 
 */

  const webfont_agents = [
    'woff2' => 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:40.0) Gecko/20100101 Firefox/40.0', // very modern browsers
    'woff' => 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:27.0) Gecko/20100101 Firefox/27.0', // modern browsers
    'ttf' => 'Mozilla/5.0 (Unknown; Linux x86_64) AppleWebKit/538.1 (KHTML, like Gecko) Safari/538.1 Daum/4.1', // safari, android, ios
    'svg' => 'Mozilla/4.0 (iPad; CPU OS 4_0_1 like Mac OS X) AppleWebKit/534.46 (KHTML, like Gecko) Version/4.1 Mobile/9A405 Safari/7534.48.3', // legacy ios
    'eot' => 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1; Trident/4.0)', // IE
  ];
  const webfont_comments = [
    'woff2' => '/* Super Modern Browsers */',
    'woff' => '/* Pretty Modern Browsers */',
    'ttf' => '/* Safari, Android, iOS */',
    'svg' => '/* Legacy iOS */',
  ];

        public function createWebfontFiles(HookEvent $event)
        {
          if ($event->process != "ProcessModule") return;
          if ($this->wire->input->get('name', 'string') != 'AdminStyleChroma') return;
          
          $variants_regular = [
            '1' => '100',
            '2' => '200',
            '3' => '300',
            '4' => 'regular',
            '5' => '500',
            '6' => '600',
            '7' => '700',
            '8' => '800',
            '9' => '900',
          ];

          $variants_italic = [
            '1' => '100italic',
            '2' => '200italic',
            '3' => '300italic',
            '4' => 'italic',
            '5' => '500italic',
            '6' => '600italic',
            '7' => '700italic',
            '8' => '800italic',
            '9' => '900italic',
          ];

          $variants_add = $this->wire->input->post->variants_add ?: [];
          $italic_add = $this->wire->input->post->italic_add== 1 ? true : false;
          $gfonts_add = array_merge(
            $this->wire->input->post->serif_add?:[],
            $this->wire->input->post->sansserif_add?:[],
            $this->wire->input->post->display_add?:[],
            $this->wire->input->post->handwriting_add?:[],            
          );

          $variants_str = '';

          foreach($variants_add as $var) {

            $variants_str .= ','.$variants_regular[$var];
            $variants_str .= $italic_add ? ','.$variants_italic[$var] : '';
          }

          $variants_str = ':'.ltrim($variants_str, ',');

          $css_url = null;

          foreach($gfonts_add as $key => $font) {
            $css_url[$key] = [
              'url' => 'https://fonts.googleapis.com/css?family='.$font.$variants_str,
              'path' => strtolower(str_replace(' ','',urldecode($font))),
            ];
            
          }

          foreach($css_url?:[] as $req) {
            $css = $this->downloadWebfontFiles($req['url'], $req['path']);
            $this->wire->files->filePutContents(
              $this->wire->config->paths->assets . "fonts/" . $req['path'] . "/" . $req['path'] . ".css",
              $css
            );
          }

        }

        public function downloadWebfontFiles($url, $path)
        {
          $data = $this->getFontData($path);
          $files = $this->wire->files;

          // make fonts path for font files if missing
          $fontsdir = $this->wire->config->paths->assets . "fonts/" .$path.'/';
          if(!$files->exists($fontsdir)) $this->wire->files->mkdir($fontsdir);

          /** @var WireHttp $http */
          $http = $this->wire(new WireHttp());
          foreach (self::webfont_agents as $format => $agent) {
            $data->rawCss .= "/* requesting format '$format' by using user agent '$agent' */\n";
            $http->setHeader("user-agent", $agent);
            $result = $http->get($url);
            $data->rawCss .= $result;
            $data = $this->parseResult($result, $format, $data);
          }
          return trim($this->createCssSuggestion($data, false), "\n");
        }

        private function createCssSuggestion($data, $deep = true): string
        {
          // bd($data->files, 'files');
          $fontsdir = $this->wire->config->urls->assets . "fonts/" . $data->path . "/";
          $css = $deep ? "/* suggestion for practical level of browser support */" : '';
          foreach ($data->fonts as $name => $set) {
            /** @var AtRuleSet $set */
            // bd('create suggestion for name '.$name);
            // bd($set, 'set');
            $files = $data->files->find("basename=$name");

            // remove src from set
            $set->removeRule('src');

            // add new src rule
            $rule = new Rule('src');
            $src = '';

            // see https://css-tricks.com/snippets/css/using-font-face-in-css/#practical-level-of-browser-support
            foreach ($files->find("format=woff|woff2") as $file) {
              $comment = self::webfont_comments[$file->format];
              // comment needs to be first!
              // last comma will be trimmed and css render() will add ; at the end!
              $src .= "\n    $comment\n    url('$fontsdir{$file->name}') format('{$file->format}'),";
            }
            $src = rtrim($src, ",\n ");
            $rule->setValue($src);
            $set->addRule($rule);

            $css .= "\n" . $set->render($data->parserformat);
          }

          if (!$deep) return $css;

          $css .= "\n\n/* suggestion for deepest possible browser support */";
          foreach ($data->fonts as $name => $set) {
            /** @var AtRuleSet $set */
            // bd('create suggestion for name '.$name);
            // bd($set, 'set');
            $files = $data->files->find("basename=$name");

            // remove src from set
            $set->removeRule('src');

            // add new src rule
            $rule = new Rule('src');
            $src = '';

            // see https://css-tricks.com/snippets/css/using-font-face-in-css/#practical-level-of-browser-support
            $eot = $files->get("format=eot");
            if ($eot) {
              $src .= "url('$fontsdir{$eot->name}'); /* IE9 Compat Modes */\n  ";
              $src .= "src: url('$fontsdir{$eot->name}?#iefix') format('embedded-opentype'), /* IE6-IE8 */\n  ";
            }
            foreach ($files->find("format!=eot") as $file) {
              $format = $file->format;
              if ($format == 'ttf') $format = 'truetype';
              $comment = self::webfont_comments[$file->format];
              // comment needs to be first!
              // last comma will be trimmed and css render() will add ; at the end!
              $src .= "\n    $comment\n    url('$fontsdir{$file->name}') format('{$file->format}'),";
            }
            $src = trim($src, ",\n ");
            $rule->setValue($src);
            $set->addRule($rule);

            $css .= "\n" . $set->render($data->parserformat);
          }

          return $css;
        }

        private function downloadWebfont(): WireData
        {
          $url = $this->wire->input->post('webfont-downloader', 'string');
          if (!$url) {
            // get data from session and return it
            $sessiondata = (array)json_decode((string)$this->wire->session->webfontdata);
            $data = new WireData();
            $data->setArray($sessiondata);
            return $data;
          }
          $data = $this->getFontData();

          // url was set, prepare fresh data
          $data->url = $url;

          /** @var WireHttp $http */
          $http = $this->wire(new WireHttp());
          foreach (self::webfont_agents as $format => $agent) {
            $data->rawCss .= "/* requesting format '$format' by using user agent '$agent' */\n";
            $http->setHeader("user-agent", $agent);
            $result = $http->get($url);
            $data->rawCss .= $result;
            $data = $this->parseResult($result, $format, $data);
          }
          // bd($data, 'data after http');

          $data->suggestedCss = trim($this->createCssSuggestion($data), "\n");

          // save data to session and return it
          $this->wire->session->webfontdata = json_encode($data->getArray());
          return $data;
        }

        /**
        * Get a blank fontdata object
        */
        private function getFontData($path): WireData
        {
          $data = new WireData();
          $data->path = $path;
          $data->rawCss = '';
          $data->suggestedCss = '';
          $data->fonts = new WireData();

          // load css parser
          require_once __DIR__ . "/vendor/autoload.php";
          $of = (new OutputFormat())->createPretty()->indentWithSpaces(2);
          $data->parserformat = $of;

          // create fonts dir
          $dir = $this->wire->config->paths->assets . "fonts/" . $path . '/';
          $this->wire->files->mkdir($dir);
          $data->fontdir = $dir;

          // downloaded font files
          $data->files = new WireArray();

          return $data;
        }

        /**
        * Extract http url from src()
        * @return string
        */
        private function getHttpUrl($src)
        {
          preg_match("/url\((.*?)\)/", $src, $matches);
          return trim($matches[1], "\"' ");
        }

        /**
        * CSS parser helper method
        * @return Rule|false
        */
        private function getRuleValue($str, RuleSet $ruleset)
        {
          try {
            $rule = $ruleset->getRules($str);
            if (!count($rule)) return false;
            return $rule[0]->getValue();
          } catch (\Throwable $th) {
            return "";
          }
        }

        private function parseResult($result, $format, $data = null): WireData
        {
          //if (!$data) $data = $this->getFontData();

          $parser = new Parser($result);
          $css = $parser->parse();

          $http = new WireHttp();
          foreach ($css->getAllRuleSets() as $set) {
            if (!$set instanceof AtRuleSet) continue;

            // create a unique name from family settings
            $name = $this->wire->sanitizer->pageName(
              $this->getRuleValue("font-family", $set) . "-" .
                $this->getRuleValue("font-style", $set) . "-" .
                $this->getRuleValue("font-weight", $set)
            );

            // save ruleset to fonts data
            $data->fonts->set($name, $set);

            // download url
            $src = (string)$this->getRuleValue("src", $set);
            $httpUrl = $this->getHttpUrl($src);
            // db($src, 'src');
            // db($httpUrl, 'httpUrl');

            // save font to file and add it to the files array
            $filename = $name . ".$format";
            $filepath = $data->fontdir . $filename;
            $http->download($httpUrl, $filepath);
            $size = wireBytesStr(filesize($filepath), true);
            $filedata = new WireData();
            $filedata->name = $filename;
            $filedata->basename = $name;
            $filedata->path = $filepath;
            $filedata->format = $format;
            $filedata->size = $size;
            $data->files->add($filedata);
          }
          // db($data, 'data');
          return $data;
        }

        private function showFontFileSize(): string
        {
          $out = "Filesize of all .woff2 files in " . $this->wire->config->urls->site . "templates/webfonts: {size}"; //JAG
          //$out = "Filesize of all .woff2 files in /site/templates/webfonts: {size}";
          $size = 0;
          foreach (glob($this->wire->config->paths->templates . "webfonts/*.woff2") as $file) {
            $size += filesize($file);
            $out .= "\n" . basename($file);
          }
          return str_replace("{size}", wireBytesStr($size, true), $out);
        }

/**
 * 
 *  End RockFrontend Code
 * 
 */

        private function checkColor( $color ) {
          $out = null;
          if ( strlen( $color ) > 0 ) {
            $out = '#' . substr($color,-6);
          }
          return $out;
        }

    /**
    * Config inputfields
    * @param InputfieldWrapper $inputfields
    */

    public function getModuleConfigInputfields() {

      $modules = $this->wire('modules');

      $wrapper = new InputFieldWrapper();

      $color_field_type = modules()->isInstalled("FieldtypeColor") ? "Color" : "Text";
      $color_field_note = modules()->isInstalled("FieldtypeColor") ? "Click the swatch to select." : "eg #00ff00 or rgba(0,0,0,1)";

      //Add color fieldset

      $color1 = $this->color1;
      $color2 = $this->color2;

      $colorfs = $modules->get('InputfieldFieldset');
      $colorfs->label = 'Chroma Scheme Colors';
      $colorfs->icon = 'eyedropper';
      $colorfs->description = 'Select the base colors for your scheme calculation. ';
      $colorfs->columnWidth = '33%';
      $wrapper->add($colorfs);

      $clean_color1 = $this->checkColor($color1);
      $clean_color2 = $this->checkColor($color2);

      // add first color
      $colorfs->add([
        'type' => $color_field_type,
        'columnWidth' => '50%',
        'name' => 'color1',
        'inputType' => 3,
        'alpha' => false,
        'spectrum' => 'allowEmpty: true
            showInitial: true
            showPalette: true
            showSelectionPalette: true
            palette: []
            localStorageKey: "spectrum.chroma_colors"
            containerClassName: "spectrum"
            replacerClassName: "spectrum"
            preferredFormat: "hex"
            showInput: true',
        'notes' => $color_field_note,
        'value' => ($this->color1=='') ? '#777777' : $clean_color1,
        'label' => 'First Color',
        'description' => 'Used for buttons and links.',
      ]);

      // add second color
      $colorfs->add([
        'type' => $color_field_type,
        'columnWidth' => '50%',
        'name' => 'color2',
        'inputType' => 3,
        'alpha' => false,
        'spectrum' => 'allowEmpty: true
            showInitial: true
            showPalette: true
            showSelectionPalette: true
            palette: []
            localStorageKey: "spectrum.chroma_colors"
            containerClassName: "spectrum"
            replacerClassName: "spectrum"
            preferredFormat: "hex"
            showInput: true',
        'notes' => $color_field_note,
        'value' => ($this->color2=='') ? '#444444' : $clean_color2,
        'label' => 'Second Color',
        'description' => 'This color is used in background colors.',
      ]);

      // add markup that will show current palette based on last saved selections

      $colorfs->add([
          'type' => 'markup',
          'label' => 'Current Palette Results',
          'icon' => 'gears',
          'value' => <<<END

            <div uk-grid class="uk-grid-margin-collapse uk-child-width-expand">
            <div><span class="sl1 uk-badge">1</span></div>
            <div><span class="sl2 uk-badge">2</span></div>
            <div><span class="sl3 uk-badge">3</span></div>
            <div><span class="sl4 uk-badge">4</span></div>
            <div><span class="sl5 uk-badge">5</span></div>
            <div><span class="sl6 uk-badge">6</span></div>
            <div><span class="sl7 uk-badge">7</span></div>
            <div><span class="sl8 uk-badge">8</span></div>
            </div>

            <div uk-grid class="uk-child-width-1-2 uk-grid-row-small uk-grid-column-small">
            <div>
            <h4 class="uk-margin-remove">Primary color</h4>
            <div class="uk-border-rounded uk-box-shadow-large uk-panel uk-panel-box uk-padding-small chroma-primary-color">
            <p class="uk-panel-text">Primary text</p>
            </div>
            </div>

            <div>
            <h4 class="uk-margin-remove">Secondary color</h4>
            <div class="uk-border-rounded uk-box-shadow-large uk-panel uk-panel-box uk-padding-small chroma-secondary-color">
            <p class="uk-panel-text">Secondary text</p>
            </div>
            </div>

            <div>
            <h4 class="uk-margin-remove">Muted color</h4>
            <div class="uk-border-rounded uk-box-shadow-large uk-panel uk-panel-box uk-padding-small chroma-muted-color">
            <p class="uk-panel-text">Muted text</p>
            </div>
            </div>

            <div>
            <h4 class="uk-margin-remove">Default color</h4>
            <div class="uk-border-rounded uk-box-shadow-large uk-panel uk-panel-box uk-padding-small chroma-default-color">
            <p class="uk-panel-text">Default text</p>
            </div>
            </div>

            <div>
            <h4 class="uk-margin-remove">Header color</h4>
            <div class="uk-border-rounded uk-box-shadow-large uk-panel uk-panel-box uk-padding-small chroma-header-color">
            <p class="uk-panel-text">Header text</p>
            </div>
            </div>

            <div>
            <h4 class="uk-margin-remove">Background color</h4>
            <div class="uk-border-rounded uk-box-shadow-large uk-panel uk-panel-box uk-padding-small chroma-background-color">
            <p class="uk-panel-text">Background text</p>
            </div>
            </div>

            </div>

          END,
        ]);

      //Add options fieldset

      $optionfs = $modules->get('InputfieldFieldset');
      $optionfs->label = 'Chroma Scheme Options';
      $optionfs->icon = 'paint-brush';
      $optionfs->description = 'Select the scheme calculation you want to apply.';
      $optionfs->columnWidth = '34%';
      $wrapper->add($optionfs);

      $optionfs->add([
        'type' => 'select',
        'name' => 'mixtype',
        'label' => 'Select Color Mixer Type',
        'description' => 'This selection will alter the LESS calculation file used with the color(s) you have selected.',
        'options' => [
          "single" => "Single - The first color will be used for primary buttons and link colors and backgrounds",
          "contrast" => "Contrast - The first color will be used for primary buttons and links and its complement will apply to backgrounds",
          "duotone" => "Duotone - The first color will be used for buttons and links, the second will be used for backgrounds",
          "harmonycool" => "Cool Harmony - The first color will apply to buttons and links, and a cool harmony will apply to the backgrounds",
          "harmonywarm" => "Warm Harmony - the first color will apply to buttons and links, and a warm harmony will apply to the backgrounds",
        ],
        "collapsed" => 0,
        "required" => 1,
        "placeholder" => "Select Color Mixer Type",
        "defaultValue" => 'single',
        "value" => $this->mixtype,
      ]);

      $optionfs->add([
        'type' => 'select',
        'name' => 'lumdir',
        'label' => 'Select Luminance Direction',
        'description' => 'This selection will take your colors and treat them, giving you either a dark main color and lighter background (light mode) or a light main color and darker backgrounds (dark mode).',
        'options' => [
          "lum-dark-to-light" => "Dark to Light (Light Mode)",
          "lum-light-to-dark" => "Light to Dark (Dark Mode)",      ],
          "collapsed" => 0,
          "required" => 1,
          "placeholder" => "Select Luminance Direction",
          "defaultValue" => 'lum-dark-to-light',
          "value" => $this->lumdir,
        ]);

        $optionfs->add([
          'type' => 'select',
          'name' => 'satlevel',
          'label' => 'Select Vibrance Level',
          'description' => 'This selection will alter the saturation levels of your background colors - the first color will not be affected.',
          'options' => [
            "sat-vibrant" => "Vibrant",
            "sat-standard" => 'Standard',
            "sat-subdued" => "Subdued",
            ],
            "required" => 1,
            "collapsed" => 0,
            "placeholder" => "Select Luminance Direction",
            "defaultValue" => 'sat-standard',
            "value" => $this->satlevel,
          ]);

        //Add fonts fieldset

        $fontfs = $modules->get('InputfieldFieldset');
        $fontfs->label = 'Chroma Scheme Fonts';
        $fontfs->icon = 'text-height';
        $fontfs->description = 'Select the fonts you would like to apply to headings and body text.';
        $fontfs->notes = 'Font stylesheets should exist somewhere within the /site/assets/fonts or site/templates/ directory hierarchies. If no font-family style rules are detected in the space, no selections will be displayed.';
        $fontfs->columnWidth = '33%';
        $wrapper->add($fontfs);

        $fontSelectOptions = $this->getFontSelectOptions();

        if (!empty($fontSelectOptions)) {
          $fontfs->add([
            'type' => 'select',
            'name' => 'header_font',
            'label' => 'Header Font',
            'description' => 'This selection will be applied to the headings in the backend.',
            'options' => array_merge(array('|inherit' => 'No Custom Font'),$fontSelectOptions),
              "collapsed" => 0,
              'required' => 1,
              'defaultValue' => '|inherit',
              "placeholder" => "Select Header Font",
              "value" => $this->header_font,
          ]);

          $fontfs->add([
            'type' => 'select',
            'name' => 'header_weight',
            'label' => 'Weight',
            'description' => '',
            'columnWidth' => '50%',
            'options' => [
              '100' => 'Hairline',
              '200' => 'Extra Light',
              '300' => 'Light',
              '400' => 'Normal',
              '500' => 'Medium',
              '600' => 'Semi Bold',
              '700' => 'Bold',
              '800' => 'Extra Bold',
              '900' => 'Heavy',
            ],
              "collapsed" => 0,
              "required" => 1,
              'defaultValue' => '400',
              "placeholder" => "Select Weight",
              "value" => $this->header_weight,
          ]);

          $fontfs->add([
            'type' => 'select',
            'name' => 'header_case',
            'label' => 'Case',
            'description' => '',
            'columnWidth' => '50%',
            'options' => [
              'uppercase' => 'All Capitals',
              'none' => 'Normal',
            ],
              "collapsed" => 0,
              'required' => 1,
              'defaultValue' => 'none',
              "placeholder" => "Select Case",
              "value" => $this->header_case,
          ]);

          $fontfs->add([
            'type' => 'select',
            'name' => 'bodytext_font',
            'label' => 'Body Text Font',
            'description' => 'This selection will apply to regular body text in the backend.',
            'options' => array_merge(array('|inherit' => 'No Custom Font'),$fontSelectOptions),
              "collapsed" => 0,
              'required' => 1,
              'defaultValue' => '|inherit',
              "placeholder" => "Select Body Text Font",
              "value" => $this->bodytext_font,
          ]);
        } else {
          $fontfs->add([
            'type' => 'markup',
            'label' => 'No Fonts Available',
            'icon' => 'bug',
            'value' => '
              There are no font families detected in the css and less files under the site/assets/fonts or site/templates directories.
            ',
                  ]);
        }

        //Add google fonts via download

        $gfontfs = $modules->get('InputfieldFieldset');
        $gfontfs->label = 'Add Google Fonts';
        $gfontfs->icon = 'text-height';
        $gfontfs->description = 'Add Google fonts via download not found in the dropdowns above.';
        $gfontfs->notes = 'If you choose to download a font again, it will either overwrite or override older css files for the same font.';
        $gfontfs->columnWidth = '100%';
        $gfontfs->collapsed = 1;
        $wrapper->add($gfontfs);

        if(!$this->refreshGoogleFontsList()) {
          $gfontfs->add([
            'type' => 'markup',
            'label' => 'Variable Test',
            'icon' => 'bug',
            'value' => '
              There is no Google Font list available and a new one could not be downloaded.
            ',
          ]);
        } else {
          $gfontfs->add([
            'type' => 'asmselect',
            'name' => 'serif_add',
            'label' => 'Serif Google Fonts',
            'description' => 'Select fonts you would like to download.',
            'options' => $this->getGoogleFontsSelect('serif'),
              "collapsed" => 0,
              "placeholder" => "Select Serif Fonts",
              "value" => "",
              'columnWidth' => 25,
          ]);
          $gfontfs->add([
            'type' => 'asmselect',
            'name' => 'sansserif_add',
            'label' => 'Sans Serif Google Fonts',
            'description' => 'Select fonts you would like to download.',
            'options' => $this->getGoogleFontsSelect('sans-serif'),
              "collapsed" => 0,
              "placeholder" => "Select Sans Serif Fonts",
              "value" => "",
              'columnWidth' => 25,
          ]);
          $gfontfs->add([
            'type' => 'asmselect',
            'name' => 'display_add',
            'label' => 'Display Google Fonts',
            'description' => 'Select fonts you would like to download.',
            'options' => $this->getGoogleFontsSelect('display'),
              "collapsed" => 0,
              "placeholder" => "Select Display Fonts",
              "value" => "",
              'columnWidth' => 25,
          ]);
          $gfontfs->add([
            'type' => 'asmselect',
            'name' => 'handwriting_add',
            'label' => 'Handwriting Google Fonts',
            'description' => 'Select fonts you would like to download.',
            'options' => $this->getGoogleFontsSelect('handwriting'),
              "collapsed" => 0,
              "placeholder" => "Select Handwriting Fonts",
              "value" => "",
              'columnWidth' => 25,
          ]);
          $gfontfs->add([
            'type' => 'Checkboxes',
            'name' => 'variants_add',
            'label' => 'Font Variants',
            'notes' => 'All fonts selected above will attempt to download the weight selected if available.',
            'options' => [
              1 => '100 Thin',
              2 => '200 Extra Light',
              3 => '300 Light',
              4 => '400 Normal',
              5 => '500 Medium',
              6 => '600 Semi Bold',
              7 => '700 Bold',
              8 => '800 Extra Bold',
              9 => '900 Heavy',
            ],
            'value' => [
              1 => 4,
              2 => 7,
            ],
            'optionColumns' => 1,
            'columnWidth' => 66,
          ]);
          $gfontfs->add([
            'type' => 'Toggle',
            'name' => 'italic_add',
            'label' => 'Include italic Versions?',
            'yesLabel' => '✓ Italic/Regular',
            'noLabel' => '✗ Regular only',
            'required' => 1,
            'defaultOption' => 'no',
            'columnWidth' => 33,
          ]);
        }

        // webfont downloader
        $data = null; //$this->downloadWebfont();
        if (isset($data) && $data->suggestedCss) {
          $f = new InputfieldMarkup();
          $f->label = 'Suggested CSS';
          $f->description = "You can copy&paste the created CSS into your stylesheet. The paths expect it to live in /site/templates/layouts/ - change the path to your needs!
              See [https://css-tricks.com/snippets/css/using-font-face-in-css/](https://css-tricks.com/snippets/css/using-font-face-in-css/) for details!";
          $f->value = "<pre style='max-height:400px;'><code>{$data->suggestedCss}</code></pre>";
          $f->notes = "Data above is stored in the current session and will be reset on logout";
          $fs->add($f);
        }
        if (isset($data) && $data->rawCss) {
          $f = new InputfieldMarkup();
          $f->label = 'Raw CSS (for debugging)';
          $f->value = "<pre style='max-height:400px;'><code>{$data->rawCss}</code></pre>";
          $f->notes = "Data above is stored in the current session and will be reset on logout";
          $f->collapsed = Inputfield::collapsedYes;
          $fs->add($f);
        }
        //$inputfields->add($fs);

        /**
        $wrapper->add([
          'type' => 'markup',
          'label' => 'Variable Test',
          'icon' => 'bug',
          'value' => '
            <pre>' . var_export($this->getFontSelectOptions(), true) .'</pre>
          ',
        ]);
        **/

          return $wrapper;
        }

      }
