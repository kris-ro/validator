<?php

use PHPUnit\Framework\TestCase;
use KrisRo\Validator\Validator;

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
}

class TestCallback {
  public function testValidString($value): bool {
    return is_string($value);
  }
  
  public static function staticTestValidString($value): bool {
    return is_string($value);
  }
}
