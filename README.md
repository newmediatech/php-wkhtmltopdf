# PHP WkHtmlToPdf - interface to wkhtmltopdf command

[[Packagist](https://img.shields.io/packagist/v/mediatech/php-wkhtmltopdf.svg?style=flat-square&maxAge=2592000)](https://packagist.org/packages/mediatech/php-wkhtmltopdf)
[[License](https://img.shields.io/packagist/l/mediatech/php-wkhtmltopdf.svg?style=flat-square&maxAge=2592000)](https://github.com/newmediatech/php-wkhtmltopdf/blob/master/LICENSE)

PHP WkHtmlToPdf provides OOP interface to ease PDF creation from HTML contents using [wkhtmltopdf](http://wkhtmltopdf.org) command tool.  
The **wkhtmltopdf** command tool must be installed previously on your server.

## Installation

Install the latest package version through [Composer](http://getcomposer.org):

```
composer require mediatech/php-wkhtmltopdf:dev-master
```

or

```
"require": {
    "mediatech/php-wkhtmltopdf": "dev-master"
},
```

## Using example

Load HTML from string and save contents to PDF specified file.

```php
use MediaTech\Pdf;

$pdf = new Pdf();
$pdf->loadHtml('<div>test</div>')
    ->save(/path/to/file.pdf);
```