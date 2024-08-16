<?php

namespace KrisRo\Validator;

class Validator {

  /**
   * The format that will be used for date checking
   * 
   * @var string
   */
  protected $dateFormat = 'Y-m-d';
  
  /**
   * Validation rules
   * 
   * @var array
   */
  protected $validationRules = [
    'positiveInteger' => '/^\d+$/',
    'integer' => '/^-?\d+$/',
    'password' => '/^[a-zA-Z0-9!@#\$\|%\^&*\(\)\[\]\{\}\-\.=\s]{6,}$/',
    'boolean' => '/^(1|0){1}$/',
    'notEmptyOneLineString' => '/^[^\n\r]+$/',
    'positiveFloat' => '/^[0-9]+(\.[0-9]+)?$/',
    'float' => '/^-?[0-9]+(\.[0-9]+)?$/',
    'internationalPhone' => '/\+(9[976]\d|8[987530]\d|6[987]\d|5[90]\d|42\d|3[875]\d|2[98654321]\d|9[8543210]|8[6421]|6[6543210]|5[87654321]|4[987654310]|3[9643210]|2[70]|7|1)\d{1,14}$/',
    'alphanumeric' => '/^[0-9a-zA-Z]+$/',
    'text' => '/^.*$/is',
    'mandatoryText' => '/^.+$/is',
    'website' => '/^(http:\/\/)?(www\.)?([a-zA-Z0-9\-_]+\.)+[a-zA-Z]{2,5}(\/([a-zA-Z0-9=&\?\.\-_]+)?)*$/',
    'fileName' => '/^[a-zA-Z0-9_\.\-]+$/',
  ];

  /**
   * Array with validation rules indexed by the POST field they are applied to.
   * <code>
   * ['field_name' => ['is_string', 'notEmptyOneLineString'], 'another_field_name' => ['is_string', 'notEmptyOneLineString']]
   * </code>
   * 
   * @var array
   */
  protected $postFieldsValidationRules = [];
  
  /**
   * Messages for failed POST filed validation
   * <code>
   * ['field_name' => 'Invalid field message', 'another_field_name' => 'Invalid field message']
   * </code>
   * 
   * @var array
   */
  protected $postFieldsMessages = [];

  /**
   * TRUE if POST form validated successfully, FALSE otherwise
   * 
   * @var boolean
   */
  protected $postValid = FALSE;
  
  /**
   * Validated POST data
   * 
   * @var mixed: FALSE if data validation fails, array otherwise
   */
  protected $post = [];

  /**
   * Validation errors
   * 
   * @var array
   */
  protected $postValidationMessages = [];


  /**
   * Value to be checked
   * 
   * @var mixt
   */
  protected $value;

  /**
   * Selected validation rules for <code>$this->value</code>
   * 
   * @var array
   */
  protected $appliedRules = [];

  /**
   * Short hand call for <code>((new Validator())->positiveInteger(99)</code>
   * 
   * @param string $ruleName
   * @param array $args
   * 
   * @return bool
   */
  public function __call($ruleName, $args) {
    if (!($args[0] ?? NULL)) {
      return FALSE;
    }

    $value = $args[0];
    if (isset($this->validationRules[$ruleName])) {
      $value = (string) $args[0];
    }

    return $this->value($value)->addValidationRule($ruleName)->process();
  }
  
  /**
   * Accepts rules to be added to <code>$this->validationRules</code>
   * 
   * @param array $rules
   */
  public function __construct(?array $rules = []) {
      $this->createRegexRules($rules);
  }
  
  /**
   * Appends multiple custom rules to <code>$this->validationRules</code> collection
   * 
   * @param array $rules
   * 
   * @return self
   */
  public function createRegexRules(array $rules): self {
    foreach ($rules as $ruleName => $regexExpression) {
      $this->createRegexRule($ruleName, $regexExpression);
    }

    return $this;
  }
  
