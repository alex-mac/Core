<?php

namespace Athens\Core\Section;

use Athens\Core\Etc\AbstractBuilder;
use Athens\Core\Etc\SafeString;
use Athens\Core\Field\Field;
use Athens\Core\Field\FieldBuilder;
use Athens\Core\WritableBearer\WritableBearerBearerBuilderTrait;
use Athens\Core\Writer\WritableInterface;

/**
 * Class SectionBuilder
 *
 * @package Athens\Core\Section
 */
class SectionBuilder extends AbstractBuilder implements SectionConstantsInterface
{
    const PAGE_TYPE_AJAX_ACTION = 'ajax-action';
    const PAGE_TYPE_AJAX_PAGE = 'ajax-page';
    
    /** @var string */
    protected $type = "base";
    
    use WritableBearerBearerBuilderTrait;

    /**
     * @param string $type
     * @return SectionBuilder
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return SectionInterface
     */
    public function build()
    {
        $this->validateId();

        $writableBearer = $this->buildWritableBearer();

        return new Section($this->id, $this->classes, $this->data, $writableBearer, $this->type);
    }
}
