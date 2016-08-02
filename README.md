![screen shot 2016-07-13 at 10 18 42 am](https://cloud.githubusercontent.com/assets/17840996/16812744/5b92f468-48e3-11e6-9d2a-5c735b1596c6.png)

# Introducing CSVelte
### A modern solution to an age-old problem

It's been over half a decade since I [released PHP CSV Utilities v0.3](http://www.devnetwork.net/viewtopic.php?f=50&t=115633). Since then, PHP has changed drastically (not to mention the changes I've gone through... hello gray hairs!). It's grown up quite a bit. We've gotten namespaces, traits, anonymous functions, generators, and a bunch more cool features since I last wrote a line of code related to CSV and tabular data. And seeing as PHP has given me so much cool new stuff to work with, I decided the library should get a shiny new name. So, without further ado, please welcome the new and improved and updated for PHP5.3+, CSVelte! Pronounced just like the word "svelte". You see what I did there? Pretty clever, ain't I?

## Getting Started

CSVelte requires at least PHP5.3

### Installation

#### With Composer 

To install using composer, just use the following command. That's it. Happy coding.

```bash
$ composer install lukev/csvelte
```

#### Without Composer (direct download)

You should be using [Composer](https://getcomposer.org/). Did you not see how easy that was? It's stupid easy. Stupid easy. Use Composer. Anyway, if you insist, you may download the latest version of CSVelte using the button above, and then include the autoloader file, which will register CSVelte's autoload function for you. That's it. Happy coding.

```php
<?php
// just include this line and classes will be auto-loaded for you
require_once "/path/to/CSVelte/src/AutoLoader.php";

$reader = CSVelte::reader("./files/input.csv");
```

## Reading CSV 

Reading CSV data from a local file is as simple as creating a reader object and iterating over it using foreach. As long as the data even slightly resembles CSV data, the reader should be able to figure out how to read it. You don't need to tell it what line endings are being used, what the delimiter character is, what the quote character is, etc. It will usually be able to figure all that out on its own. You just tell it where to find a CSV file and you're golden.

```php
<?php
foreach ($reader = CSVelte::reader('./data/products.csv') as $line_no => $row) {
    // $row will now be a CSVelte\Table\Row object (which is an iterator)
    foreach ($row as $label => $data) {
        // $label will now be the column header value (if there is one, otherwise it will be a numeric index)
        // $data will now be a CSVelte\Table\Cell object
        $str = (string) $data; // can be converted to string this easily
        $val = $data->getValue(); // or use this to get semantic value
        do_something_useful_with($label, $str);
    }
}
```

It is however, possible to explicitly tell the reader class what "flavor" of CSV you are working with via the CSVelte\Flavor class. To do this, you simply create a new flavor class and pass it as the second argument to the reader method. If you pass a flavor object to the reader, it turns autodetection off completely. So don't expect that whatever attributes you don't pass to your flavor object will be ascertained by the reader. They won't. It will just use some sane default instead.

```php
<?php
$flvr = new Flavor(array(
    'delimiter' => "\t",
    'lineTerminator' => "\n",
    'quoteStyle' => Flavor::QUOTE_ALL,
    'header' => false
));
$reader = CSVelte::reader('./data/products.csv', $flvr);
```

## Learn More

Head on over to the [API documentation](https://deni-zen.github.io/csvelte/documentation.html) for instructions on reading and writing CSV files, auto-detecting CSV format, and more.

## Contribute

CSVelte is a free (as in beer) library. I work on it in my spare time. If you like it, feel free to buy me a beer (you can PayPal me at luke.visinoni@gmail.com). Contributions will help me to keep pumping out new features and bug fixes and to, eventually, create a dedicated CSVelte website. Or, if you have an idea for a new feature or a bug report, feel free to [submit a pull request](https://github.com/deni-zen/csvelte/pulls).

## License

CSVelte is also free as in speach. It is licensed under the MIT license, meaning basically, you can do whatever you want with it so long as you include the original copyright and license notice in any copy of the software/source.

## About the author

CSVelte was designed and developed by [Luke Visinoni](https://github.com/deni-zen). Feel free to [drop me a line](mailto:luke.visinoni@gmail.com) if you want to tell me how great it is.

