<?php

namespace Athens\Core\Test;

use PHPUnit_Framework_TestCase;

use PHPExcel_Reader_Excel2007;

use Athens\Core\Choice\ChoiceBuilder;
use Athens\Core\Writer\ExcelWriter;
use Athens\Core\Field\Field;
use Athens\Core\Writer\HTMLWriter;
use Athens\Core\FormAction\FormAction;
use Athens\Core\FormAction\FormActionBuilder;
use Athens\Core\Form\FormBuilder;
use Athens\Core\Section\SectionBuilder;
use Athens\Core\Page\PageBuilder;
use Athens\Core\Etc\StringUtils;
use Athens\Core\Settings\Settings;
use Athens\Core\Etc\SafeString;
use Athens\Core\Row\RowBuilder;
use Athens\Core\Table\TableBuilder;
use Athens\Core\Field\FieldBuilder;
use Athens\Core\Filter\Filter;
use Athens\Core\Filter\FilterBuilder;
use Athens\Core\FilterStatement\FilterStatement;
use Athens\Core\Link\LinkBuilder;
use Athens\Core\Script\ScriptBuilder;

use Athens\Core\Test\Mock\MockHTMLWriter;

class WriterTest extends PHPUnit_Framework_TestCase
{
    /**
     * Strip quotes marks from a string
     * @param string $string
     * @return string
     */
    protected function stripQuotes($string)
    {
        return str_replace(['"', "'"], "", $string);
    }
    public function testVisitField()
    {
        $writer = new HTMLWriter();

        /* A literal field */
        $field = new Field(["field-class"], [], "literal", "A literal field", "initial", true, [], 200);

        // Get result and strip quotes, for easier analysis
        $result = $this->stripQuotes($writer->visitField($field));

        // Assert that the field contains the given elements
        $this->assertContains("initial", $writer->visitField($field));
        $this->assertContains("field-class", $writer->visitField($field));

        /* A section-label field */
        $field = new Field([], [], "section-label", "A section-label field", "initial");
        $this->assertContains("A section-label field", $writer->visitField($field));
        $this->assertNotContains("initial", $writer->visitField($field));

        /* An html field */
        $field = new Field([], [], FieldBuilder::TYPE_HTML, "An HTML Field", "initial");
        $this->assertContains('textarea class=html', $this->stripQuotes($writer->visitField($field)));

        /* A choice field */
        $aliases = ["alias1", "alias2"];
        $values = ["value1", "value2"];
        $choices = [
            ChoiceBuilder::begin()->setAlias($aliases[0])->setValue($values[0])->build(),
            ChoiceBuilder::begin()->setAlias($aliases[1])->setValue($values[1])->build(),
        ];

        $field = new Field(
            [],
            [],
            "choice",
            "A literal field",
            $values[0],
            true,
            $choices,
            200
        );
        
        $keys = array_keys($field->getChoices());

        // Get result and strip quotes, for easier analysis
        $result = $this->stripQuotes($writer->visitField($field));

        // Assert that the field contains our choices
        $this->assertContains($values[0], $result);
        $this->assertContains($values[1], $result);
        $this->assertContains("value=" . $keys[0], $result);
        $this->assertContains("value=" . $keys[1], $result);

        // Assert that the "initial" choice is selected
        $this->assertContains("value={$keys[0]} checked", $result);

        /* A multiple choice field */
        $field = new Field(
            [],
            [],
            "multiple-choice",
            "A multiple-choice field",
            [$values[1]],
            true,
            $choices,
            200
        );

        // Get result and strip quotes, for easier analysis
            $result = $this->stripQuotes($writer->visitField($field));

        // Assert that the field contains our choices
            $this->assertContains($values[0], $result);
            $this->assertContains($values[1], $result);
            $this->assertContains("value={$keys[0]}", $result);
            $this->assertContains("value={$keys[1]}", $result);

        // Assert that the "initial" choice is selected
            $this->assertContains("value={$keys[1]}  selected", $result);

        /* A text field */
            $field = new Field(
                [],
                [],
                "text",
                "A text field",
                "5",
                true,
                [],
                200,
                "helptext",
                "placeholder for a text field"
            );

        // Get result and strip quotes, for easier analysis
            $result = $this->stripQuotes($writer->visitField($field));

            $this->assertContains('value=5', $result);
            $this->assertContains('<input type=text', $result);
            $this->assertContains('placeholder for a text field', $result);

        /* A textarea field */
            $field = new Field(
                [],
                [],
                "textarea",
                "A textarea field",
                "initial value",
                true,
                [],
                1000,
                "helptext",
                "placeholder for a textarea field"
            );

        // Get result and strip quotes, for easier analysis
            $result = $this->stripQuotes($writer->visitField($field));

        // By our current method of calculation, should have size of 100 means 10 rows
        // If change calculation, change this test
            $this->assertContains('rows=10', $result);
            $this->assertContains('<textarea', $result);
            $this->assertContains('initial value', $result);
            $this->assertContains('placeholder for a textarea field', $result);

        /* A textarea field without an initial value*/
            $field = new Field([], [], "textarea", "A textarea field", "", true, [], 1000);

        // Get result and strip quotes, for easier analysis
            $result = $this->stripQuotes($writer->visitField($field));

        // Assert that the text area contains no initial text
            $this->assertContains('></textarea>', $result);
    }

