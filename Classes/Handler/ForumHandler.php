<?php
namespace Mittwald\Typo3Forum\Handler;

use Mittwald\Typo3Forum\Domain\Model\Forum\Post;
use Mittwald\Typo3Forum\Domain\Repository\Forum\ForumRepository;
use Mittwald\Typo3Forum\Domain\Repository\Forum\TopicRepository;
use Mittwald\Typo3Forum\Domain\Repository\User\FrontendUserRepository;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Fluid\View\StandaloneView;

class ForumHandler
{

	/**
	 * @var FrontendUserRepository
	 */
	protected $frontendUserRepository;

	/**
	 * @var ConfigurationManager
	 */
	protected $configurationManager;

	/**
	 * @var ForumRepository
	 */
	protected $forumRepository;

	/**
	 * @var TopicRepository
	 */
	protected $topicRepository;

	/**
	 * @var int
	 */
	protected $pid;


	protected function initializeObject()
	{
		$objectManager = GeneralUtility::makeInstance(ObjectManager::class);
		$this->configurationManager = $objectManager->get(ConfigurationManager::class);
		$this->topicRepository = $objectManager->get(TopicRepository::class);
		$this->forumRepository = $objectManager->get(ForumRepository::class);
		$this->frontendUserRepository = $objectManager->get(FrontendUserRepository::class);
	}

	/**
	 * @param ServerRequest $request
	 * @return JsonResponse
	 * @throws \Exception
	 */
	public function main(ServerRequest $request)
	{
		$this->initializeObject();

		$this->pid = (int)$request->getQueryParams()['pid'];

		$content = [];
		$arguments = $request->getParsedBody()['tx_typo3forum_ajax'] ?? '';

		$displayedUser = json_decode($arguments['displayedUser']);
		if (!empty($displayedUser)) {
			$content['onlineUser'] = $this->_getOnlineUser($displayedUser);
		}

		$displayedForumMenus = json_decode($arguments['displayedForumMenus']);
		if (!empty($displayedForumMenus)) {
			$content['forumMenus'] = $this->_getForumMenus($displayedForumMenus);
		}


//		if (!empty($postSummarys)) {
//			$content['postSummarys'] = $this->_getPostSummarys($postSummarys);
//		}
//		if (!empty($topicIcons)) {
//			$content['topicIcons'] = $this->_getTopicIcons($topicIcons);
//		}
//		if (!empty($forumIcons)) {
//			$content['forumIcons'] = $this->_getForumIcons($forumIcons);
//		}

		$displayedTopics = json_decode($arguments['displayedTopics']);
		if (!empty($displayedTopics)) {
			$content['topics'] = $this->_getTopics($displayedTopics);
		}
//		if (!empty($displayedPosts)) {
//			$content['posts'] = $this->_getPosts($displayedPosts);
//		}
//		if ($displayOnlinebox == 1) {
//			$content['onlineBox'] = $this->_getOnlinebox();
//		}
//		$displayedAds = json_decode($displayedAds);
//		if ((int)$displayedAds->count > 1) {
//			$content['ads'] = $this->_getAds($displayedAds);
//		}
//
//		$this->view->assign('content', json_encode($content));
//		return $this->view->render('Main');

		return new JsonResponse($content);
	}


	/**
	 * @return array
	 */
	private function _getOnlinebox() {
		$data = [];
		$data['count'] = $this->frontendUserRepository->countByFilter(TRUE);
		$this->request->setFormat('html');
		$users = $this->frontendUserRepository->findByFilter((int)$this->settings['widgets']['onlinebox']['limit'], [], TRUE);
		$this->view->assign('users', $users);
		$data['html'] = $this->view->render('Onlinebox');
		$this->request->setFormat('json');
		return $data;
	}

	/**
	 * @param string $displayedForumMenus
	 * @return array
	 */
	private function _getForumMenus($displayedForumMenus) {
		$data = [];
		if (count($displayedForumMenus) < 1) return $data;

		$extbaseSettings = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK, 'Typo3Forum');
		$templateRootPaths = $extbaseSettings['view']['templateRootPaths'];

