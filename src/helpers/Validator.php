<?php

namespace App\Helpers;

class Validator
{
    private array $errors = [];
    private array $data = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function validate(array $rules): bool
    {
        foreach ($rules as $field => $ruleSet) {
            $ruleArray = explode('|', $ruleSet);
            
            foreach ($ruleArray as $rule) {
                $this->applyRule($field, $rule);
            }
        }
        
        return empty($this->errors);
    }

    private function applyRule(string $field, string $rule): void
    {
        $value = $this->data[$field] ?? null;

        if ($rule === 'required') {
            if (empty($value) && $value !== '0') {
                $this->addError($field, "Поле '$field' обязательно для заполнения");
            }
            return;
        }

        if (strpos($rule, 'min:') === 0) {
            $min = (int)substr($rule, 4);
            if (strlen($value) < $min) {
                $this->addError($field, "Поле '$field' должно быть не менее $min символов");
            }
            return;
        }

        if (strpos($rule, 'max:') === 0) {
            $max = (int)substr($rule, 4);
            if (strlen($value) > $max) {
                $this->addError($field, "Поле '$field' должно быть не более $max символов");
            }
            return;
        }

        if ($rule === 'email') {
            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $this->addError($field, "Поле '$field' должно быть корректным email адресом");
            }
            return;
        }

        if ($rule === 'numeric') {
            if (!is_numeric($value)) {
                $this->addError($field, "Поле '$field' должно быть числом");
            }
            return;
        }

        if ($rule === 'date') {
            $d = \DateTime::createFromFormat('Y-m-d', $value);
            if (!$d || $d->format('Y-m-d') !== $value) {
                $this->addError($field, "Поле '$field' должно быть корректной датой");
            }
            return;
        }

        if (strpos($rule, 'before:') === 0) {
            $beforeDate = substr($rule, 7);
            $d = \DateTime::createFromFormat('Y-m-d', $value);
            $before = new \DateTime($beforeDate);
            if ($d && $d >= $before) {
                $this->addError($field, "Дата должна быть раньше $beforeDate");
            }
            return;
        }

        if ($rule === 'confirmed') {
            $confirmField = $field . '_confirmation';
            if ($value !== ($this->data[$confirmField] ?? null)) {
                $this->addError($field, "Поля '$field' и подтверждение не совпадают");
            }
            return;
        }

        if ($rule === 'in:games,in:accounts,in:keys,in:subscriptions') {
            $allowed = explode(',', substr($rule, 3));
            if (!in_array($value, $allowed)) {
                $this->addError($field, "Недопустимое значение для '$field'");
            }
            return;
        }
    }

    private function addError(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getFirstError(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function getValidatedData(): array
    {
        return $this->data;
    }
}

// Функция-помощник для быстрой валидации
function validate(array $data, array $rules): Validator
{
    $validator = new Validator($data);
    $validator->validate($rules);
    return $validator;
}
