<?php


namespace AppBundle\Services\ParamConverter;

use Doctrine\Common\Persistence\ManagerRegistry;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;

class QueryConverter implements ParamConverterInterface
{
    const CONVERTER_NAME = 'query';
    const MAPPING_OPTION_KEY = 'mapping';

    /**
     * @var ManagerRegistry
     */
    private $doctrine;

    /**
     * Constructor.
     *
     * QueryConverter constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->doctrine = $registry;
    }

    /**
     * Apply parameter conversion.
     *
     * @param Request $request
     * @param ParamConverter $configuration
     * @return bool
     */
    public function apply(Request $request, ParamConverter $configuration)
    {
        $mapping = $this->mapping($request, $this->getMappingOption($configuration->getOptions()), $configuration->isOptional());

        $entity = $this->doctrine->getManagerForClass($configuration->getClass())->getRepository($configuration->getClass())->findOneBy($mapping);

        if (($configuration->isOptional() || $entity !== null) && $mapping) {
            $request->attributes->set($configuration->getName(), $entity);
            return true;
        }

        return false;
    }

    /**
     * Does query converter handle this conversion.
     *
     * @param ParamConverter $configuration
     * @return bool
     */
    public function supports(ParamConverter $configuration)
    {
        return $configuration->getConverter() === self::CONVERTER_NAME;
    }

    /**
     * Get mapping information from request.
     *
     * @param Request $request
     * @param $mappingOption
     * @param $optional
     * @return array|bool
     */
    protected function mapping(Request $request, $mappingOption, $optional)
    {
        $mapping = [];
        foreach ($mappingOption as $parameter => $attribute) {
            if (!$optional && !$request->query->has($parameter)) {
                return false;
            }

            if ($request->query->has($parameter)) {
                $mapping[$attribute] = $request->query->get($parameter);
            }
        }

        return $mapping;
    }

    /**
     * Get mapping option.
     *
     * @param $options
     * @return mixed
     */
    protected function getMappingOption($options)
    {
        return $options[self::MAPPING_OPTION_KEY];
    }
}