		/* @var StandaloneView $standaloneView */
		$standaloneView = GeneralUtility::makeInstance(StandaloneView::class);
		$standaloneView->setTemplateRootPaths($templateRootPaths);
		$standaloneView->getRenderingContext()->setControllerName('Ajax');
		$standaloneView->setTemplate('forumMenu');
		$standaloneView->setFormat('html');

		$foren = $this->forumRepository->findByUids($displayedForumMenus);
		$counter = 0;
		foreach ($foren as $forum) {
			$standaloneView->assignMultiple([
				'forum' => $forum,
				'user' => $this->getCurrentUser(),
				'pid' => $this->pid
			]);

			$csrfToken = FormProtectionFactory::get('frontend')->generateToken('forumMenu_' . $forum->getUid());
			$standaloneView->assign('csrfToken', $csrfToken);

			$data[$counter]['uid'] = $forum->getUid();
			$data[$counter]['html'] = $standaloneView->render();
			$counter++;
		}
		return $data;
	}

	/**
	 * @param string $displayedPosts
	 * @return array
	 */
	private function _getPosts($displayedPosts) {
		$data = [];
		if (count($displayedPosts) < 1) return $data;
		$this->request->setFormat('html');
		$posts = $this->postRepository->findByUids($displayedPosts);
		$counter = 0;

		$extbaseSettings = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK, 'Typo3Forum');
		$templateRootPaths = $extbaseSettings['view']['templateRootPaths'];

		foreach ($posts as $post) {
			/* @var StandaloneView $standaloneView */
			$standaloneView = GeneralUtility::makeInstance(StandaloneView::class);
			$standaloneView->setControllerContext($this->controllerContext);
			$standaloneView->setTemplateRootPaths($templateRootPaths);
			$standaloneView->getRenderingContext()->setControllerName('Ajax');
			$standaloneView->setTemplate('PostHelpfulButton');
			$standaloneView->setFormat('html');
			/** @var Post $post */
			$standaloneView->assign('settings',$extbaseSettings['settings'])->assign('post', $post)
				->assign('user', $this->getCurrentUser());

			$data[$counter]['uid'] = $post->getUid();
			$data[$counter]['postHelpfulButton'] = $standaloneView->render();
			$data[$counter]['postHelpfulCount'] = $post->getHelpfulCount();
			$data[$counter]['postUserHelpfulCount'] = $post->getAuthor()->getHelpfulCount();
			$data[$counter]['author']['uid'] = $post->getAuthor()->getUid();

			/* @var StandaloneView $standaloneView */
			$standaloneView = GeneralUtility::makeInstance(StandaloneView::class);
			$standaloneView->setControllerContext($this->controllerContext);
			$standaloneView->setTemplateRootPaths($templateRootPaths);
			$standaloneView->getRenderingContext()->setControllerName('Ajax');
			$standaloneView->setTemplate('PostEditLink');
			$standaloneView->setFormat('html');
			$standaloneView->assign('settings',$extbaseSettings['settings'])->assign('post', $post)
				->assign('user', $this->getCurrentUser());
			$data[$counter]['postEditLink'] = $standaloneView->render();
			$counter++;
		}
		#$this->request->setFormat('json');
		return $data;
	}

	/**
	 * @param string $displayedTopics
	 * @return array
	 */
	private function _getTopics($displayedTopics) {
		$data = [];
		if (count($displayedTopics) < 1) return $data;

		$extbaseSettings = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK, 'Typo3Forum');
		$templateRootPaths = $extbaseSettings['view']['templateRootPaths'];

		/* @var StandaloneView $standaloneView */
		$standaloneView = GeneralUtility::makeInstance(StandaloneView::class);
		$standaloneView->setTemplateRootPaths($templateRootPaths);
		$standaloneView->getRenderingContext()->setControllerName('Ajax');
		$standaloneView->setTemplate('topicListMenu');
		$standaloneView->setFormat('json');

		$topicIcons = $this->topicRepository->findByUids($displayedTopics);
		$counter = 0;
		foreach ($topicIcons as $topic) {
			$csrfToken = FormProtectionFactory::get('frontend')->generateToken('topicListMenu_' . $topic->getUid());
			$standaloneView->assign('csrfToken', $csrfToken);

			$standaloneView->assign('topic', $topic);
			$standaloneView->assign('pid', $this->pid);
			$data[$counter]['uid'] = $topic->getUid();
			$data[$counter]['replyCount'] = $topic->getReplyCount();
			$data[$counter]['topicListMenu'] = $standaloneView->render();
			$counter++;
		}
		return $data;
	}

	/**
	 * @param string $topicIcons
	 * @return array
	 */
	private function _getTopicIcons($topicIcons) {
		$data = [];
		$topicIcons = json_decode($topicIcons);
		if (count($topicIcons) < 1) return $data;
		$this->request->setFormat('html');
		$topicIcons = $this->topicRepository->findByUids($topicIcons);
		$counter = 0;
		foreach ($topicIcons as $topic) {
			$this->view->assign('topic', $topic);
			$data[$counter]['html'] = $this->view->render('topicIcon');
			$data[$counter]['uid'] = $topic->getUid();
			$counter++;
		}
		$this->request->setFormat('json');
		return $data;
	}

	/**
	 * @param string $forumIcons
	 * @return array
	 */
	private function _getForumIcons($forumIcons) {
		$data = [];
		$forumIcons = json_decode($forumIcons);
		if (count($forumIcons) < 1) return $data;
		$this->request->setFormat('html');
		$forumIcons = $this->forumRepository->findByUids($forumIcons);
		$counter = 0;
		foreach ($forumIcons as $forum) {
			$this->view->assign('forum', $forum);
			$data[$counter]['html'] = $this->view->render('forumIcon');
			$data[$counter]['uid'] = $forum->getUid();
			$counter++;
		}
		$this->request->setFormat('json');
		return $data;
	}

	/**
	 * @param string $postSummarys
	 * @return array
	 */
	private function _getPostSummarys($postSummarys) {
		$postSummarys = json_decode($postSummarys);
		$data = [];
		$counter = 0;
		$this->request->setFormat('html');
		foreach ($postSummarys as $summary) {
			$post = false;
			switch ($summary->type) {
				case 'lastForumPost':
					$forum = $this->forumRepository->findByUid($summary->uid);
					/* @var Post */
					$post = $forum->getLastPost();
					break;
				case 'lastTopicPost':
					$topic = $this->topicRepository->findByUid($summary->uid);
					/* @var Post */
					$post = $topic->getLastPost();
					break;
			}
			if ($post) {
				$data[$counter] = $summary;
				$this->view->assign('post', $post)
					->assign('hiddenImage', $summary->hiddenimage);
				$data[$counter]->html = $this->view->render('postSummary');
				$counter++;
			}
		}
		$this->request->setFormat('json');
		return $data;
	}

	/**
	 * @param array $displayedUser
	 * @return array
	 */
	private function _getOnlineUser($displayedUser)
	{
		$onlineUsers = $this->frontendUserRepository->findByFilter("", [], true, $displayedUser);
		// write online user
		foreach ($onlineUsers as $onlineUser) {
			$output[] = $onlineUser->getUid();
		}
		if (!empty($output)) return $output;
	}

	/**
	 * @param \stdClass $meta
	 * @return array
	 */
	private function _getAds(\stdClass $meta) {
		$count = (int)$meta->count;
		$result = [];
		$this->request->setFormat('html');

		$actDatetime = new \DateTime();
		if (!$this->sessionHandlingService->get('adTime')) {
			$this->sessionHandlingService->set('adTime', $actDatetime);
			$adDateTime = $actDatetime;
		} else {
			$adDateTime = $this->sessionHandlingService->get('adTime');
		}
		if ($actDatetime->getTimestamp() - $adDateTime->getTimestamp() > $this->settings['ads']['timeInterval'] && $count > 2) {
			$this->sessionHandlingService->set('adTime', $actDatetime);
			if ((int)$meta->mode === 0) {
				$ads = $this->adRepository->findForForumView(1);
			} else {
				$ads = $this->adRepository->findForTopicView(1);
			}
			if (!empty($ads)) {
				$this->view->assign('ads', $ads);
				$result['html'] = $this->view->render('ads');
				$result['position'] = mt_rand(1, $count - 2);
			}
		}
		$this->request->setFormat('json');
		return $result;
	}

}
