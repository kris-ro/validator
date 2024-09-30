<?php

use PHPUnit\Framework\TestCase;
use KrisRo\Validator\Validator;
use KrisRo\Validator\CracklibCheck;

class ValidatorTest extends TestCase {
  
  public function testvalidatorAgainstData() {
    $this->assertEquals(TRUE, (new Validator())->positiveInteger(9999));
    
    $this->assertEquals(TRUE, (new Validator())->positiveInteger('9999'));
    
    $this->assertEquals(TRUE, (new Validator(['alphanumeric' => '/^[a-z0-9\-_]+$/i']))
                                      ->value('99z9-d_')
                                      ->addValidationRule('is_string')
                                      ->addValidationRule('alphanumeric')
                                      ->process());
    
    $this->assertEquals(FALSE, (new Validator(['alphanumeric' => '/^[a-z0-9\-_]+$/i']))
                                      ->value('99z9-d_(')
                                      ->addValidationRule('is_string')
                                      ->addValidationRule('alphanumeric')
                                      ->process());
    
    $this->assertEquals(TRUE, (new Validator())->value('999')->addValidationRule('is_string')->addValidationRule('integer')->process());
    
    $this->assertEquals(FALSE, (new Validator())->value('-999')->addValidationRule('is_string')->addValidationRule('positiveInteger')->process());
    
    $this->assertEquals(FALSE, (new Validator())->value('999')->addValidationRule('is_int')->process());
    
    $this->assertEquals(TRUE, (new Validator())->value(999)->addValidationRule('is_int')->process());
    
    $this->assertEquals(FALSE, (new Validator())->value(999)->addValidationRule('is_string')->process());
  }
  
  public function testValidatorAgainstCallback() {
    $this->assertEquals(FALSE, (new Validator())->value(999)->addValidationRule([new TestCallback(), 'testValidString'])->process());
    $this->assertEquals(FALSE, (new Validator())->value(999)->addValidationRule(['TestCallback', 'staticTestValidString'])->process());
    $this->assertEquals(TRUE, (new Validator())->value('999')->addValidationRule(['TestCallback', 'staticTestValidString'])->process());
  }
  
  public function testPostData() {
    $_POST = [
      'id' => 'DF-999',
      'name' => 'Smith, John',
      'birth_date' => '10-25-1963',
    ];

    $postValidator = new Validator();

    // Invalid date format
    $validationResult = $postValidator
      ->createRegexRule('userId', '/^[a-z]{2}-\d+$/i')
      ->addPostValidationMessages([
        'id' => 'Invalid ID',
        'name' => 'Invalid Name',
        'birth_date' => 'Invalid Date',
      ])
      ->addPostValidationRules([
        'id' => ['userId'],
        'name' => ['notEmptyOneLineString'],
        'birth_date' => ['isValidDate'],
      ])
      ->processPost();
    
    $this->assertEquals(FALSE, $validationResult);
    $this->assertEquals(TRUE, !empty($postValidator->getPostValidationMessages()));

    
    // Added correct date format
    $validationResult = $postValidator
      ->createRegexRule('userId', '/^[a-z]{2}-\d+$/i')
      ->setDateFormat('m-d-Y')
      ->addPostValidationMessages([
        'id' => 'Invalid ID',
        'name' => 'Invalid Name',
        'birth_date' => 'Invalid Date',
      ])
      ->addPostValidationRules([
        'id' => ['userId'],
        'name' => ['notEmptyOneLineString'],
        'birth_date' => ['isValidDate'],
      ])
      ->processPost();

    $this->assertEquals(TRUE, $validationResult);
    $this->assertEquals(TRUE, empty($postValidator->getPostValidationMessages()));


    // Invalid ID
    $_POST['id'] = '6DF-999';
    
    $validationResult = $postValidator
      ->createRegexRule('userId', '/^[a-z]{2}-\d+$/i')
      ->setDateFormat('m-d-Y')
      ->addPostValidationMessages([
        'id' => 'Invalid ID',
        'name' => 'Invalid Name',
        'birth_date' => 'Invalid Date',
      ])
      ->addPostValidationRules([
        'id' => ['userId'],
        'name' => ['notEmptyOneLineString'],
        'birth_date' => ['isValidDate'],
      ])
      ->processPost();
    
    $this->assertEquals(FALSE, $validationResult);
    $this->assertEquals(TRUE, !empty($postValidator->getPostValidationMessages()));
  }
  
