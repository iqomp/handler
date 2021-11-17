<?php

/**
 * Data processor
 * @package iqomp/handler
 * @version 1.1.1
 */

namespace Iqomp\Handler;

use Iqomp\Formatter\Formatter;
use Iqomp\Validator\Form;

class Handler
{
    protected string $model;
    protected array $formatter;

    protected array $arguments = [];
    protected array $disalow_methods = [];
    protected array $error = [];
    protected array $errors_code = [];
    protected array $forms = [];
    protected string $method;
    protected string $pager_rpp = 'rpp';
    protected string $pager_page = 'page';
    protected string $pager_total = 'total';
    protected $result;

    protected function getErrorTransCode(int $code)
    {
        return $this->errors_code[$code] ?? $code;
    }

    // create
    // -> validate, create, getOne, format
    // createMany
    // -> validate, createMany, return bool
    // getOne
    // -> getOne, format
    // get
    // -> get, format, inc pagination
    // set
    // -> validate, set, return bool
    public function __call(string $name, array $arguments)
    {
        $this->arguments = $arguments;
        $this->method = $name;

        if (in_array($name, $this->disalow_methods)) {
            $this->error = [
                'code' => $this->getErrorTransCode(500),
                'message' => 'Calling this method is not allowed'
            ];

            return null;
        }

        if (isset($this->forms[$name])) {
            $f_params = (array)($arguments[1] ?? []);
            $f_name   = $this->forms[$name];
            $f_object = (object)($arguments[0] ?? []);
            $form = new Form($f_name, $f_params);

            $result = $form->validate($f_object);

            if (!$result) {
                $this->error = [
                    'code' => $this->getErrorTransCode(422),
                    'data' => $form->getErrors()
                ];

                return null;
            }

            $arguments[0] = (array)$result;
        }

        $model = $this->model;

        // call before_[method]
        $before_method = 'before_' . $name;
        if (method_exists($this, $before_method)) {
            $arguments = $this->$before_method($arguments);
        }

        // forward it to model
        $result = call_user_func_array([$model, $name], $arguments);
        if (!$result) {
            $error = $model::lastError();
            if ($error) {
                $this->error = [
                    'code' => $this->getErrorTransCode(500),
                    'message' => $error
                ];

                return null;
            }

            return $result;
        }

        if ($name == 'create') {
            $result = $model::getOne(['id' => $result]);
        }

        // call after_[method]
        $after_method = 'after_' . $name;
        if (method_exists($this, $after_method)) {
            $after_args = $arguments;
            $after_args[] = $result;
            call_user_func_array([$this, $after_method], $after_args);
        }

        $format = null;
        if (in_array($name, ['getOne', 'create'])) {
            $format = 'format';
        } elseif ($name == 'get') {
            $format = 'formatMany';
        }

        if ($result && $format) {
            $f_name = $this->formatter['name'];
            $f_opts = $this->formatter['options'];
            $result = Formatter::$format($f_name, $result, $f_opts);
        }

        $this->result = $result;
        return $result;
    }

    public function error()
    {
        return $this->error;
    }

    public function result()
    {
        return $this->result;
    }

    public function pagination()
    {
        if (in_array($this->method, $this->disalow_methods)) {
            return null;
        }

        if ($this->method != 'get') {
            return null;
        }

        $model = $this->model;
        $cond  = $this->arguments[0] ?? [];
        $rpp   = $this->arguments[1] ?? 0;
        $page  = $this->arguments[2] ?? 1;
        $total = $model::count($cond);

        return [
            $this->pager_rpp   => (int)$rpp,
            $this->pager_page  => (int)$page,
            $this->pager_total => (int)$total
        ];
    }
}
