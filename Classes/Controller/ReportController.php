<?php
namespace Mittwald\Typo3Forum\Controller;

/*                                                                      *
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

use Mittwald\Typo3Forum\Domain\Factory\Moderation\ReportFactory;
use Mittwald\Typo3Forum\Domain\Model\Forum\Post;
use Mittwald\Typo3Forum\Domain\Model\Moderation\PostReport;
use Mittwald\Typo3Forum\Domain\Model\Moderation\Report;
use Mittwald\Typo3Forum\Domain\Model\Moderation\ReportComment;
use Mittwald\Typo3Forum\Domain\Model\Moderation\UserReport;
use Mittwald\Typo3Forum\Domain\Model\User\FrontendUser;
use Mittwald\Typo3Forum\Domain\Repository\Moderation\PostReportRepository;
use Mittwald\Typo3Forum\Domain\Repository\Moderation\ReportRepository;
use Mittwald\Typo3Forum\Domain\Repository\Moderation\UserReportRepository;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class ReportController extends AbstractController {

	/**
	 * @var PostReportRepository
	 */
	protected $postReportRepository;

	/**
	 * @var ReportFactory
	 */
	protected $reportFactory;


	/**
	 * @var ReportRepository
	 */
	protected $reportRepository;

	/**
	 * @var UserReportRepository
	 */
	protected $userReportRepository;


	/**
	 * @param PostReportRepository $postReportRepository
	 */
	public function injectPostReportRepository(PostReportRepository $postReportRepository): void
	{
		$this->postReportRepository = $postReportRepository;
	}


	/**
	 * @param ReportFactory $reportFactory
	 */
	public function injectReportFactory(ReportFactory $reportFactory): void
	{
		$this->reportFactory = $reportFactory;
	}


	/**
	 * @param ReportRepository $reportRepository
	 */
	public function injectReportRepository(ReportRepository $reportRepository): void
	{
		$this->reportRepository = $reportRepository;
	}


	/**
	 * @param UserReportRepository $userReportRepository
	 */
	public function injectUserReportRepository(UserReportRepository $userReportRepository): void
	{
		$this->userReportRepository = $userReportRepository;
	}

	/**
	 * Displays a form for creating a new post report.
	 *
	 * @param FrontendUser $user
	 * @param ReportComment $firstComment
	 *
	 * @TYPO3\CMS\Extbase\Annotation\IgnoreValidation("firstComment")
	 */
	public function newUserReportAction(FrontendUser $user, ReportComment $firstComment = NULL) {
		$this->view->assignMultiple([
			'firstComment' => $firstComment,
			'user' => $user,
		]);
	}

	/**
	 * Displays a form for creating a new post report.
	 *
	 * @param Post $post
	 * @param ReportComment $firstComment
	 *
	 * @TYPO3\CMS\Extbase\Annotation\IgnoreValidation("firstComment")
	 */
	public function newPostReportAction(Post $post, ReportComment $firstComment = NULL) {
		$this->authenticationService->assertReadAuthorization($post);
		$this->view->assign('firstComment', $firstComment)->assign('post', $post);
	}

	/**
	 * Creates a new post report and stores it into the database.
	 *
	 * @param FrontendUser $user
	 * @param ReportComment $firstComment
	 */
	public function createUserReportAction(FrontendUser $user, ReportComment $firstComment = NULL) {

		/** @var UserReport $report */
		$report = $this->reportFactory->createUserReport($firstComment);
		$report->setUser($user);
		$this->userReportRepository->add($report);

		// Notify observers.
		$this->signalSlotDispatcher->dispatch(Report::class, 'reportCreated', ['report' => $report]);

		// Display success message and redirect to topic->show action.
		$this->controllerContext->getFlashMessageQueue()->enqueue(
			new FlashMessage(LocalizationUtility::translate('Report_New_Success', 'Typo3Forum'))
		);
		$this->redirect('show', 'User', NULL, ['user' => $user], $this->settings['pids']['UserShow']);
	}

	/**
	 * Creates a new post report and stores it into the database.
	 *
	 * @param Post $post
	 * @param ReportComment $firstComment
	 */
	public function createPostReportAction(Post $post, ReportComment $firstComment = NULL) {
		// Assert authorization;
		$this->authenticationService->assertReadAuthorization($post);

		/** @var PostReport $report */
		$report = $this->reportFactory->createPostReport($firstComment);
		$report->setPost($post);
		$this->postReportRepository->add($report);

		// Notify observers.
		$this->signalSlotDispatcher->dispatch(Report::class, 'reportCreated', ['report' => $report]);

		// Display success message and redirect to topic->show action.
		$this->controllerContext->getFlashMessageQueue()->enqueue(
			new FlashMessage(LocalizationUtility::translate('Report_New_Success', 'Typo3Forum'))
		);
		$this->redirect('show', 'Topic', NULL, ['topic' => $post->getTopic()]);
	}

}