    public function testRenderBooleanField()
    {
        $writer = new HTMLWriter();

        /* A required boolean field*/
        $initialFalseField = new Field([], [], "boolean", "A boolean field", "", true, []);

        // Get result and strip quotes, for easier analysis
        $result = $this->stripQuotes($writer->visitField($initialFalseField));

        // Assert that the boolean field is rendered as two radio choices
        $this->assertEquals(2, substr_count($result, "<input type=radio"));

        /* An unrequired boolean field*/
        $initialFalseField = new Field([], [], "boolean", "A boolean field", "", false, []);

        // Get result and strip quotes, for easier analysis
        $result = $this->stripQuotes($writer->visitField($initialFalseField));

        // Assert that the boolean field is rendered as two radio choices
        $this->assertContains('<input type=checkbox', $result);

        /* An unrequired boolean field with initial value */
        $initialFalseField = new Field([], [], "boolean", "A boolean field", false, false, []);
        $initialTrueField = new Field([], [], "boolean", "A boolean field", true, false, []);

        // Get result and strip quotes, for easier analysis
        $resultInitialFalse = $this->stripQuotes($writer->visitField($initialFalseField));
        $resultInitialTrue = $this->stripQuotes($writer->visitField($initialTrueField));

        // Assert that the boolean field is rendered as two radio choices
        $this->assertNotContains('checked>', $resultInitialFalse);
        $this->assertContains('checked>', $resultInitialTrue);

        /* A required boolean field with initial value */
        $initialFalseField = new Field([], [], "boolean", "A boolean field", false, true, []);
        $initialZeroField = new Field([], [], "boolean", "A boolean field", 0, true, []);
        $initialZeroStringField = new Field([], [], "boolean", "A boolean field", "0", true, []);

        $initialTrueField = new Field([], [], "boolean", "A boolean field", true, true, []);
        $initialOneField = new Field([], [], "boolean", "A boolean field", 1, true, []);
        $initialOneStringField = new Field([], [], "boolean", "A boolean field", "1", true, []);

        // Get result and strip quotes, for easier analysis
        $resultInitialFalse = $this->stripQuotes($writer->visitField($initialFalseField));
        $resultInitialZero = $this->stripQuotes($writer->visitField($initialZeroField));
        $resultInitialZeroString = $this->stripQuotes($writer->visitField($initialZeroStringField));

        $resultInitialTrue = $this->stripQuotes($writer->visitField($initialTrueField));
        $resultInitialOne = $this->stripQuotes($writer->visitField($initialOneField));
        $resultInitialOneString = $this->stripQuotes($writer->visitField($initialOneStringField));

        // Assert that the correct values have been checked.
        $this->assertContains('value=0 checked>', $resultInitialFalse);
        $this->assertNotContains('value=1 checked>', $resultInitialFalse);

        $this->assertContains('value=0 checked>', $resultInitialZero);
        $this->assertNotContains('value=1 checked>', $resultInitialZeroString);

        $this->assertContains('value=0 checked>', $resultInitialFalse);
        $this->assertNotContains('value=1 checked>', $resultInitialFalse);

        $this->assertContains('value=1 checked>', $resultInitialTrue);
        $this->assertNotContains('value=0 checked>', $resultInitialTrue);

        $this->assertContains('value=1 checked>', $resultInitialOne);
        $this->assertNotContains('value=0 checked>', $resultInitialOne);

        $this->assertContains('value=1 checked>', $resultInitialOneString);
        $this->assertNotContains('value=0 checked>', $resultInitialOneString);
    }

