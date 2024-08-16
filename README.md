# Validator

Simple class for validating data and POST forms

## Installation

Use composer to install *Validator*.

```bash
composer require kris-ro/validator:dev-main
```

## Usage

```php
require YOUR_PATH . '/vendor/autoload.php';

use KrisRo\Validator\Validator;

$validator = new Validator();

# chaining validators
$result = $validator
            ->value('99z9-d_')
            ->addValidationRule('is_string')
            ->addValidationRule('alphanumeric')
            ->process(); # false; only letters and numbers allowed
```
### Magic calls
```php
# the above can be also called as :

$validator = new Validator();
$result = $validator->alphanumeric('99z9-d_'); # false; only letters and numbers allowed

# other validation rules with magic call
(new Validator())->positiveInteger(99999); # true
(new Validator())->positiveInteger('9999s9'); # false
(new Validator())->notEmptyOneLineString('def54%'); # true
(new Validator())->notEmptyOneLineString(''); # false
```
All rules in `$validationRules` in `src/Validator.php` can be called magically as long as they are not PHP reserved words.

### Using a callback class
```php
$validator = new Validator();
$result = $validator
            ->value(999)
            ->addValidationRule([new YourValidationClass(), 'YourMethod'])
            ->process(); # boolean

# callback with static method
$result = $validator
            ->value(999)
            ->addValidationRule(['YourValidationClass', 'YourStaticMethod'])
            ->process(); # boolean
```
### Add a validation regular expression dynamically.
```php
$validator = new Validator();
$result = $validator
            ->createRegexRule('alphanumeric', '/^[a-z0-9\-_]+$/i')
            ->value('abc_-9')
            ->addValidationRule('alphanumeric')
            ->process(); # true


# In constructor
$validator = new Validator(['alphanumeric' => '/^[a-z0-9\-_]+$/i']);
$result = $validator
            ->value('abc_-9')
            ->addValidationRule('alphanumeric')
            ->process(); # true
```
### Validate date
```php
$validator = new Validator();
$result = $validator
            ->setDateFormat('m-d-Y')
            ->value('2000-12-12')
            ->addValidationRule('isValidDate') # method of the [KrisRo\Validator\Validator] class
            ->process(); # false, wrong date format
```
### Validate POST forms
```php
$_POST = [
  'multiple_values' => ['997', '786', '665'], # will validate values recursively 
  'id' => 'DF-999',
];

$postValidator = new Validator();

$validationResult = $postValidator
  ->addPostValidationMessages([
    'id' => 'Invalid ID',
    'multiple_values' => 'Invalid Multiple Values',
  ])
  ->addPostValidationRules([
    'id' => ['notEmptyOneLineString'],
    'multiple_values' => ['positiveInteger'],
  ])
  ->processPost(); # boolean

# Get error messages indexed by the field name
$postValidator->getPostValidationMessages();

# Get validated data, returns array indexed by the field name
$postValidator->getPost();
```

## Inheritance
This class can be extended and overwritten. Almost all methods and properties are public or protected.

You can also extend `KrisRo\Validator\Validator` and update the content of the protected property `validationRules`.

If you need a custom rule use a callback class or extend `KrisRo\Validator\Validator`, create your own validation method in the new class and use the method's name as a validation rule (similar to `isValidDate`).

## License

[MIT](https://choosealicense.com/licenses/mit/)