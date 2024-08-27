# PHP Validator

Simple class for validating data and POST forms

## Installation

Use composer to install *Validator*.

```bash
composer require kris-ro/validator
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
### Validate string length
```php
(new Validator())->value('xx')->minLength(3); # false
(new Validator())->minLength(3, 'xx'); # false

(new Validator())->value('xxx')->maxLength(3); # true
(new Validator())->maxLength(3, 'xxxx'); # false

# chained validators
(new Validator())
            ->value('#Pwd123')
            ->addValidationRule('is_string')
            ->addValidationRule(['minLength', 8])
            ->process(); # false

# in forms
$_POST = [
  'id' => 'DF-999 859',
];

$postValidator = new Validator();

$validationResult = $postValidator
  ->addPostValidationMessages([
    'id' => 'Invalid ID',
  ])
  ->addPostValidationRules([
    'id' => ['notEmptyOneLineString', ['minLength', 5], ['isLength', 10], ['maxLength', 15]],
  ])
  ->processPost(); # true
```
### Validate numerical limits
```php
(new Validator())->value(10)->smallerThan(9.89); # false
(new Validator())->smallerThan(9.89, 10); # false

(new Validator())->value(1.05)->greaterThan(99.2); # false
(new Validator())->greaterThan(99.2, 1.05); # false

(new Validator())->value(101.05)->between(99.2, 201); # true
(new Validator())->between(99.2, 201, 101.05); # true

# in forms
$_POST = [
  'id' => 'DF-999',
  'int_value' => '999',
  'between_value' => '100',
];

$postValidator = new Validator();

$validationResult = $postValidator
  ->addPostValidationMessages([
    'id' => 'Invalid ID',
    'int_value' => 'Invalid Positive Integer',
    'between_value' => 'Invalid Between Values',
  ])
  ->addPostValidationRules([
    'id' => ['notEmptyOneLineString'],
    'int_value' => ['positiveInteger', ['greaterThan', 'limit' => 99]],
    'between_value' => ['positiveInteger', ['between', 'lowerLimit' => 99, 'upperLimit' => 101]],
  ])
  ->processPost(); # true
```
### Validate email
```php
(new Validator())->email('some-email@domain.tld'); # true
(new Validator())->value('some-email@domain.tld')->addValidationRule('is_string')->addValidationRule('email')->process(); # true

(new Validator())->isValidEmail(Validator::EMAIL_VALIDATOR_PHP, 'some-email@domain.tld'); # true 
(new Validator())->isValidEmail(Validator::EMAIL_VALIDATOR_REGEXP, 'some-email@domain.tld'); # true
(new Validator())->isValidEmail(Validator::EMAIL_VALIDATOR_REGEXP, 'much.”more\ unusual”@example.com'); # false
(new Validator())->isValidEmail(Validator::EMAIL_VALIDATOR_SIMPLIFIED, 'some-email@domain.tld'); # true - checks if there is a @ character

# in forms
$_POST = [
  'email' => 'much.”more\ unusual”@example.com',
];

$postValidator = new Validator();
$validationResult = $postValidator
  ->addPostValidationMessages([
    'email' => 'Invalid Email Address',
  ])
  ->addPostValidationRules([
    'email' => [['isValidEmail', 'mode' => Validator::EMAIL_VALIDATOR_REGEXP]],
  ])
  ->processPost(); # false

print_r($postValidator->getPostValidationMessages()); # should be ['email' => 'Invalid Email Address']
```
### Validate password strength
```php
# by regexp (requires at least 8 letters - including CAPITAL, numbers and special chars)
(new Validator())->strongPassword('password'); # false

# in forms
$_POST = [
  'password' => '#Password123',
];

(new Validator())
  ->addPostValidationMessages([
    'password' => 'Password To Week',
  ])
  ->addPostValidationRules([
    'password' => ['strongPassword'],
  ])
  ->processPost(); # true

# if you wanna punish your users, 
# this (on top of strongPassword rule) will fail any password that 
# has a same consecutive char more than twice
# or has the same char more than third of the total character count

(new Validator())->paranoiaStrong('#Passrs1') # fails: to many s

# in forms
$_POST = [
  'password' => '#Password123',
];

$postValidator = new Validator();

$validationResult = $postValidator
  ->addPostValidationMessages([
    'password' => 'Password To Week',
  ])
  ->addPostValidationRules([
    'password' => [['minLength', 10], 'paranoiaStrongPassword'],
  ])
  ->processPost(); # true


# using cracklib-runtime library if available
# before using this you need to check if cracklib-runtime library and proc_open function are available

$checker = new CracklibCheck();
(new Validator())->value('Password123')->addValidationRule(['minLength', 6])->addValidationRule([$checker, 'testPassword'])->process(); # false
print_r($checker->getResponseMessage());

# in forms
$_POST = [
  'password' => '#Password123',
];

$checker = new CracklibCheck();
$postValidator = new Validator();

$validationResult = $postValidator
  ->addPostValidationMessages([
    'password' => 'Password To Week',
  ])
  ->addPostValidationRules([
    'password' => [['minLength', 8], [$checker, 'testPassword']],
  ])
  ->processPost(); # true
print_r($checker->getResponseMessage());

```

### Validate POST forms with recursive data
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