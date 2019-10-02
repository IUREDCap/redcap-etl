<!-- =================================================
Copyright (C) 2019 The Trustees of Indiana University
SPDX-License-Identifier: BSD-3-Clause
================================================== -->

Local Test Directory
==============================

This directory is a place for developers to put tests that
they do NOT want to be committed to Git. The .gitignore file
is set to ignore all files in this directory except for
this README file.

To include PHPCap in your tests, require the PHPCap autoloader:

    <?php 
    
    require(__DIR__ . '/../../autoloader.php');
    use \IU\PHPCap\RedCapProject;
    ...
