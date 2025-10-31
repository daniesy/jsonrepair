<?php

declare(strict_types=1);

namespace JsonRepair\Streaming;

/**
 * Caret position within parsing context
 */
enum Caret: string
{
    case BEFORE_VALUE = 'beforeValue';
    case AFTER_VALUE = 'afterValue';
    case BEFORE_KEY = 'beforeKey';
}

/**
 * Type of parsing context
 */
enum StackType: string
{
    case ROOT = 'root';
    case OBJECT = 'object';
    case ARRAY = 'array';
    case ND_JSON = 'ndJson';
    case FUNCTION_CALL = 'dataType';
}

/**
 * Stack for managing streaming parse state
 */
class Stack
{
    /** @var array<StackType> */
    private array $stack = [];
    private Caret $caret;

    public function __construct()
    {
        $this->stack[] = StackType::ROOT;
        $this->caret = Caret::BEFORE_VALUE;
    }

    /**
     * Get current stack type
     */
    public function type(): StackType
    {
        return end($this->stack) ?: StackType::ROOT;
    }

    /**
     * Get current caret position
     */
    public function caret(): Caret
    {
        return $this->caret;
    }

    /**
     * Pop from stack
     */
    public function pop(): bool
    {
        array_pop($this->stack);
        $this->caret = Caret::AFTER_VALUE;
        return true;
    }

    /**
     * Push to stack
     */
    public function push(StackType $type, Caret $newCaret): bool
    {
        $this->stack[] = $type;
        $this->caret = $newCaret;
        return true;
    }

    /**
     * Update caret position
     */
    public function update(Caret $newCaret): bool
    {
        $this->caret = $newCaret;
        return true;
    }
}
