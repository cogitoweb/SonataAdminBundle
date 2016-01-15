<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\AdminBundle\Admin;

use Doctrine\Common\Inflector\Inflector;
use Doctrine\Common\Util\ClassUtils;
use Sonata\AdminBundle\Exception\NoValueException;
use Sonata\AdminBundle\Util\FormBuilderIterator;
use Sonata\AdminBundle\Util\FormViewIterator;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class AdminHelper.
 *
 * @author  Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
class AdminHelper
{
    /**
     * @var Pool
     */
    protected $pool;

    /**
     * @param Pool $pool
     */
    public function __construct(Pool $pool)
    {
        $this->pool = $pool;
    }

    /**
     * @throws \RuntimeException
     *
     * @param FormBuilderInterface $formBuilder
     * @param string               $elementId
     *
     * @return FormBuilderInterface
     */
    public function getChildFormBuilder(FormBuilderInterface $formBuilder, $elementId)
    {
        foreach (new FormBuilderIterator($formBuilder) as $name => $formBuilder) {
            if ($name == $elementId) {
                return $formBuilder;
            }
        }

        return;
    }

    /**
     * @param FormView $formView
     * @param string   $elementId
     *
     * @return null|FormView
     */
    public function getChildFormView(FormView $formView, $elementId)
    {
        foreach (new \RecursiveIteratorIterator(new FormViewIterator($formView), \RecursiveIteratorIterator::SELF_FIRST) as $name => $formView) {
            if ($name === $elementId) {
                return $formView;
            }
        }

        return;
    }

    /**
     * @deprecated
     *
     * @param string $code
     *
     * @return AdminInterface
     */
    public function getAdmin($code)
    {
        return $this->pool->getInstance($code);
    }

    /**
     * Note:
     *   This code is ugly, but there is no better way of doing it.
     *   For now the append form element action used to add a new row works
     *   only for direct FieldDescription (not nested one).
     *
     * @throws \RuntimeException
     *
     * @param AdminInterface $admin
     * @param object         $subject
     * @param string         $elementId
     *
     * @return array
     */
    public function appendFormFieldElement(AdminInterface $admin, $subject, $elementId)
    {
        // retrieve the subject
        $formBuilder = $admin->getFormBuilder();

        $form = $formBuilder->getForm();
        $form->setData($subject);
        $form->handleRequest($admin->getRequest());

        $elementId = preg_replace('#.(\d+)#', '[$1]', implode('.', explode('_', substr($elementId, strpos($elementId, '_') + 1))));
		
        // append a new instance into the object
        $this->addNewInstance($admin, $elementId);
		
		// return new form with empty row
        $finalForm = $admin->getFormBuilder()->getForm();
        $finalForm->setData($subject);
        $finalForm->setData($form->getData());

        return $finalForm;
    }

    /**
     * Add a new instance to the related FieldDescriptionInterface value.
     *
     * @param object                    $object
     * @param FieldDescriptionInterface $fieldDescription
     *
     * @throws \RuntimeException
     */
    public function addNewInstance($object, FieldDescriptionInterface $fieldDescription)
    {
        $instance = $fieldDescription->getAssociationAdmin()->getNewInstance();
        $mapping  = $fieldDescription->getAssociationMapping();

        $method = sprintf('add%s', $this->camelize($mapping['fieldName']));

        if (!method_exists($object, $method)) {
            $method = rtrim($method, 's');

            if (!method_exists($object, $method)) {
                $method = sprintf('add%s', $this->camelize(Inflector::singularize($mapping['fieldName'])));

                if (!method_exists($object, $method)) {
                    throw new \RuntimeException(sprintf('Please add a method %s in the %s class!', $method, ClassUtils::getClass($object)));
                }
            }
        }

        $object->$method($instance);
    }

    /**
     * Camelize a string.
     *
     * @static
     *
     * @param string $property
     *
     * @return string
     */
    public function camelize($property)
    {
        return BaseFieldDescription::camelize($property);
    }

    protected function entityClassNameFinder(AdminInterface $admin, $elements)
    {
        $element = array_shift($elements);
        $associationAdmin = $admin->getFormFieldDescription($element)->getAssociationAdmin();
        if (count($elements) == 0) {
            return $associationAdmin->getClass();
        } else {
            return $this->entityClassNameFinder($associationAdmin, $elements);
        }
    }
}
