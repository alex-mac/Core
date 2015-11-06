<?php

namespace UWDOEM\Framework\Table;

use UWDOEM\Framework\Filter\DummyFilter;
use UWDOEM\Framework\Visitor\VisitableTrait;
use UWDOEM\Framework\Row\RowInterface;
use UWDOEM\Framework\Row\RowBuilder;


class TableForm implements TableFormInterface {

    /** @var RowInterface */
    protected $_rowBuilder;

    protected $_rows;

    protected $_actions;

    protected $_isValid;

    protected $_validators;

    protected $_errors = [];

    protected $_onValidFunc;

    protected $_onInvalidFunc;

    use VisitableTrait;


    /** @return RowInterface */
    public function getPrototypicalRow() {
        return $this->_rowBuilder->build();
    }

    public function getHash() {
        return $this->getPrototypicalRow()->getHash();
    }

    public function getRows() {
        return $this->_rows;
    }

    public function getFilter() {
        return new DummyFilter();
    }

    public function getFieldBearer() {
        return $this->getPrototypicalRow()->getFieldBearer();
    }

    public function getSubForms() {
        return [];
    }

    public function getSubFormByName($name) {
        return null;
    }

    protected function validate() {

        $this->_isValid = true;

        $firstPrototypicalSlug = current($this->getPrototypicalRow()->getFieldBearer()->getFields())->getSlug();

        $submittedSlugs = array_keys($_POST);

        $submittedFirstSlugMatches = array_filter($submittedSlugs, function($postSlug) use ($firstPrototypicalSlug) {
            return strpos($postSlug, $firstPrototypicalSlug) !== false;
        });

        $rowPrefixes = array_map(function($name) use ($firstPrototypicalSlug) {
            return str_replace($firstPrototypicalSlug, "", $name);
        }, $submittedFirstSlugMatches);

        $rows = [];
        foreach ($rowPrefixes as $prefix) {
            $newRow = $this->_rowBuilder->build();

            foreach($this->getPrototypicalRow()->getFieldBearer()->getFields() as $name=>$field) {

                $suffix = implode("-",$field->getSuffixes());

                $newField = $newRow->getFieldBearer()->getFieldByName($name);

                $newField->addSuffix($suffix);
                $newField->addPrefix(trim($prefix, "-"));

                $slug = $newField->getSlug();

                if (isset($_POST[$slug])) {
                    $newField->setInitial($_POST[$slug]);
                }

                $newField->validate();
            }
            $rows[] = $newRow;
        }
        $this->_rows = $rows;

        // Validate each row exogenously
        foreach ($this->getRows() as $row) {
            foreach ($row->getFieldBearer()->getFields() as $name => $field) {
                if (array_key_exists($name, $this->_validators)) {
                    foreach($this->_validators[$name] as $validator) {
                        call_user_func_array($validator, [$field, $this]);
                    }
                }
            }
        }

        // See if there exist any invalid fields
        foreach ($this->getRows() as $row) {
            foreach ($row->getFieldBearer()->getFields() as $name => $field) {
                if (!$field->isValid()) {
                    $this->_isValid = False;
                    $this->addError("Please correct the indicated errors and resubmit the form.");
                    break;
                }
            }
        }
    }

    public function isValid() {
        if (!isset($this->_isValid)) {
            $this->validate();
        }
        return $this->_isValid;
    }

    public function onValid() {
        $args = array_merge([$this], func_get_args());
        return call_user_func_array($this->_onValidFunc, $args);
    }

    public function onInvalid() {
        $args = array_merge([$this], func_get_args());
        return call_user_func_array($this->_onInvalidFunc, $args);
    }

    public function getErrors() {
        return $this->_errors;
    }

    public function getActions() {
        return $this->_actions;
    }

    public function addError($error) {
        $this->_errors[] = $error;
    }

    public function __construct(
        RowBuilder $rowBuilder,
        callable $onValidFunc,
        callable $onInvalidFunc,
        $actions = [],
        $validators = []
    ) {
        $this->_actions = $actions;
        $this->_rowBuilder = $rowBuilder;

        $this->_onInvalidFunc = $onInvalidFunc;
        $this->_onValidFunc = $onValidFunc;

        $this->_validators = $validators;
    }

}