    public function testRenderFieldErrors()
    {
        $writer = new HTMLWriter();

        /* Field not required, no data provided: no field errors */
        $field = new Field([], [], "text", "An unrequired field", "5", false, [], 200);

        $field->validate();

        // Confirm that the field has no errors
        $this->assertEmpty($field->getErrors());

        // Get result and strip quotes, for easier analysis
        $result = $this->stripQuotes($writer->visitField($field));

        // Assert that the result does not display any errors
        $this->assertNotContains("field-errors", $result);

        /* Field required, but no data provided: field errors */
        $field = new Field([], [], "text", "A required field", "5", true, [], 200);

        $field->validate();

        // Confirm that the field has errors
        $this->assertNotEmpty($field->getErrors());

        // Get result and strip quotes, for easier analysis
        $result = $this->stripQuotes($writer->visitField($field));

        // Assert that the result does display errors
        $this->assertContains("field-errors", $result);
    }

    public function testVisitForm()
    {
        $writer = new HTMLWriter();

        $actions = [
            new FormAction([], [], FormAction::TYPE_JAVASCRIPT, "JS", "console.log('here');"),
            new FormAction([], [], FormAction::TYPE_SUBMIT, "POST", "post-target")
        ];
        $onValidFunc = function () {
            return "valid";
        };
        $onInvalidFunc = function () {
            return "invalid";
        };

        $id = "f" . (string)rand();
        $classes = [(string)rand(), (string)rand()];
        $data = [
            'd' . (string)rand() => (string)rand(),
            'd' . (string)rand() => (string)rand(),
        ];
        $method = "m" . (string)rand();
        $target = "t" . (string)rand();
        $helptext = "h" . (string)rand();

        $form = FormBuilder::begin()
            ->setId($id)
            ->addClass($classes[0])
            ->addClass($classes[1])
            ->addData(array_keys($data)[0], array_values($data)[0])
            ->addData(array_keys($data)[1], array_values($data)[1])
            ->setMethod($method)
            ->setTarget($target)
            ->addAction($actions[0])
            ->addAction($actions[1])
            ->addWritable(
                new Field([], [], 'literal', 'A literal field', 'Literal field content', true, []),
                "literalField"
            )
            ->addWritable(
                new Field([], [], 'text', 'A text field', "5", false, []),
                "textField"
            )
            ->setFieldHelptext("textField", $helptext)
            ->addOnInvalidFunc($onInvalidFunc)
            ->addOnValidFunc($onValidFunc)
            ->build();

        $requestURI = (string)rand();
        $_SERVER["REQUEST_URI"] = $requestURI;

        // Get result and strip quotes, for easier analysis
        $result = $this->stripQuotes($writer->visitForm($form));

        $this->assertContains("<form", $result);
        $this->assertContains("id=$id", $result);
        $this->assertContains("class=" . implode(" ", $classes), $result);
        $this->assertContains("method=$method", $result);
        $this->assertContains("target=$target", $result);
        $this->assertContains("data-request-uri=$requestURI", $result);
        $this->assertContains("data-field-for=a-literal-field", $result);
        $this->assertContains("A literal field", $result);
        $this->assertContains("<label for=a-literal-field class=field-label required >", $result);
        $this->assertContains("Literal field content", $result);
        $this->assertContains("data-field-for=a-text-field", $result);
        $this->assertContains("A text field", $result);
        $this->assertContains("span class=field-helptext>$helptext", $result);
        $this->assertContains("value=5", $result);
        $this->assertContains("name=a-text-field", $result);
        $this->assertContains('<input type=text', $result);
        $this->assertContains('onclick=console.log(here);', $result);
        $this->assertContains('JS</button>', $result);
        $this->assertContains('<input class=form-action', $result);
        $this->assertContains('type=submit name=post', $result);
        $this->assertContains('value=POST', $result);
        $this->assertContains("data-" . array_keys($data)[0] . "=" . array_values($data)[0], $result);
        $this->assertContains("data-" . array_keys($data)[1] . "=" . array_values($data)[1], $result);
        $this->assertContains('</form>', $result);
    }

