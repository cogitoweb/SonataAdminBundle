<?php

namespace Sonata\AdminBundle\Admin;

/**
 * Description of CogitowebPool
 *
 * @author Daniele Artico <daniele.artico@cogitoweb.it>
 */
class CogitowebPool extends Pool
{
	/**
	 * {@inheritdoc}
	 */
	public function getAdminByClass($class)
	{
		if (!$this->hasAdminByClass($class)) {
			return;
		}

		if (!is_array($this->adminClasses[$class])) {
			throw new \RuntimeException('Invalid format for the Pool::adminClass property');
		}

		// Classes can be associated to several admins
//		if (count($this->adminClasses[$class]) > 1) {
//			throw new \RuntimeException(sprintf('Unable to found a valid admin for the class: %s, get too many admin registered: %s', $class, implode(',', $this->adminClasses[$class])));
//		}

		return $this->getInstance($this->adminClasses[$class][0]);
	}
}
