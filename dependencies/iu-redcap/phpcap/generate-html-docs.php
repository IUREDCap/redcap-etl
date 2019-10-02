<?php 
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

/**
 * Code to generate HTML documents from the Markdown documents.
 *
 */

require_once('vendor/autoload.php');


/**
 * Extension of Parsedown class that is used to translate Markdown to HTML.
 *
 */
class MyParsedown extends \Parsedown
{
    protected $highlighter;
    protected $minimalHighlighter;

    public function __construct()
    {
        # Syntax highlighter for PHP
        $this->highlighter = new \FSHL\Highlighter(new \FSHL\Output\Html());
        $this->highlighter->setLexer(new \FSHL\Lexer\Php());
        
        # Syntax highlighter for shell commands/scripts
        $this->minimalHighlighter = new \FSHL\Highlighter(new \FSHL\Output\Html());
        $this->minimalHighlighter->setLexer(new \FSHL\Lexer\Minimal());
    }

    /**
     * Use new highlighters for code blocks.
     * 
     * @param unknown $block
     * @return unknown
     */
    public function blockFencedCodeComplete($block)
    {
        $text = print_r($block, true);
        
        $matches = array();
        preg_match('/\[class\] => language-([a-zA-Z]+)/', $text, $matches);
        
        if (count($matches) > 1 && $matches[1] === 'shell') {
            $block['element']['text']['text'] = $this->minimalHighlighter->highlight($block['element']['text']['text']);
        }
        else {
            $block['element']['text']['text'] = $this->highlighter->highlight($block['element']['text']['text']);
        }

        return $block;
    }

}

/**
 * Translates the specified Markdown file to HTML format and creates a file with
 * the HTML translation.
 * 
 * @param string $file the Markdown file to translate.
 * @param array $files the list of all the Markdown files.
 */
function translateFile($file, $files)
{
    $parsedown = new MyParsedown();
    
    $markdown = file_get_contents($file);
    
    $content = $parsedown->text($markdown);
    
    $content = str_replace('<pre>', '<div class="description"><pre>', $content);
    $content = str_replace('</pre>', '</pre></div>', $content);
    
    # Create anchors for h2 and h3 tags
    $content = preg_replace('/<h2>([^<]*)<\/h2>/', '<h2 id="\1">\1</h2>', $content);
    $content = preg_replace('/<h3>([^<]*)<\/h3>/', '<h3 id="\1">\1</h3>', $content);
    
    # Convert links to Markdown documents to links to HTML documents
    $content = str_replace('.md">', '.html">', $content);
    
    $html = "<!DOCTYPE html>\n" . "<html>\n" . "<head>\n" . '<meta charset="UTF-8">' . "\n"
            . '<link rel="stylesheet" href="' . 'themes/apigen/theme-phpcap/src/resources/style.css">' . "\n"
            . '<link rel="stylesheet" href="' . 'themes/apigen/theme-phpcap/src/resources/docstyle.css">' . "\n"
            . "<title>PHPCap Documentation</title>\n" 
            . "</head>\n"
            . "<body>\n"
            . '<div id="left">'."\n"
	        . '<div id="menu">'."\n"
	        . '<div id="topmenu">'."\n"
	        . '<span>PHPCap Docs</span> | <a href="api/index.html">PHPCap API</a>'."\n"
	        . '<hr />'."\n"
	        . "</div>\n"
	        . createIndex($file, $files)
            . '</div>'."\n"
            . '</div>'."\n"
            . '<div id="splitter"></div>'."\n"
            . '<div id="right">'."\n"
            . '<div id="rightInner">'."\n"
            . '<div id="content">' . "\n"
            . $content
            . '</div>' . "\n" 
            . '<div id="footer">' 
            . "\n" . 'PHPCap documentation' . "\n" . '</div>' . "\n"
            . "</div></div>\n"
            . '<script src="' . 'api/resources/combined.js"></script>'. "\n" 
            //. '<script src="' . __DIR__ . '/docs/api/elementlist.js"></script>' . "\n"
            . "</body>\n" . "</html>\n";
    
    $outputFile =  pathinfo($file, PATHINFO_DIRNAME).'/'.pathinfo($file, PATHINFO_FILENAME).".html";
    $outputFile = str_replace('docs-md', 'docs', $outputFile);
    
    print "{$outputFile}\n";
    
    file_put_contents($outputFile, $html);
}

/**
 * Creates an index for the specified file. Code assumes 
 * each file has a single <h1> tag that is at the start
 * of the file. The text for this tag is used as the
 * name of the document represented by the file.
 * 
 * @param string $file Markdown file for which an index is being created.
 * @param array $files List of all Markdown files (i.e., the contents of the index).
 * 
 * @return string the index in HTML format.
 */
