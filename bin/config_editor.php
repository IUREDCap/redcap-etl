<!DOCTYPE html>
<?php

#-------------------------------------------------------------
# Configuration editor
#
# **WORK IN PROGRESS**
#
# To run this use:
#
#     php -S localhost:8000 config_editor.php
#
# 8000 is the port number, and another value can be chosen.
# After executing this command, access the configuration
# editor at:
#
#     http://localhost:8000
#
#-------------------------------------------------------------

require(__DIR__.'/../dependencies/autoload.php');

use IU\REDCapETL\RedCapEtl;
use IU\REDCapETL\EtlException;
use IU\REDCapETL\Version;
use IU\REDCapETL\Logger;

$submitValue = $_POST['submitValue'];
$configurationFile = $_POST['configurationFile'];
$contents = $_POST['contents'];

if (array_key_exists('directory', $_GET)) {
    $directory = $_GET['directory'];
} else {
    $directory = __DIR__;
}
$directory = realpath($directory);

$configFile = null;
if (array_key_exists('configFile', $_GET)) {
    $configFile = $_GET['configFile'];
}

?>

<html>
<head>
<style>
.dirbox {
    border: 1px solid black;
    padding: 4px;
    width: 28em;
    font-weight: bold;
    background-color: #EEEEEE;
}
a.dirlink, a:visited.dirlink {
    color: black;
    font-weight: bold;
    text-decoration: none;
}
a.filelink, a:visited.filelink {
    color: black;
    text-decoration: none;
}
</style>
</head>
<body>

<?php

echo "<h2>REDCap-ETL JSON Configuration File Editor</h2>\n";
echo "<hr />\n";

if ($submitValue === 'Upload File' || $submitValue === 'Save') {
    if ($submitValue === 'Upload File') {
        $contents = file_get_contents($configurationFile);
    } elseif ($submitValue === 'Save') {
        $saveResult =  file_put_contents($configurationFile, $contents);
        if ($saveResult) {
            echo "File saved<br/>\n";
        } else {
            echo "File not saved<br/>\n";
        }
    }
?>

    <p>
    Configuration file <?php echo $configurationFile; ?>
    </p>

    <form action="<?php echo __FILE__; ?>" method="post">
        <input type="hidden" name="configurationFile" value="<?php echo $configurationFile; ?>">
        <textarea name="contents" rows="22" cols="80"><?php echo $contents; ?></textarea>
        <br />
        <input type="submit" value="Save" name="submitValue">
    <form>

<?php
} else {
    if (!empty($configFile)) {
        $contents = file_get_contents($configFile);
        echo "<h4>{$configFile}</h4>\n";
        echo '<textarea rows="20" cols="80">'."\n";
        echo $contents;
        echo "</textarea>\n";
    } else {
        chdir($directory);

        echo "<p>Select Configuration File</p>\n";

        echo '<div class="dirbox">'.$directory."/</div>\n";

        echo '<div style="border: 1px solid black; width: 28em; overflow-y: scroll; height: 400px; padding: 4px;">'
            ."\n";

        $files = scandir($directory);
        foreach ($files as $file) {
            if (is_dir($file) && $file !== '.') {
                echo '<a href="'.__FILE__.'?directory='.$directory.'/'.$file.'" class="dirlink">'
                    .$file."/</a><br/>\n";
            }
        }
        foreach ($files as $file) {
            if (!is_dir($file) && (preg_match('/.json$/i', $file) || preg_match('/.ini$/i', $file))) {
                echo '<a href="'.__FILE__.'?configFile='.$directory.'/'.$file.'" class="filelink">'
                    .$file."</a><br/>\n";
            }
        }
?>

        <form action="<?php echo __FILE__; ?>" method="post">
            <input type="hidden" name="directory" value="<?php echo $directory; ?>">
            <input type="text" name="configurationFile" id="configurationFile">
            <input type="submit" value="Create New File" name="submitValue">
        </form>

<?php
        echo "</div>\n";
    }
}
?>

</body>
</html>
