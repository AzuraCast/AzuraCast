<?php
namespace App\Normalizer\Annotation;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD"})
 */
class DeepNormalize
{
    /**
     * @var bool
     */
    private $deepNormalize;

    public function __construct(array $data)
    {
        $this->deepNormalize = (bool)$data['value'];
    }

    /**
     * @return bool
     */
    public function getDeepNormalize(): bool
    {
        return $this->deepNormalize;
    }
}