    public function testVisitFormAction()
    {
        $writer = new HTMLWriter();

        $label = 'l' . (string)rand();
        $target = 't' . (string)rand();

        $jsFormAction = FormActionBuilder::begin()
        ->setType(FormAction::TYPE_JAVASCRIPT)
        ->setLabel($label)
        ->setTarget($target)
        ->build();

        // Get result and strip quotes, for easier analysis
        $result = $this->stripQuotes($writer->visitFormAction($jsFormAction));

        $this->assertContains("onclick=$target", $result);
        $this->assertContains(">$label</button>", $result);

        $submitAction = FormActionBuilder::begin()
            ->setType(FormAction::TYPE_SUBMIT)
            ->setLabel($label)
            ->build();

        // Get result and strip quotes, for easier analysis
        $result = $this->stripQuotes($writer->visitFormAction($submitAction));

        $this->assertContains("<input", $result);
        $this->assertContains("value=$label", $result);

        $ajaxSubmitAction = FormActionBuilder::begin()
            ->setType(FormAction::TYPE_AJAX_SUBMIT)
            ->setLabel($label)
            ->build();

        // Get result and strip quotes, for easier analysis
        $result = $this->stripQuotes($writer->visitFormAction($ajaxSubmitAction));

        $this->assertContains("onclick=athens.ajax.submitForm($(this).closest(form)[0]);", $result);
        $this->assertContains(">$label</button>", $result);
    }
    
    /**
     * @expectedException              Twig_Error_Loader
     * @expectedExceptionMessageRegExp #Unable to find template "form/nonexistant-type.twig".*#
     */
    public function testVisitNonBaseForm()
    {
        $writer = new HTMLWriter();

        $form = FormBuilder::begin()
            ->setId("f-" . (string)rand())
            ->setType("nonexistant-type")
            ->addWritable(
                new Field([], [], 'literal', 'A literal field', 'Literal field content', true, []),
                "literalField"
            )
            ->addWritable(
                new Field([], [], 'text', 'A text field', "5", false, []),
                "textField"
            )
            ->build();

        $writer->visitForm($form);
    }

    public function testRenderFormErrors()
    {
        $writer = new HTMLWriter();

        $_SERVER["REQUEST_URI"] = "";

        /* Field not required, no data provided: no field errors */
        $field = new Field([], [], "text", "An unrequired field", "5", false, [], 200);
        $form = FormBuilder::begin()
            ->setId("f-" . (string)rand())
            ->addWritable($field)
            ->build();

        // Confirm that the form is valid and has no errors
        $this->assertTrue($form->isValid());
        $this->assertEmpty($form->getErrors());

        // Get result and strip quotes, for easier analysis
        $result = $this->stripQuotes($writer->visitForm($form));

        // Assert that the result does not display any errors
        $this->assertNotContains("form-errors", $result);

        /* Field required, but no data provided: field errors */
        $field = new Field([], [], "text", "A required field", "5", true, [], 200);
        $form = FormBuilder::begin()
            ->setId("f-" . (string)rand())
            ->addWritable($field)
            ->build();

        // Confirm that the form is not valid and does have errors
        $this->assertFalse($form->isValid());
        $this->assertNotEmpty($form->getErrors());

        // Get result and strip quotes, for easier analysis
        $result = $this->stripQuotes($writer->visitForm($form));

        // Assert that the result does display errors
        $this->assertContains("form-errors", $result);

        // Assert that form has been given the class has-errors
        $this->assertContains("class= prevent-double-submit has-errors", $result);
    }

