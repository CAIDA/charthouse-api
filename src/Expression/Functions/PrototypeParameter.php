<?php

namespace App\Expression\Functions;

use Swagger\Annotations as SWG;
use Symfony\Component\Serializer\Annotation\Groups;

class PrototypeParameter
{
    public static $TYPE_TIME_SERIES = 'timeSeries';
    public static $TYPE_STRING = 'string';
    public static $TYPE_NUMBER = 'number';

    /**
     * @Groups({"public"})
     * @SWG\Property(
     *     type="string",
     *     example="Series"
     * )
     */
    private $name;

    /**
     * @Groups({"public"})
     * @SWG\Property(
     *     type="string",
     *     example="List of Series (at least 2)"
     * )
     */
    private $description;

    /**
     * @Groups({"public"})
     * @SWG\Property(
     *     type="string",
     *     enum={"timeSeries", "string", "number"},
     *     example="timeSeries"
     * )
     */
    private $type;

    /**
     * @Groups({"public"})
     */
    private $mandatory;

    /**
     * @Groups({"public"})
     */
    private $multiple;

    public function __construct($name, $description, $type, $mandatory, $multiple)
    {
        $this->setName($name);
        $this->setDescription($description);
        $this->setType($type);
        $this->setMandatory($mandatory);
        $this->setMultiple($multiple);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function isMandatory(): bool
    {
        return $this->mandatory;
    }

    public function setMandatory(bool $mandatory): void
    {
        $this->mandatory = $mandatory;
    }

    public function isMultiple(): bool
    {
        return $this->multiple;
    }

    public function setMultiple(bool $multiple): void
    {
        $this->multiple = $multiple;
    }

}
