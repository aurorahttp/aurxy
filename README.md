<p align="center"><img src="https://raw.githubusercontent.com/panlatent/aurxy/master/docs/images/logo.png" 
alt="aurxy" /></p>

[![Build Status](https://travis-ci.org/aurorahttp/aurxy.svg)](https://travis-ci.org/aurorahttp/aurxy)
[![Coverage Status](https://coveralls.io/repos/github/aurorahttp/aurxy/badge.svg?branch=master)](https://coveralls.io/github/aurorahttp/aurxy?branch=master)
[![Latest Stable Version](https://poser.pugx.org/aurora/aurxy/v/stable.svg)](https://packagist.org/packages/aurora/aurxy)
[![Total Downloads](https://poser.pugx.org/aurora/aurxy/downloads.svg)](https://packagist.org/packages/aurora/aurxy) 
[![Latest Unstable Version](https://poser.pugx.org/aurora/aurxy/v/unstable.svg)](https://packagist.org/packages/aurora/aurxy)
[![License](https://poser.pugx.org/aurora/aurxy/license.svg)](https://packagist.org/packages/aurora/aurxy)
[![Aurora Http](https://img.shields.io/badge/Powered_by-Aurora_Http-green.svg?style=flat)](https://aurorahttp.com/)

HTTP proxy server with powerful customizable filter rules.

Installation
------------
It's recommended that you use [Composer](https://getcomposer.org/) to install this library.

```bash
$ composer require aurora/aurxy
```

This will install the library and all required dependencies. The library requires PHP 7.0 or newer.

Workflow
--------

```
   ------ request  ---- filter --->        ---------------> 
  |                                |      |                |
Client       Aurxy Server         middleware         Remote Server  
  |                                |      |                |
   <----- response ---- filter ----        <---------------
 ```

Usage
-----

License
-------
The Aurxy is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).