    public function testVisitSection()
    {
        $writer = new HTMLWriter();

        $id = "s" . (string)rand();
        $classes = [(string)rand(), (string)rand()];
        $data = [
            'd' . (string)rand() => (string)rand(),
            'd' . (string)rand() => (string)rand(),
        ];
        $requestURI = (string)rand();

        $subSection = SectionBuilder::begin()
            ->setId("s" . (string)rand())
            ->addContent("Some sub-content.")
            ->build();

        $section = SectionBuilder::begin()
            ->setId($id)
            ->addClass($classes[0])
            ->addClass($classes[1])
            ->addData(array_keys($data)[0], array_values($data)[0])
            ->addData(array_keys($data)[1], array_values($data)[1])
            ->addLabel("Label")
            ->addContent("Some content.")
            ->addWritable($subSection)
            ->build();

        $_SERVER["REQUEST_URI"] = $requestURI;

        // Get result and strip quotes, for easier analysis
        $result = $this->stripQuotes($writer->visitSection($section));

        $this->assertContains("<div id=$id ", $result);
        $this->assertContains("class=section-container " . implode(' ', $classes), $result);
        $this->assertContains("data-" . array_keys($data)[0] . "=" . array_values($data)[0], $result);
        $this->assertContains("data-" . array_keys($data)[1] . "=" . array_values($data)[1], $result);
        $this->assertContains("data-request-uri=$requestURI", $result);
        $this->assertContains("<div class=section-label  >Label</div>", $result);
        $this->assertContains("<div class=section-writables>", $result);
        $this->assertContains("Some sub-content.", $result);
    }

    public function testVisitRow()
    {
        $writer = new HTMLWriter();

        $initialText = (string)rand();
        $initialLiteral = SafeString::fromString('<a href="http://example.com">A link</a>');
        $initialHidden = (string)rand();
        $onClick = "console.log('Click!');";

        $textField = FieldBuilder::begin()
            ->setType("text")
            ->setLabel("Text Field")
            ->setInitial($initialText)
            ->build();

        $literalField = FieldBuilder::begin()
            ->setType("literal")
            ->setLabel("Literal Field")
            ->setInitial($initialLiteral)
            ->build();

        $hiddenField = FieldBuilder::begin()
            ->setType("text")
            ->setLabel("Hidden Field")
            ->setInitial($initialHidden)
            ->build();

        $highlightableRow = RowBuilder::begin()
            ->addWritable($textField, "TextField")
            ->addWritable($literalField, "LiteralField")
            ->addWritable($hiddenField, "HiddenField")
            ->setHighlightable(true)
            ->build();

        // Get result and strip quotes, for easier analysis
        $result = $this->stripQuotes($writer->visitRow($highlightableRow));

        $this->assertContains("<tr", $result);
        $this->assertContains("</tr>", $result);
        $this->assertContains("<td class=TextField", $result);
        $this->assertContains("<td class=LiteralField", $result);
        $this->assertContains("<td class=HiddenField", $result);
        $this->assertContains("highlightable", $result);
        $this->assertContains("class= clickable", $result);
        $this->assertContains($this->stripQuotes($initialLiteral), $result);

        $clickableRow = RowBuilder::begin()
            ->addWritable($textField, "TextField")
            ->addWritable($literalField, "LiteralField")
            ->addWritable($hiddenField, "HiddenField")
//            ->setVisibleFieldNames(["TextField", "LiteralField"])
//            ->setHiddenFieldNames(["HiddenField"])
            ->setOnClick($onClick)
            ->build();

        // Get result and strip quotes, for easier analysis
        $result = $this->stripQuotes($writer->visitRow($clickableRow));
        $this->assertContains("class= clickable", $result);
    }

    public function testVisitTable()
    {
        $writer = new HTMLWriter();

        $id = "t" . (string)rand();
        $classes = [(string)rand(), (string)rand()];
        $data = [
            'd' . (string)rand() => (string)rand(),
            'd' . (string)rand() => (string)rand(),
        ];
        $requestURI = (string)rand();

        $field1 = new Field([], [], "text", "Text Field Label", (string)rand());
        $field1Name = "TextField1";
        $row1 = RowBuilder::begin()
            ->addWritable($field1, $field1Name)
            ->setColumnLabel($field1Name, $field1->getLabel())
            ->build();

        $field2 = new Field([], [], "text", "Text Field Label", (string)rand());
        $field2Name = "TextField2";
        $row2 = RowBuilder::begin()
            ->addWritable($field2, $field2Name)
            ->build();

        $table = TableBuilder::begin()
            ->setId($id)
            ->addClass($classes[0])
            ->addClass($classes[1])
            ->addData(array_keys($data)[0], array_values($data)[0])
            ->addData(array_keys($data)[1], array_values($data)[1])
            ->addRow($row1)
            ->addRow($row2)
            ->build();

        $_SERVER["REQUEST_URI"] = $requestURI;

        // Get result and strip quotes, for easier analysis
        $result = $this->stripQuotes($writer->visitTable($table));

        $row1Written = $this->stripQuotes($writer->visitRow($row1));
        $row2Written = $this->stripQuotes($writer->visitRow($row2));

        $this->assertContains("id=$id class=table-container", $result);
        $this->assertContains("class=table-container " . implode(" ", $classes), $result);
        $this->assertContains("data-" . array_keys($data)[0] . "=" . array_values($data)[0], $result);
        $this->assertContains("data-" . array_keys($data)[1] . "=" . array_values($data)[1], $result);
        $this->assertContains("data-request-uri=$requestURI", $result);
        $this->assertContains("</table>", $result);

        $this->assertContains("<th data-header-for=$field1Name>{$field1->getLabel()}</th>", $result);

        $this->assertContains($row1Written, $result);
        $this->assertContains($row2Written, $result);
    }

