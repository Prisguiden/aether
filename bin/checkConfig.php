#!/usr/bin/php
<?php

$file = "autogenerated.config.xml";
if (!file_exists($file)) {
    echo "autogenerated.config.xml not found\n";
    echo "Must be run in config/ dir of an aether project.\n";
    exit(-1);
}

$data = file_get_contents($file);

$dom = new DOMDocument();
$dom->loadXML($data);
$xpath = new DOMXPath($dom);
$searchpath = $xpath->query("//option[@name='searchpath']");

$searchpaths = explode(";", $searchpath->item(0)->textContent);
$searchpaths = array_map("trim", $searchpaths);
$searchpaths = array_filter($searchpaths, "strlen");

$modules = $xpath->query("//module");

$allModules = array();
$modulesByPath = array();

foreach ($modules as $m) {
    foreach ($m->childNodes as $c) {
        $data = "";
        if ($c->nodeType == XML_TEXT_NODE)
            $data .= trim($c->textContent);
        if ($data) {
            $path = array();
            $t = $c;
            while (($t = $t->parentNode) != null) {
                if ($t->nodeName == 'rule') {
                    $pathBit = $t->getAttribute('match');
                    if (!$pathBit) {
                        $pathBit = $t->getAttribute('pattern');
                        $pathBit = substr($pathBit, 1, strlen($pathBit) - 2);
                    }

                    array_unshift($path, $pathBit);
                }
            }

            $module = $data;
            $pathString = "/" . implode("/", $path);

            $exists = false;
            foreach ($searchpaths as $spath) {
                $testfile = $spath . "modules/" . $module . ".php";
                if (file_exists($testfile)) 
                    $exists = $testfile;
            }
            $moduleInfo = array(
                'name' => $module,
                'filename' => ($exists ? $exists : 'not found')
            );
            $modulesByPath[$pathString]['modules'][$module] = $moduleInfo;
            $allModules[$module] = $moduleInfo;
        }
    }
}

print "\nUnique modules loaded per path\n";
print "──────────────────────────────\n";
foreach ($modulesByPath as $path => $d) {
    print "[36m" . $path . "[0m\n";
    foreach ($d['modules'] as $module => $data) {
        print "    [32m{$module}[0m ({$data['filename']})\n";
    }
}

$unusedModules = array();
foreach ($searchpaths as $spath) {
    $d = opendir($spath . "modules");
    while ($f = readdir($d)) {
        if (substr($f, -4) !== ".php") 
            continue;

        $module = substr($f, 0, -4);
        if (!isset($allModules[$module])) {
            $unusedModules[] = array(
                'name' => $module,
                'filename' => $spath . "modules/" . $f
            );
        }
    }
}

if ($unusedModules) {
    print "\nModules NOT used by this config (but might be used by other configs)\n";
    print "────────────────────────────────────────────────────────────────────\n";
    foreach ($unusedModules as $m) {
        print "    [31m{$m['name']}[0m ({$m['filename']})\n";
    }
}
