# MyBB-Installer
A Command-Line Interface Installer for MyBB

## Requirements
* You must have [Composer](http://www.getcomposer.org) installed
* You must be running PHP 5.6.30 or later (this may work with older versions of PHP, but I have not tried them out.)

## Known Issues
Currently, this utility relies on the [PclZip](http://www.phpconcept.net/pclzip/) library, however this library hasn't been updated for years. As of right now, this installer comes with a modified version of their library in order to get around a PHP Notice that pops up when using PHP 7. Eventually I would like to remove this dependency on [PclZip](http://www.phpconcept.net/pclzip/).

## Installation
The easiest (and recommended) way to install this is to run: `composer global require holycowzorz/mybb-installer`

This will install the MyBB installer into your global composer directory and make sure that the installer is accessible from anywhere on your system.

## Usage
`mybb install <version> --dir <directory name>`
By default, the installer will install into the current directory. Also by default, the newest version of MyBB will be installed.
You can specify which version of MyBB you would like to install. You can also change what directory you would like to install MyBB into by adding the `--dir` option.

## To Do:
* Remove dependency of PclZip
* Write documentation/comments for code

## Special Thanks
Thanks to [@taylorotwell](https://github.com/taylorotwell) for creating the [Laravel installer](https://github.com/laravel/installer) which gave me the idea to create this MyBB installer.
I also used a little bit of code from the [Laravel installer](https://github.com/laravel/installer) to speed things up.