    public function testVisitSortFilter()
    {
        $writer = new HTMLWriter();

        $handle = (string)rand();
        $type = Filter::TYPE_SORT;

        $filter = FilterBuilder::begin()
            ->setType($type)
            ->setId($handle)
            ->build();

        $result = $this->stripQuotes($filter->accept($writer));

        $this->assertContains("class=sort-container data-handle-for=$handle", $result);
    }

    public function testVisitSelectFilter()
    {
        $writer = new HTMLWriter();

        $handle = (string)rand();
        $optionNames = ["s".(string)rand(), "s".(string)rand(), "s".(string)rand()];
        $optionFieldNames = [(string)rand(), (string)rand(), (string)rand()];
        $optionConditions = [
            FilterStatement::COND_GREATER_THAN,
            FilterStatement::COND_CONTAINS,
            FilterStatement::COND_LESS_THAN
        ];
        $optionValues = [rand(), rand(), rand()];

        $defaultOption = 1;

        $filter = FilterBuilder::begin()
            ->setType(Filter::TYPE_SELECT)
            ->setId($handle)
            ->addOptions([
                $optionNames[0] => [$optionFieldNames[0], $optionConditions[0], $optionValues[0]],
                $optionNames[1] => [$optionFieldNames[1], $optionConditions[1], $optionValues[1]],
            ])
            ->addOptions([
                $optionNames[2] => [$optionFieldNames[2], $optionConditions[2], $optionValues[2]],
            ])
            ->setDefault($optionNames[$defaultOption])
            ->build();

        $result = $this->stripQuotes($filter->accept($writer));

        // Assert that the result contains the container for our filter controls
        $this->assertContains("class=select-container data-handle-for=$handle", $result);

        // Assert that each of the option names is presented
        foreach ($optionNames as $name) {
            $this->assertContains($name, $result);
        }
    }

    public function testVisitLink()
    {
        $writer = new HTMLWriter();

        $uri = 'u' . (string)rand();
        $text = 't' . (string)rand();

        $classes = ["class1", "class2"];
        $data = [
            'd' . (string)rand() => (string)rand(),
            'd' . (string)rand() => (string)rand(),
        ];

        $link = LinkBuilder::begin()
            ->addClass($classes[0])
            ->addClass($classes[1])
            ->addData(array_keys($data)[0], array_values($data)[0])
            ->addData(array_keys($data)[1], array_values($data)[1])
            ->setURI($uri)
            ->setText($text)
            ->build();

        // Get result and strip quotes, for easier analysis
        $result = $this->stripQuotes($writer->visitLink($link));

        $this->assertContains("<a", $result);
        $this->assertContains("href=$uri", $result);
        $this->assertContains(">$text</a>", $result);
        $this->assertContains("class=link " . implode(' ', $classes), $result);
        $this->assertContains("data-" . array_keys($data)[0] . "=" . array_values($data)[0], $result);
        $this->assertContains("data-" . array_keys($data)[1] . "=" . array_values($data)[1], $result);
    }

