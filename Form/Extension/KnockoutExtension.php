<?php

namespace Padam87\KnockoutJSBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use JMS\DiExtraBundle\Annotation as DI;

/**
 * @DI\Service("form.type_extension.knockout")
 * @DI\Tag("form.type_extension", attributes = { "alias" = "form" })
 */
class KnockoutExtension extends AbstractTypeExtension
{
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $knockout = array(
            'name' => $form->getName(),
            'fields' => $this->buildViewModelFields($form->getChildren()),
            'bindings' => $this->buildViewModelBindings($form->getChildren()),
            'collections' => $this->getCollections($form->getChildren(), $view),
        );

        $view->set('knockout', $knockout);
    }

    protected function buildViewModelFields(array $children)
    {
        $fields = array();

        foreach ($children as $field) {
            if($field->getName() === '_token') continue;

            if ($field->hasChildren()) {
                if ($field->getConfig()->getType()->getInnerType()->getName() == 'knockout') {
                    $fields[$field->getName()] = $this->buildViewModelFields($field->getChildren());

                    $i = 1;

                    foreach ($fields[$field->getName()] as $k => $collectionItem) {
                        $collectionItem['id'] = $i;
                        $i++;

                        $fields[$field->getName()][$k] = $collectionItem;
                    }

                    $i++;
                } else {
                    $fields[$field->getName()] = $this->buildViewModelFields($field->getChildren());
                }
            } else {
                $data = $field->getData();

                if (is_object($data)) {
                     $data = $data->getId();
                }

                $fields[$field->getName()] = $data === NULL ? "" : $data;
            }
        }

        return $fields;
    }

    protected function buildViewModelBindings(array $children, $pre = "")
    {
        $bindings = array();

        foreach ($children as $field) {
            if($field->getName() === '_token') continue;

            if ($field->getConfig()->getType()->getInnerType()->getName() == 'knockout') {
                 // TODO
            } else {
                if ($field->hasChildren()) {
                    $newPre = empty($pre) ? $field->getName() : $pre . '.' . $field->getName();

                    foreach ($this->buildViewModelBindings($field->getChildren(), $newPre) as $k => $binding) {
                        $bindings[$k] = $binding;
                    }
                } else {
                    if (empty($pre)) {
                        $bindings[$field->getName()] = "value: " . $field->getName();
                    } else {
                        $bindings[str_replace(".", "_", $pre) . '_' . $field->getName()] = "value: " . $pre . '.' . $field->getName();
                    }
                }
            }
        }

        return $bindings;
    }

    protected function getCollections(array $children, $parentView)
    {
        $collections = array();

        foreach ($children as $field) {
            if ($field->getConfig()->getType()->getInnerType()->getName() == 'knockout') {
                $prototypeData = array();

                foreach (array_keys($field->createView($parentView)->get("prototype")->getChildren()) as $name) {
                    $prototypeData[$name] = "";
                }

                $collections[$field->getName()] = $prototypeData;
            }
        }

        return $collections;
    }

    public function getDefaultOptions()
    {
        return array(
            'knockout' => false,
        );
    }

    public function getExtendedType()
    {
        return 'form';
    }
}