  public function testPostDataWithArrayValue() {
    $_POST = [
      'multiple_values' => ['997', '786', '665'],
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
      ->processPost();

    $this->assertEquals(TRUE, $validationResult);
    $this->assertEquals(TRUE, empty($postValidator->getPostValidationMessages()));
  }

  public function testReadMeExamples() {
    # chaining validators
    $validator = new Validator();
    $result = $validator
                ->value('99z9-d_')
                ->addValidationRule('is_string')
                ->addValidationRule('alphanumeric')
                ->process(); # false; only letters and numbers allowed
    $this->assertEquals(FALSE, $result);
    
    $validator = new Validator();
    $result = $validator->alphanumeric('99z9-d_'); # false; only letters and numbers allowed
    $this->assertEquals(FALSE, $result);
    
    $this->assertEquals(TRUE, (new Validator())->positiveInteger(99999));
    $this->assertEquals(FALSE, (new Validator())->positiveInteger('9999s9'));
    $this->assertEquals(TRUE, (new Validator())->notEmptyOneLineString('def54%'));
    $this->assertEquals(FALSE, (new Validator())->notEmptyOneLineString(''));
    
    $validator = new Validator();
    $result = $validator
            ->createRegexRule('alphanumeric', '/^[a-z0-9\-_]+$/i') # overites existing rule
            ->value('abc_-9')
            ->addValidationRule('alphanumeric')
            ->process();
    $this->assertEquals(TRUE, $result);
    
    $validator = new Validator(['alphanumeric' => '/^[a-z0-9\-_]+$/i']);
    $result = $validator
            ->value('abc_-9')
            ->addValidationRule('alphanumeric')
            ->process();
    $this->assertEquals(TRUE, $result);
    
    $validator = new Validator();
    $result = $validator
            ->setDateFormat('m-d-Y')
            ->value('2000-12-12')
            ->addValidationRule('isValidDate') # method of the [KrisRo\Validator\Validator] class
            ->process(); # false, wrong date format
    $this->assertEquals(FALSE, $result);
    
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
      ->processPost();
    $this->assertEquals(TRUE, $validationResult);



    $_POST = [
      'boolean_collection' => ['brand' => 0, 'model' => '1', 'width' => 0, 'height' => 1], # will validate values recursively 
      'id' => 'DF-999',
    ];

    $postValidator = new Validator();

    $validationResult = $postValidator
      ->addPostValidationMessages([
        'id' => 'Invalid ID',
        'boolean_collection' => 'Invalid Multiple Boolean Values',
      ])
      ->addPostValidationRules([
        'id' => ['notEmptyOneLineString'],
        'boolean_collection' => ['boolean'],
      ])
      ->processPost();
    $this->assertEquals(TRUE, $validationResult);

  }

  public function testStringLength() {
    $this->assertEquals(FALSE, (new Validator())->value('xx')->minLength(3));
    $this->assertEquals(FALSE, (new Validator())->minLength(3, 'xx'));
    
    $this->assertEquals(TRUE, (new Validator())->value('xxx')->minLength(3));
    $this->assertEquals(TRUE, (new Validator())->minLength(3, 'xxx'));
    
    $this->assertEquals(TRUE, (new Validator())->value('xxx')->maxLength(3));
    $this->assertEquals(FALSE, (new Validator())->maxLength(3, 'xxxx'));
    
    $this->assertEquals(TRUE, (new Validator())->value('xxx')->isLength(3));
    $this->assertEquals(TRUE, (new Validator())->isLength(3, 'xxx'));
    
    $this->assertEquals(FALSE, (new Validator())->value('#Pwd123')->addValidationRule(['minLength', 8])->process());
    $this->assertEquals(TRUE, (new Validator())->value('#Pwd1239')->addValidationRule(['maxLength', 8])->process());
    
    $this->assertEquals(FALSE, (new Validator())->value('#Pwd123')->addValidationRule('is_string')->addValidationRule(['minLength', 8])->process());
    
    
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
      ->processPost();
    $this->assertEquals(TRUE, $validationResult);
  }
  
  public function testNumericalLimits() {
    $this->assertEquals(FALSE, (new Validator())->value(10)->smallerThan(9.89));
    $this->assertEquals(FALSE, (new Validator())->smallerThan(9.89, 10));
    
    $this->assertEquals(TRUE, (new Validator())->value(10.1)->smallerThan(99));
    $this->assertEquals(TRUE, (new Validator())->smallerThan(99, 10.1));

    $this->assertEquals(FALSE, (new Validator())->value(1.05)->greaterThan(99.2));
    $this->assertEquals(FALSE, (new Validator())->greaterThan(99.2, 1.05));
    
    $this->assertEquals(TRUE, (new Validator())->value(103.5)->greaterThan(99.3));
    $this->assertEquals(TRUE, (new Validator())->greaterThan(99.3, 103.5));
    
    $this->assertEquals(FALSE, (new Validator())->value(1010.05)->between(99.2, 201));
    $this->assertEquals(FALSE, (new Validator())->between(99.2, 201, 1010.05));
    
    $this->assertEquals(TRUE, (new Validator())->value(101.05)->between(99.2, 201));
    $this->assertEquals(TRUE, (new Validator())->between(99.2, 201, 101.05));
    
    $this->assertEquals(FALSE, (new Validator())->value(99.2)->between(99.2, 201));
    $this->assertEquals(FALSE, (new Validator())->between(99.2, 201, 99.2));

    /**
     * Form values are passed as strings, 
     * so PHP engine will convert them to the type expected by the methods
     */
    
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
      ->processPost();
    $this->assertEquals(TRUE, $validationResult);
    
    $this->assertEquals(TRUE, empty($postValidator->getPostValidationMessages()));

    $validationResult = $postValidator
      ->addPostValidationMessages([
        'id' => 'Invalid ID',
        'int_value' => 'Invalid Positive Integer',
        'between_value' => 'Invalid Between Values',
      ])
      ->addPostValidationRules([
        'id' => ['notEmptyOneLineString'],
        'int_value' => ['positiveInteger', ['greaterThan', 'limit' => 9999]],
        'between_value' => ['positiveInteger', ['between', 'lowerLimit' => 9999, 'upperLimit' => 101]],
      ])
      ->processPost();
    
    $this->assertEquals(FALSE, $validationResult);
    $this->assertEquals(TRUE, count($postValidator->getPostValidationMessages()) === 2);
  }
  
  public function testOptionalPostField() {
    $_POST = [
      'optional_multiple_values' => ['997', '786', '665'], # will validate values recursively 
      'id' => 'DF-999',
    ];

    $postValidator = new Validator();

    $validationResult = $postValidator
      ->addPostValidationMessages([
        'id' => 'Invalid ID',
        'optional_multiple_values' => 'Invalid Multiple Values',
      ])
      ->addPostValidationRules([
        'id' => ['notEmptyOneLineString'],
        'optional_multiple_values' => ['positiveInteger', 'isOptional'],
      ])
      ->processPost();
    $this->assertEquals(TRUE, $validationResult);

    $_POST = [
      'optional_multiple_values' => [], # optional field 
      'id' => 'DF-999',
    ];

    $validationResult = $postValidator
      ->addPostValidationMessages([
        'id' => 'Invalid ID',
        'optional_multiple_values' => 'Invalid Multiple Values',
      ])
      ->addPostValidationRules([
        'id' => ['notEmptyOneLineString'],
        'optional_multiple_values' => ['positiveInteger', 'isOptional'],
      ])
      ->processPost();
    $this->assertEquals(TRUE, $validationResult);
  }

  public function testValidEmail() {
    $this->assertEquals(TRUE, (new Validator())->email('some-email@domain.tld'));
    $this->assertEquals(TRUE, (new Validator())->value('some-email@domain.tld')->addValidationRule('is_string')->addValidationRule('email')->process());
    
    $this->assertEquals(TRUE, (new Validator())->isValidEmail(Validator::EMAIL_VALIDATOR_PHP, 'some-email@domain.tld'));
    $this->assertEquals(TRUE, (new Validator())->isValidEmail(Validator::EMAIL_VALIDATOR_REGEXP, 'some-email@domain.tld'));
    $this->assertEquals(FALSE, (new Validator())->isValidEmail(Validator::EMAIL_VALIDATOR_REGEXP, 'much.”more\ unusual”@example.com'));
    $this->assertEquals(TRUE, (new Validator())->isValidEmail(Validator::EMAIL_VALIDATOR_SIMPLIFIED, 'some-email@domain.tld'));

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
      ->processPost();
    
    $this->assertEquals(FALSE, $validationResult);
    $this->assertEquals(TRUE, count($postValidator->getPostValidationMessages()) === 1);
  }
  
  public function testStrongPassword() {
    $this->assertEquals(FALSE, (new Validator())->strongPassword('password'));
    $this->assertEquals(FALSE, (new Validator())->strongPassword('password123'));
    $this->assertEquals(FALSE, (new Validator())->strongPassword('Password123'));
    $this->assertEquals(FALSE, (new Validator())->strongPassword('#Pwd123'));
    $this->assertEquals(TRUE, (new Validator())->strongPassword('#Password123'));
    
    $_POST = [
      'password' => '#Password123',
    ];

    $postValidator = new Validator();
    
    $validationResult = $postValidator
      ->addPostValidationMessages([
        'password' => 'Password To Week',
      ])
      ->addPostValidationRules([
        'password' => ['strongPassword'],
      ])
      ->processPost();
    $this->assertEquals(TRUE, $validationResult);
    
    $this->assertEquals(TRUE, empty($postValidator->getPostValidationMessages()));
  }
  
  public function testParanoiaPassword() {
    $this->assertEquals(TRUE, (new Validator())->paranoiaStrongPassword('#Password123'));
    $this->assertEquals(FALSE, (new Validator())->paranoiaStrongPassword('#Passrs1'));
    $this->assertEquals(FALSE, (new Validator())->paranoiaStrongPassword('#Passsword123'));
    
    $_POST = [
      'password' => '#Passrs1',
    ];

    $postValidator = new Validator();
    
    $validationResult = $postValidator
      ->addPostValidationMessages([
        'password' => 'Password To Week',
      ])
      ->addPostValidationRules([
        'password' => [['minLength', 10], 'paranoiaStrongPassword'],
      ])
      ->processPost();
    $this->assertEquals(FALSE, $validationResult);
  }
  
  public function testStrongPasswordCracklibRuntime() {
    $checker = new CracklibCheck();
    $this->assertEquals(FALSE, (new Validator())->value('password123456')->addValidationRule([$checker, 'testPassword'])->process());
    $this->assertEquals(FALSE, empty($checker->getResponseMessage()));
    
    $checker = new CracklibCheck();
    $this->assertEquals(FALSE, (new Validator())->value('Password123')->addValidationRule(['minLength', 6])->addValidationRule([$checker, 'testPassword'])->process());
    
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
      ->processPost();
    $this->assertEquals(TRUE, $validationResult);

    $this->assertEquals(TRUE, empty($postValidator->getPostValidationMessages()));
    $this->assertEquals(FALSE, empty($checker->getResponseMessage()));
  }
}

class TestCallback {
  public function testValidString($value): bool {
    return is_string($value);
  }
  
  public static function staticTestValidString($value): bool {
    return is_string($value);
  }
}
