[![Build Status](https://travis-ci.org/donsingh/html2php.svg?branch=master)](https://travis-ci.org/donsingh/html2php)


# HTML2PHP
Module for converting HTML files into associative PHP Arrays

## Introduction
This allows you to convert any HMTL File / String to a associative PHP array.

```
<html>
    <head>
        <title>This is a sample title</title>
    </head>
    <body>
        <div class="container">
            <p>This is a sample text</p>
        </div>
    </body>
</html>
```

Will convert it to:

```
Array
(
    [tagname] => <html>,
    [start]   => Array
                (
                    [0] => <html>,
                    [1] => 1
                )
    [end]     => Array
                (
                    [0] => </html>,
                    [1] => 123
                )
    [text]    => ...
    [children] => Array
                (
                    [0] => Array
                        (
                            [tagname] => <head>
                            ...
                            ...
                            [children] => Array
                                            (
                                                [0] => Array(...)
                                            )
                        )
                    [0] => Array
                        (
                            [tagname] => <body>,
                            ...
                            ...
                        )
                )
)
```

## Usage

Simply require the library and initialize it with the path+filename to make the lib read the file.

```
require('html2php.php');

$reader = new HTML2PHP("/path/to/file/sample.html");

// See the parsed file as a string
$reader->getHTMLString();

// Grab the entire constructed array
$arr = $reader->getArray();

```

## Finding Elements

You can also search for elements similar to CSS/Jquery style selectors;

* tagname
* search by id      
* search by class  

```

$result = $reader->find("p");

foreach($result as $tag){
    //Print out the text content of each matching tag
    echo $tag['text'] . PHP_EOL;
}

```

## List of Methods

Matches with a search tagname / selector
1. `<Array> find( <string> $selector )`

Get the full HTML String

2. `<Array> getHTMLString();`

Used to check if a given string is a closing tag

3. `<Bool> isClosingTag( <string> $tag )`