  /**
   * Appends custom rules to <code>$this->validationRules</code> collection
   * 
   * @param string $ruleName
   * @param string $regexExpression
   * 
   * @return self
   */
  public function createRegexRule(string $ruleName, string $regexExpression): self {
    if (!preg_match('/^[a-z0-9\-_]+$/i', $ruleName) || !is_string($regexExpression)) {
      trigger_error('Invalid validation rule. Should be: ["rule_name" => "regex expression"]', E_USER_ERROR);
    }

    $this->validationRules[$ruleName] = $regexExpression;

    return $this;
  }
  
  /**
   * Specify the value that needs to be validated
   * 
   * @param mixt $value
   * 
   * @return self
   */
  public function value($value = NULL): self {
    $this->value = $value;
    
    return $this;
  }

  /**
   * Adds rule to the collection <code>$this->appliedRules</code> used to validate the <code>$this->value</code>
   * 
   * @param string|array $validationRuleName
   * 
   * @return self
   */
  public function addValidationRule(string|array $validationRuleName): self {
    if (is_array($validationRuleName)) {
      if (is_callable($validationRuleName)) {
        $this->appliedRules[] = $validationRuleName;
        return $this;
      }

      trigger_error('Unknown validation rule', E_USER_ERROR);
    }

    if ($this->isRule($validationRuleName)) {
      $this->appliedRules[] = $validationRuleName;
      return $this;

    } elseif (method_exists($this, $validationRuleName)) {
      $this->appliedRules[] = $validationRuleName;
      return $this;

    } elseif (function_exists($validationRuleName)) {
      $this->appliedRules[] = $validationRuleName;
      return $this;
    }

    trigger_error('Unknown validation rule', E_USER_ERROR);
  }
  
  public function addPostValidationMessages(array $fieldMessages): self {
    $this->postFieldsValidationRulesMessages = [];

    foreach ($fieldMessages as $field => $message) {
      $this->postFieldsValidationRulesMessages[$field] = $message;
    }
    
    return $this;
  }
  
  public function resetPostFieldsValidationRules(): self {
    $this->postFieldsValidationRules = [];
    
    return $this;
  }
  
  /**
   * Setup validation rules for the form <code>
   * ['field_name' => ['is_string', 'notEmptyOneLineString']]
   * </code>
   * 
   * @param array $fieldsRules
   * 
   * @return self
   */
  public function addPostValidationRules(array $fieldsRules): self {
    if (array_diff(array_keys($this->postFieldsValidationRulesMessages), array_keys($fieldsRules))) {
      trigger_error('Fields missmatch between messages and validation rules', E_USER_ERROR);
    }
    
    $this->postFieldsValidationRules = [];

    foreach ($fieldsRules as $field => $fieldRules) {
      $this->addPostValidationRulesForField($field, $fieldRules);
    }

    return $this;
  }
  
  /**
   * Setup validation rules for field
   * 
   * @param string $field
   * @param array $fieldRules
   * 
   * @return self
   */
  public function addPostValidationRulesForField(string $field, array $fieldRules): self {
    if (empty($this->postFieldsValidationRules[$field])) {
      $this->postFieldsValidationRules[$field] = [];
    }

    foreach ($fieldRules as $rule) {
      $this->addPostValidationRule($field, $rule);
    }

    return $this;
  }
  
  /**
   * Validate rule and add it to collection
   * 
   * @param string $field
   * @param string|array $ruleName
   * 
   * @return self
   */
  public function addPostValidationRule(string $field, string|array $ruleName): self {
    if (is_array($ruleName)) {
      if (is_callable($ruleName)) {
        $this->postFieldsValidationRules[$field][] = $ruleName;
        return $this;
      }

      trigger_error('Unknown validation rule', E_USER_ERROR);
    }

    if ($this->isRule($ruleName)) {
      $this->postFieldsValidationRules[$field][] = $ruleName;
      return $this;

    } elseif (method_exists($this, $ruleName)) {
      $this->postFieldsValidationRules[$field][] = $ruleName;
      return $this;

    } elseif (function_exists($ruleName)) {
      $this->postFieldsValidationRules[$field][] = $ruleName;
      return $this;
    }

    trigger_error('Unknown validation rule', E_USER_ERROR);
  }

