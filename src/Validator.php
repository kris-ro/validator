<?php

namespace KrisRo\Validator;

class Validator {

  const EMAIL_VALIDATOR_REGEXP = 'REGEXP';
  const EMAIL_VALIDATOR_SIMPLIFIED = '@';
  const EMAIL_VALIDATOR_PHP = 'PHP';

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
    'integer' => '/^-?\d+$/',
    'positiveInteger' => '/^\d+$/',
    'boolean' => '/^(1|0){1}$/',
    'notEmptyOneLineString' => '/^[^\n\r]+$/',
    'float' => '/^-?[0-9]+(\.[0-9]+)?$/',
    'positiveFloat' => '/^[0-9]+(\.[0-9]+)?$/',
    'internationalPhone' => '/\+(9[976]\d|8[987530]\d|6[987]\d|5[90]\d|42\d|3[875]\d|2[98654321]\d|9[8543210]|8[6421]|6[6543210]|5[87654321]|4[987654310]|3[9643210]|2[70]|7|1)\d{1,14}$/',
    'alphanumeric' => '/^[0-9a-zA-Z]+$/',
    'text' => '/^.*$/is',
    'mandatoryText' => '/^.+$/is',
    'website' => '/^(http:\/\/)?(www\.)?([a-zA-Z0-9\-_]+\.)+[a-zA-Z]{2,5}(\/([a-zA-Z0-9=&\?\.\-_]+)?)*$/',
    'fileName' => '/^[a-zA-Z0-9_\.\- ]+$/',
    'strongPassword' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[#@$!%*?&^()+=\-_\[\]\';,\.\/{}|:<>?~`])[A-Za-z\d#@$!%*?&^()+=\-_\[\]\';,\.\/{}|:<>?~`]{8,}$/',
    // from : https://stackoverflow.com/questions/201323/how-can-i-validate-an-email-address-using-a-regular-expression
    'email' => <<<REGEXEMAIL
               /([-!#-'*+\/-9=?A-Z^-~]+(\.[-!#-'*+\/-9=?A-Z^-~]+)*|"([]!#-[^-~ \t]|(\\[\t -~]))+")@([0-9A-Za-z]([0-9A-Za-z-]{0,61}[0-9A-Za-z])?(\.[0-9A-Za-z]([0-9A-Za-z-]{0,61}[0-9A-Za-z])?)*|\[((25[0-5]|2[0-4][0-9]|1[0-9]{2}|[1-9]?[0-9])(\.(25[0-5]|2[0-4][0-9]|1[0-9]{2}|[1-9]?[0-9])){3}|IPv6:((((0|[1-9A-Fa-f][0-9A-Fa-f]{0,3}):){6}|::((0|[1-9A-Fa-f][0-9A-Fa-f]{0,3}):){5}|[0-9A-Fa-f]{0,4}::((0|[1-9A-Fa-f][0-9A-Fa-f]{0,3}):){4}|(((0|[1-9A-Fa-f][0-9A-Fa-f]{0,3}):)?(0|[1-9A-Fa-f][0-9A-Fa-f]{0,3}))?::((0|[1-9A-Fa-f][0-9A-Fa-f]{0,3}):){3}|(((0|[1-9A-Fa-f][0-9A-Fa-f]{0,3}):){0,2}(0|[1-9A-Fa-f][0-9A-Fa-f]{0,3}))?::((0|[1-9A-Fa-f][0-9A-Fa-f]{0,3}):){2}|(((0|[1-9A-Fa-f][0-9A-Fa-f]{0,3}):){0,3}(0|[1-9A-Fa-f][0-9A-Fa-f]{0,3}))?::(0|[1-9A-Fa-f][0-9A-Fa-f]{0,3}):|(((0|[1-9A-Fa-f][0-9A-Fa-f]{0,3}):){0,4}(0|[1-9A-Fa-f][0-9A-Fa-f]{0,3}))?::)((0|[1-9A-Fa-f][0-9A-Fa-f]{0,3}):(0|[1-9A-Fa-f][0-9A-Fa-f]{0,3})|(25[0-5]|2[0-4][0-9]|1[0-9]{2}|[1-9]?[0-9])(\.(25[0-5]|2[0-4][0-9]|1[0-9]{2}|[1-9]?[0-9])){3})|(((0|[1-9A-Fa-f][0-9A-Fa-f]{0,3}):){0,5}(0|[1-9A-Fa-f][0-9A-Fa-f]{0,3}))?::(0|[1-9A-Fa-f][0-9A-Fa-f]{0,3})|(((0|[1-9A-Fa-f][0-9A-Fa-f]{0,3}):){0,6}(0|[1-9A-Fa-f][0-9A-Fa-f]{0,3}))?::)|(?!IPv6:)[0-9A-Za-z-]*[0-9A-Za-z]:[!-Z^-~]+)])/
               REGEXEMAIL,
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
   * Messages displayed to users on validation failure
   * <code>
   * ['field_name' => 'Invalid field message', 'another_field_name' => 'Invalid field message']
   * </code>
   *
   * @var array
   */
  protected $postFieldsValidationRulesMessages = [];

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

      if (is_string($validationRuleName[0]) && method_exists($this, $validationRuleName[0])) {
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
      } elseif (method_exists($this, $ruleName[0])) {
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

    } elseif ('isOptional' == $ruleName) {
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
      if ($this->processRule($rule) === FALSE) {
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
   * Returns the currently used date format
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
    if (($date ?? $this->value) === NULL) {
      trigger_error('Invalid date passed. Expected : ' . $this->dateFormat, E_USER_ERROR);
    }

    \DateTime::createFromFormat($this->dateFormat, ($date ?: $this->value));
    $errors = \DateTime::getLastErrors();

    if (!$errors) {
      return TRUE;
    }

    if (($errors['warning_count'] ?? 0) + ($errors['error_count'] ?? 0) > 0) {
      return FALSE;
    }

    return TRUE;
  }

  public function paranoiaStrongPassword(string $value): bool {
    if (!($value ?? $this->value)) {
      return FALSE;
    }

    if (!preg_match($this->validationRules['strongPassword'], ($value ?? $this->value))) {
      return FALSE;
    }

    $successiveCharacters = [];
    $characterCount = [];
    $totalCharacterCount = floor(strlen($value ?? $this->value) / 3);

    $lastCharacter = '';
    foreach (mb_str_split($value ?? $this->value, 1) as $character) {
      $characterCount[$character] = ($characterCount[$character] ?? '') . $character;


      if (strlen($characterCount[$character]) > $totalCharacterCount) {
        return FALSE;
      }

      if ($character == $lastCharacter) {
        $successiveCharacters[$character] = ($successiveCharacters[$character] ?? '') . $lastCharacter;
      } else {
        $successiveCharacters[$character] = $character;
      }

      if (strlen($successiveCharacters[$character] ?? '') > 2) {
        return FALSE;
      }

      $lastCharacter = $character;
    }

    return TRUE;
  }


  /**
   * Validate email address
   *
   * @param string|null $emailAddress
   * @param string|null $mode
   *
   * @return bool
   */
  public function isValidEmail(string $mode, ?string $value = NULL): bool {
    if (!($value ?? $this->value)) {
      return FALSE;
    }

    switch ($mode) {
      case self::EMAIL_VALIDATOR_PHP;
      case self::EMAIL_VALIDATOR_REGEXP;
        return $this->processRule('email', $value);
      case self::EMAIL_VALIDATOR_SIMPLIFIED:
      case 'simplifiedEmail':
        return $this->isSimplifiedEmail($value);
    }

    return filter_var($value, FILTER_VALIDATE_EMAIL);
  }

  /**
   * Validate email address (just if @ is present)
   *
   * @param string|null $emailAddress
   *
   * @return bool
   */
  public function isSimplifiedEmail(?string $emailAddress = NULL): bool {
    if (!($emailAddress ?? $this->value)) {
      return FALSE;
    }

    return strpos($emailAddress, '@') !== FALSE;
  }

  /**
   * Fails a string with length below limit
   *
   * @param int $limit
   * @param string|null $value
   *
   * @return bool
   */
  public function minLength(int $limit, ?string $value = NULL): bool {
    if (($value ?? $this->value) === NULL) {
      trigger_error('No value to compare', E_USER_ERROR);
    }

    if (strlen($value ?? $this->value) >= $limit) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Fails a string with length above limit
   *
   * @param int $limit
   * @param string|null $value
   *
   * @return bool
   */
  public function maxLength(int $limit, ?string $value = NULL): bool {
    if (($value ?? $this->value) === NULL) {
      trigger_error('No value to compare', E_USER_ERROR);
    }

    if (strlen($value ?? $this->value) <= $limit) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Fails a string with length different than limit
   *
   * @param int $limit
   * @param string|null $value
   *
   * @return bool
   */
  public function isLength(int $limit, ?string $value = NULL): bool {
    if (($value ?? $this->value) === NULL) {
      trigger_error('No value to compare', E_USER_ERROR);
    }

    if (strlen($value ?? $this->value) == $limit) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Fails a number greater than limit
   *
   * @param float $limit
   * @param float|null $value
   *
   * @return bool
   */
  public function smallerThan(float $limit, ?float $value = NULL): bool {
    if (($value ?? $this->value) === NULL) {
      trigger_error('No value to compare', E_USER_ERROR);
    }

    if (($value ?? $this->value) < $limit) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Fails a value smaller than limit
   *
   * @param float $limit
   * @param float|null $value
   *
   * @return bool
   */
  public function greaterThan(float $limit, ?float $value = NULL): bool {
    if (($value ?? $this->value) === NULL) {
      trigger_error('No value to compare', E_USER_ERROR);
    }

    if (($value ?? $this->value) > $limit) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Fails a value outside range
   *
   * @param float $lowerLimit
   * @param float $upperLimit
   * @param float|null $value
   *
   * @return bool
   */
  public function between(float $lowerLimit, float $upperLimit, ?float $value = NULL) {
    if (($value ?? $this->value) === NULL) {
      trigger_error('No value to compare', E_USER_ERROR);
    }

    if (($value ?? $this->value) >= $upperLimit) {
      return FALSE;
    }

    if (($value ?? $this->value) <= $lowerLimit) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Validates value against rule
   *
   * @param string|array $rule
   * @return bool
   */
  protected function processRule(string|array $rule, $value = NULL): bool {
    if (is_array($rule)) {
      if (is_callable($rule)) {
        return call_user_func($rule, ($value ?: $this->value), $this->post, $this->postValidationMessages);
      }

      if (is_string($rule[0]) && method_exists($this, $rule[0])) {
        $method = $rule[0];

        $args = array_slice($rule, 1);
        if (!$this->value) {
          $args += ['value' => $value];
        }

        return $this->$method(...$args);
      }

      trigger_error('Invalid validation rule triggered at data validation', E_USER_ERROR);
    }

    if (isset($this->validationRules[$rule])) {
      // $this->value needs to be string
      // $this->validationRules contains regex patterns used with preg_match
      if (is_string(($value ?? $this->value)) || is_int(($value ?? $this->value))) {
        return preg_match($this->validationRules[$rule], (string) ($value ?? $this->value)) ? TRUE : FALSE;

      } elseif (is_array(($value ?: $this->value))) {
        foreach (($value ?: $this->value) as $iterationValue) {
          if ($this->processRule($rule, $iterationValue) === FALSE) {
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

    return FALSE;
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
 
    // if marked as optional value, return TRUE if field empty
    if (in_array('isOptional', $rules)) {
      if (empty($_POST[$field])) {
        return TRUE;
      }
      unset($rules[current(array_keys($rules, 'isOptional'))]);
    }

    foreach ($rules as $rule) {
      $this->value = NULL;
      if ($this->processRule($rule, $_POST[$field]) === FALSE) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Override the error message for post field
   *
   * @param string $field
   * @param string $message
   *
   * @return string
   */
  public function setPostValidationMessage(string $field, string $message): string {
    return $this->postFieldsValidationRulesMessages[$field] = $message;
  }

  public function getPostValidationMessages(): array {
    return $this->postValidationMessages;
  }

  public function getPost(): array {
    return $this->post;
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