    public function testVisitScript()
    {
        $writer = new HTMLWriter();

        $contents = 'c' . (string)rand();

        $classes = ["class1", "class2"];
        $data = [
            'd' . (string)rand() => (string)rand(),
            'd' . (string)rand() => (string)rand(),
        ];

        $script = ScriptBuilder::begin()
            ->addClass($classes[0])
            ->addClass($classes[1])
            ->addData(array_keys($data)[0], array_values($data)[0])
            ->addData(array_keys($data)[1], array_values($data)[1])
            ->setContents($contents)
            ->build();

        // Get result and strip quotes, for easier analysis
        $result = $this->stripQuotes($writer->visitScript($script));

        $this->assertContains("<script", $result);
        $this->assertContains("$contents\n</script>", $result);
        $this->assertContains("class=script " . implode(' ', $classes), $result);
        $this->assertContains("data-" . array_keys($data)[0] . "=" . array_values($data)[0], $result);
        $this->assertContains("data-" . array_keys($data)[1] . "=" . array_values($data)[1], $result);
    }

    public function testVisitExcelPage()
    {
        $writer = new ExcelWriter();

        $id = "t" . (string)rand();

        $label = 'Text Field Label';
        $value1 = (string)rand();
        $value2 = (string)rand();

        $field1 = new Field([], [], "text", $label, $value1);
        $field1Name = "TextField1";
        $row1 = RowBuilder::begin()
            ->addWritable($field1, $field1Name)
            ->setColumnLabel($field1Name, $field1Name)
            ->build();

        $field2 = new Field([], [], "text", $label, $value2);
        $field2Name = "TextField2";
        $row2 = RowBuilder::begin()
            ->addWritable($field2, $field2Name)
            ->build();

        $table = TableBuilder::begin()
            ->setId($id)
            ->addRow($row1)
            ->addRow($row2)
            ->build();

        $page = PageBuilder::begin()
            ->setType(PageBuilder::TYPE_EXCEL)
            ->setId('test-excel-page')
            ->addWritable($table)
            ->build();

        // Get result and strip quotes, for easier analysis
        $tmp = tmpfile();

        $fileLocation = stream_get_meta_data($tmp)['uri'];

        fwrite($tmp, $writer->visitPage($page));

        $objReader = new PHPExcel_Reader_Excel2007();
        $objPHPExcel = $objReader->load($fileLocation);

        $worksheet = $objPHPExcel->getSheet(0);

        $this->assertEquals($label, $worksheet->getCell('A1')->getValue());
        $this->assertEquals($value1, $worksheet->getCell('A2')->getValue());
        $this->assertEquals($value2, $worksheet->getCell('A3')->getValue());
    }

    public function testVisitPage()
    {
        $writer = new HTMLWriter();

        $pageType = PageBuilder::TYPE_FULL_HEADER;
        $pageHeader = "Page Header";
        $pageSubHeader = "Page subheader";

        $id = "p" . (string)rand();
        $classes = [(string)rand(), (string)rand()];
        $data = [
            'd' . (string)rand() => (string)rand(),
            'd' . (string)rand() => (string)rand(),
        ];

        $breadCrumbs = ['t' . (string)rand() => 'u' . (string)rand()];

        $section = SectionBuilder::begin()
            ->setId("s" . (string)rand())
            ->addLabel("Label")
            ->addContent("Some content.")
            ->build();

        $page = PageBuilder::begin()
            ->setId($id)
            ->addClass($classes[0])
            ->addClass($classes[1])
            ->addWritable($section)
            ->addData(array_keys($data)[0], array_values($data)[0])
            ->addData(array_keys($data)[1], array_values($data)[1])
            ->setHeader($pageHeader)
            ->setSubHeader($pageSubHeader)
            ->setType($pageType)
            ->setTitle("Page Title")
            ->setBaseHref(".")
            ->addBreadCrumb(array_keys($breadCrumbs)[0], array_values($breadCrumbs)[0])
            ->build();

        // Add project CSS and JS
        $cssFile1 = "/path/to/file/1.css";
        $cssFile2= "/path/to/file/2.css";
        $jsFile1 = "/path/to/file/1.js";
        $jsFile2= "/path/to/file/2.js";

        Settings::getInstance()->addProjectCSS($cssFile1);
        Settings::getInstance()->addProjectCSS($cssFile2);

        Settings::getInstance()->addProjectJS($jsFile1);
        Settings::getInstance()->addProjectJS($jsFile2);

        // Provide a request URI, for the page's hash function
        $requestURI = (string)rand();
        $_SERVER["REQUEST_URI"] = $requestURI;

        // Get result and strip quotes, for easier analysis
        $result = $this->stripQuotes($writer->visitPage($page));

        $this->assertContains("<html>", $result);
        $this->assertContains("<head>", $result);
        $this->assertContains("<title>Page Title</title>", $result);
        $this->assertContains("<base href=.", $result);
        $this->assertContains("</head>", $result);
        $this->assertContains("<body id=$id", $result);
        $this->assertContains("class=" . implode(" ", $classes), $result);
        $this->assertContains("data-" . array_keys($data)[0] . "=" . array_values($data)[0], $result);
        $this->assertContains("data-" . array_keys($data)[1] . "=" . array_values($data)[1], $result);
        $this->assertContains(
            "href=" . array_values($breadCrumbs)[0] . ">" . array_keys($breadCrumbs)[0] . "</a>",
            $result
        );
        $this->assertRegExp("/h1.*class=header.*$pageHeader.*h1/s", $result);
        $this->assertRegExp("/h2.*class=subheader.*$pageSubHeader.*h2/s", $result);
        $this->assertContains("<div class=section-label  >Label</div>", $result);

        $this->assertContains($cssFile1, $result);
        $this->assertContains($cssFile2, $result);
        $this->assertContains($jsFile1, $result);
        $this->assertContains($jsFile2, $result);
    }

