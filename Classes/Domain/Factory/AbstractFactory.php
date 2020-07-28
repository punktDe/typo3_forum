<?php
namespace Mittwald\Typo3Forum\Domain\Factory;

/*                                                                    - *
 *  COPYRIGHT NOTICE                                                    *
 *                                                                      *
 *  (c) 2015 Mittwald CM Service GmbH & Co KG                           *
 *           All rights reserved                                        *
 *                                                                      *
 *  This script is part of the TYPO3 project. The TYPO3 project is      *
 *  free software; you can redistribute it and/or modify                *
 *  it under the terms of the GNU General Public License as published   *
 *  by the Free Software Foundation; either version 2 of the License,   *
 *  or (at your option) any later version.                              *
 *                                                                      *
 *  The GNU General Public License can be found at                      *
 *  http://www.gnu.org/copyleft/gpl.html.                               *
 *                                                                      *
 *  This script is distributed in the hope that it will be useful,      *
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of      *
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the       *
 *  GNU General Public License for more details.                        *
 *                                                                      *
 *  This copyright notice MUST APPEAR in all copies of the script!      *
 *                                                                      */

use Mittwald\Typo3Forum\Configuration\ConfigurationBuilder;
use Mittwald\Typo3Forum\Domain\Model\User\FrontendUser;
use Mittwald\Typo3Forum\Domain\Repository\User\FrontendUserRepository;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;

abstract class AbstractFactory implements SingletonInterface {

	/**
	 * @var FrontendUserRepository
	 */
	protected $frontendUserRepository = NULL;

	/**
	 * @var ObjectManagerInterface
	 */
	protected $objectManager = NULL;

    /**
     * @var ConfigurationBuilder
     */
    protected $configurationBuilder;

	/**
	 * Whole TypoScript typo3_forum settings
	 * @var array
	 */
	protected $settings;



	/**
	 * @param FrontendUserRepository $frontendUserRepository
	 */
	public function injectFrontendUserRepository(FrontendUserRepository $frontendUserRepository): void
	{
		$this->frontendUserRepository = $frontendUserRepository;
	}



	/**
	 * @param ConfigurationBuilder $configurationBuilder
	 */
    public function injectConfigurationBuilder(ConfigurationBuilder $configurationBuilder): void
	{
		$this->configurationBuilder = $configurationBuilder;
	}



	/**
	 * @param ObjectManagerInterface $objectManager
	 */
	public function injectObjectManager(ObjectManagerInterface $objectManager): void
	{
		$this->objectManager = $objectManager;
	}

	/**
	 *
	 */
	public function initializeObject() {
		$this->settings = $this->configurationBuilder->getSettings();
	}

	/**
	 * Determines the class name of the domain object this factory is used for.
	 *
	 * @return string The class name
	 */
	protected function getClassName() {
		$thisClass = get_class($this);
		$thisClass = preg_replace('/Factory/', 'Model', $thisClass);
		$thisClass = preg_replace('/Model$/', '', $thisClass);

		return $thisClass;
	}

	/**
	 * Creates an instance of the domain object class.
	 *
	 * @return AbstractDomainObject An instance of the domain object.
	 */
	protected function getClassInstance() {
		return $this->objectManager->get($this->getClassName());
	}

	/**
	 * Gets the currently logged in user. Convenience wrapper for the findCurrent
	 * method of the frontend user repository.
	 *
	 * @return FrontendUser The user that is currently logged in.
	 */
	protected function getCurrentUser() {
		return $this->frontendUserRepository->findCurrent();
	}

}
