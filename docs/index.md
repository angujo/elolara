## Welcome to angujo/elolara

This library tends to assist in creation and update of Laravel models/eloquents through autogeneration of files

### Installation

#### Via Composer (Recommended)
```
composer require angujo/elolara
```

#### Manual
Download the files and add it to your directory path.
Include the autoload file in your project
```
include_once('.../path/to/package/dir/autoload.php');
```
Do people still do manual?

###Usage
This package is meant to be run on development machine.
Write access to app/Models directory should be enabled.
1. Publish the package
```
php artisan vendor:publish --provider="Angujo\Elolara\Laravel\ElolaraServiceProvider"
```
2. Make changes to the config file ``elolara.php`` in the ``config`` directory
3. Run the package artisan
```
php artsian elolara:generate
```