  /**
   * Check <code>$this->value</code> against rules specified in <code>$this->appliedRules</code>
   * 
   * @return bool
   */
  public function process(): bool {
    foreach ($this->appliedRules as $rule) {
      if (!$this->processRule($rule)) {
        return FALSE;
      }
    }
    
    return TRUE;
  }
  
  /**
   * Set the desired date format
   * 
   * @param string $dateFormat
   * 
   * @return self
   */
  public function setDateFormat(string $dateFormat): self {
    $this->dateFormat = $dateFormat;
    
    return $this;
  }
  
  /**
   * 
   * @return string
   */
  public function getDateFormat(): string {
    return $this->dateFormat;
  }
  
  /**
   * Validate date
   * 
   * @param string $date
   * 
   * @return bool
   */
  public function isValidDate(?string $date = NULL): bool {
    \DateTime::createFromFormat($this->dateFormat, ($date ?: $this->value));
    $errors = \DateTime::getLastErrors();
    
    if ($errors['warning_count'] + $errors['error_count'] > 0) {
      return false;
    }
    
    return true;
  }
  
  /**
   * Validates value against rule
   * 
   * @param string|array $rule
   * @return bool
   */
  protected function processRule(string|array $rule, $value = NULL): bool {
    if (is_array($rule)) {
      return call_user_func($rule, ($value ?: $this->value));
    }
    
    if (isset($this->validationRules[$rule])) {
      // $this->value needs to be string
      // $this->validationRules contains regex patterns used with preg_match
      if (is_string(($value ?: $this->value))) {
        return preg_match($this->validationRules[$rule], ($value ?: $this->value)) ? TRUE : FALSE;

      } elseif (is_array(($value ?: $this->value))) {
        foreach (($value ?: $this->value) as $iterationValue) {
          if (!$this->processRule($rule, $iterationValue)) {
            return FALSE;
          }
        }
        
        return TRUE;
      }
    }
    
    if (method_exists($this, $rule)) {
      return $this->$rule($value) ? TRUE : FALSE;
    }
    
    if (function_exists($rule)) {
      return $rule($value ?: $this->value) ? TRUE : FALSE;
    }
    
    return false;
  }

  /**
   * Validate POST form
   * 
   * @return bool
   */
  public function processPost(): bool {
    $this->post = [];
    $this->postValidationMessages = [];

    if (count($this->postFieldsValidationRules)) {
      $this->postValid = TRUE;
    }

    foreach ($this->postFieldsValidationRules as $field => $rules) {
      if ($this->validatePost($field, $rules)) {
        $this->post[$field] = $_POST[$field];
      } else {
        $this->postValidationMessages[$field] = $this->postFieldsValidationRulesMessages[$field];
        $this->postValid = FALSE;
        $this->post = [];
      }
    }
    
    return $this->postValid;
  }

  /**
   * Validate form field
   * 
   * @param string $field
   * @param array $rules
   * 
   * @return bool
   */
  public function validatePost(string $field, array $rules): bool {
    if (!isset($_POST[$field])) {
      return FALSE;
    }

    foreach ($rules as $rule) {
      if (!$this->processRule($rule, $_POST[$field])) {
        return FALSE;
      }
    }
    
    return TRUE;
  }
  
  public function getPostValidationMessages(): array {
    return $this->postValidationMessages;
  }
  
  /**
   * Checks if specified rule exists in <code>$this->validationRules</code>
   * 
   * @param string $validationRuleName
   * 
   * @return bool
   */
  protected function isRule(string $validationRuleName): bool {
    return $validationRuleName && isset($this->validationRules[$validationRuleName]);
  }
}