    public function testSaferawFilter()
    {
        $writer = new MockHTMLWriter();
        $env = $writer->getEnvironment();

        $template = "{{ var|saferaw|raw }}";

        $unsafeVar = '<a href="http://example.com">a link</a>';
        $safeVar = SafeString::fromString($unsafeVar);

        // Render the unsafe string
        $result = $env->createTemplate($template)->render(["var" => $unsafeVar]);
        $this->assertEquals(htmlentities($unsafeVar), $result);

        // Render the safe string
        $result = $env->createTemplate($template)->render(["var" => $safeVar]);
        $this->assertEquals((string)$safeVar, $result);
    }

    public function testWritedataFilter()
    {
        $writer = new MockHTMLWriter();
        $env = $writer->getEnvironment();

        $template = "{{ data|writedata|raw }}";

        $data = [
            'key1' => 'value1',
            'key2' => 'value2',
        ];

        // Render the data
        $result = $env->createTemplate($template)->render(['data' => $data]);

        $this->assertContains("data-key1=\"value1\"", $result);
        $this->assertContains("data-key2=\"value2\"", $result);
    }

    public function testSlugifyFilter()
    {
        $writer = new MockHTMLWriter();
        $env = $writer->getEnvironment();

        $template = "{{ var|slugify }}";

        $var = "^a#%5m4ll3r^^7357!@ 57r1n6";

        // Render the unsafe string
        $result = $env->createTemplate($template)->render(["var" => $var]);
        $this->assertEquals(StringUtils::slugify($var), $result);
    }

    public function testStripFormFilter()
    {
        $writer = new MockHTMLWriter();
        $env = $writer->getEnvironment();

        $template = "{{ var|stripForm|raw }}";

        $var = <<<HTML
<form id="formid">
    <div class="form-actions"><button>Press me!</button></div>
    <span class="form-errors">You made a mistake!</span>
</form>
HTML;
        $expected = <<<HTML
<div id="formid">
    <span class="form-errors hidden">You made a mistake!</span>
</div>
HTML;

        // Render the unsafe string
        $result = $env->createTemplate($template)->render(["var" => $var]);
        $this->assertEquals(preg_replace('/\s+/', '', $expected), preg_replace('/\s+/', '', $result));
    }

    public function testMD5Filter()
    {
        $writer = new MockHTMLWriter();
        $env = $writer->getEnvironment();

        $template = "{{ var|md5 }}";

        $var = "^a#%5m4ll3r^^7357!@ 57r1n6";

        // Render the unsafe string
        $result = $env->createTemplate($template)->render(["var" => $var]);
        $this->assertEquals(md5($var), $result);
    }

    public function testRequestURIGlobal()
    {
        $requestURI = (string)rand();
        $_SERVER["REQUEST_URI"] = $requestURI;

        $writer = new MockHTMLWriter();
        $env = $writer->getEnvironment();

        $template = "{{ requestURI }}";

        // Render the unsafe string
        $result = $env->createTemplate($template)->render([]);
        $this->assertEquals($requestURI, $result);
    }
}
