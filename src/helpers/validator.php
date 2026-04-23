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
    
    public static function make(array $data): self
    {
        return new self($data);
    }
    
    public function required(string $field, string $message = null): self
    {
        if (!isset($this->data[$field]) || trim($this->data[$field]) === '') {
            $this->errors[$field][] = $message ?? "Поле '$field' обязательно для заполнения";
        }
        return $this;
    }
    
    public function email(string $field, string $message = null): self
    {
        if (isset($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field][] = $message ?? "Некорректный email адрес";
        }
        return $this;
    }
    
    public function minLength(string $field, int $length, string $message = null): self
    {
        if (isset($this->data[$field]) && strlen($this->data[$field]) < $length) {
            $this->errors[$field][] = $message ?? "Минимальная длина поля '$field' - $length символов";
        }
        return $this;
    }
    
    public function maxLength(string $field, int $length, string $message = null): self
    {
        if (isset($this->data[$field]) && strlen($this->data[$field]) > $length) {
            $this->errors[$field][] = $message ?? "Максимальная длина поля '$field' - $length символов";
        }
        return $this;
    }
    
    public function matches(string $field, string $matchField, string $message = null): self
    {
        if (!isset($this->data[$field]) || !isset($this->data[$matchField]) || 
            $this->data[$field] !== $this->data[$matchField]) {
            $this->errors[$field][] = $message ?? "Поля не совпадают";
        }
        return $this;
    }
    
    public function date(string $field, string $message = null): self
    {
        if (isset($this->data[$field])) {
            $date = \DateTime::createFromFormat('Y-m-d', $this->data[$field]);
            if (!$date || $date->format('Y-m-d') !== $this->data[$field]) {
                $this->errors[$field][] = $message ?? "Некорректный формат даты";
            }
        }
        return $this;
    }
    
    public function dateNotOlderThan(string $field, int $year, string $message = null): self
    {
        if (isset($this->data[$field])) {
            $date = \DateTime::createFromFormat('Y-m-d', $this->data[$field]);
            if ($date && $date->format('Y') < $year) {
                $this->errors[$field][] = $message ?? "Дата не может быть старше $year года";
            }
        }
        return $this;
    }
    
    public function custom(string $field, callable $callback, string $message): self
    {
        if (isset($this->data[$field]) && !$callback($this->data[$field])) {
            $this->errors[$field][] = $message;
        }
        return $this;
    }
    
    public function fails(): bool
    {
        return !empty($this->errors);
    }
    
    public function passes(): bool
    {
        return empty($this->errors);
    }
    
    public function errors(): array
    {
        return $this->errors;
    }
    
    public function firstError(?string $field = null): ?string
    {
        if ($field && isset($this->errors[$field])) {
            return $this->errors[$field][0];
        }
        
        foreach ($this->errors as $fieldErrors) {
            if (!empty($fieldErrors)) {
                return $fieldErrors[0];
            }
        }
        
        return null;
    }
    
    public function getData(): array
    {
        return $this->data;
    }
}