function createIndex($file, $files)
{
    $index = '';
    
    $fileName = pathinfo($file, PATHINFO_FILENAME).".html";
    
    if (strcmp($fileName,'index.html') === 0) {
        $index = '<span id="overview">Overview</span>'."\n";
    }
    else {
        $index .= '<a href="index.html" title="Overview"><span id="overview">Overview</span></a>'."\n";
    }
    
    $index .= "<ul>\n";

    foreach ($files as $indexFile) {
        $indexFileName = pathinfo($indexFile, PATHINFO_FILENAME).".html";
        
        if (!(strcmp($indexFileName,"index.html") === 0)) {
        
            $parsedown = new MyParsedown();
            $markdown = file_get_contents($indexFile);
            $content = $parsedown->text($markdown);
        
            $html = new DOMDocument();
        
            #------------------------------------------------------
            # Get the first <h1> element value; it will be used as
            # the label for the link for the document
            #------------------------------------------------------
            $h1 = '';
            $html->loadHTML($content);
            foreach($html->getElementsByTagName('h1') as $h1) {
                // print("------------HTML: ".$html->saveHTML($h1));
                $h1 = $h1->nodeValue;
                break;
            }

            #---------------------------------------------
            # Get the text for the h2 and h3 tags
            #---------------------------------------------
            $xpath = new DOMXPath($html);
            $expression = '(//h2|//h3)';
            $elements = $xpath->query($expression);
            $headings = array();
            foreach ($elements as $element) {
                array_push($headings, [$element->tagName, $element->nodeValue]);
            }
            
            # if this is the index entry for the current file being processed
            if (strcmp($fileName,$indexFileName) === 0) {
                $index .= '<li class="active"><a href="'.$indexFileName.'">'.$h1.'</a></li>'."\n";
                
                # if any h2 or h3 headings were found
                if (count($headings) > 0) {
                    $index .= '<ul class="intraPage">'."\n";
                    $lastTag = 'h2';
                    foreach ($headings as $heading) {
                        list($tag, $text) = $heading;
                        if ($tag > $lastTag) {
                            $index .= '<ul class="intraPage">'."\n";
                        } elseif ($tag < $lastTag) {
                            $index .= "</ul>\n";
                        }
                        
                        $index .= '    <li class="active"><a href="#'.$text.'">'.$text.'</a></li>'."\n";

                        $lastTag = $tag;
                    }
                    if ($lastTag > 'h2') {
                        $index .= "</ul>\n";
                    }
                    $index .= "</ul>\n";
                }
            }
            else {
                $index .= '<li><a href="'.$indexFileName.'">'.$h1.'</a></li>'."\n";
            }
        }
        
    }
    $index .= "</ul>\n";
            
    return $index;
}

/**
 * Sorts file names in alphabetical order, except that file names
 * starting with 'user' (ignoring case) always sort first, and
 * file names starting with 'developer' (ignoring case) always sort last.
 * 
 * @param string $a file name 1 (possibly a full path name)
 * @param string $b file name 2 (possibly a full path name) 
 * @return number -1 for a < b, 0 for a = b, and 1 for a > b
 */
function fileNameCompare($a, $b)
{
    $comparison = 0;
    
    # Get just the file names from the full paths
    $a = basename($a);  
    $b = basename($b);
    

    if (stripos($a, 'user', 0) === 0 && stripos($b, 'user', 0) !== 0) {
        # if $a starts with 'user' and $b does not, sort $a first
        $comparison = -1;
    } elseif (stripos($a, 'user', 0) !== 0 && stripos($b, 'user', 0) === 0) {
        # if $a does not start with 'user' and $b does, sort $b first
        $comparison = 1;
    } elseif (stripos($a, 'developer', 0) === 0 && stripos($b, 'developer', 0) !== 0) {
        # if $a starts with 'developer' and $b does not, sort $a last
        $comparison = 1;
    } elseif (stripos($a, 'developer', 0) !== 0 && stripos($b, 'developer', 0) === 0) {
        # if $a does not start with 'developer' and $b does, sort $b last
        $comparison = -1;
    } else {
        # for cases not covered above, sort alphabetically
        $comparison = strcmp($a,$b);
    }
    
    return $comparison;
}

# Set the input and output directories
$inputDirectory  = __DIR__."/docs-md/";
$outputDirectory = __DIR__."/docs/";

$inputResources  = $inputDirectory.'/resources/';
$outputResources = $outputDirectory.'/resources/';

# Create the html resources directory if it doesn't already exist
if (!file_exists($outputResources)) {
    mkdir($outputResources);
}

# Copy the Markdown resources to the HTML resources directory
$resources = glob($inputResources . "*");
foreach ($resources as $resource) {
    $dest = str_replace('docs-md', 'docs', $resource);
    copy($resource, $dest);
}

# Process each Markdown file
$files = glob($inputDirectory . "*.md");
usort($files, "fileNameCompare");
foreach($files as $file)
{
    print "\nTranslating\n$file\n";
    translateFile( $file, $files );
}

print "\nDone.\n";
