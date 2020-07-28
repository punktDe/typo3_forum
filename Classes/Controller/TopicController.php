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

use Mittwald\Typo3Forum\Domain\Exception\Authentication\NoAccessException;
use Mittwald\Typo3Forum\Domain\Factory\Forum\PostFactory;
use Mittwald\Typo3Forum\Domain\Factory\Forum\TopicFactory;
use Mittwald\Typo3Forum\Domain\Model\Forum\Forum;
use Mittwald\Typo3Forum\Domain\Model\Forum\Post;
use Mittwald\Typo3Forum\Domain\Model\Forum\Topic;
use Mittwald\Typo3Forum\Domain\Repository\Forum\AdRepository;
use Mittwald\Typo3Forum\Domain\Repository\Forum\CriteriaRepository;
use Mittwald\Typo3Forum\Domain\Repository\Forum\ForumRepository;
use Mittwald\Typo3Forum\Domain\Repository\Forum\PostRepository;
use Mittwald\Typo3Forum\Domain\Repository\Forum\TagRepository;
use Mittwald\Typo3Forum\Domain\Repository\Forum\TopicRepository;
use Mittwald\Typo3Forum\Service\AttachmentService;
use Mittwald\Typo3Forum\Service\SessionHandlingService;
use Mittwald\Typo3Forum\Service\TagService;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;

class TopicController extends AbstractController {

	/**
	 * @var AdRepository
	 */
	protected $adRepository;

	/**
	 * @var AttachmentService
	 */
	protected $attachmentService;

	/**
	 * @var CriteriaRepository
	 */
	protected $criteraRepository;


	/**
	 * @var DatabaseConnection
	 */
	protected $databaseConnection;

	/**
	 * @var ForumRepository
	 */
	protected $forumRepository;

	/**
	 * @var PostFactory
	 */
	protected $postFactory;

	/**
	 * @var PostRepository
	 */
	protected $postRepository;

	/**
	 * @var SessionHandlingService
	 */
	protected $sessionHandling;

	/**
	 * @var TagRepository
	 */
	protected $tagRepository;

	/**
	 * @var TagService
	 */
	protected $tagService = NULL;

	/**
	 * @var TopicFactory
	 */
	protected $topicFactory;

	/**
	 * @var TopicRepository
	 */
	protected $topicRepository;



	/**
	 * @param AdRepository $adRepository
	 */
	public function injectAdRepository(AdRepository $adRepository): void
	{
		$this->adRepository = $adRepository;
	}


	/**
	 * @param AttachmentService $attachmentService
	 */
	public function injectAttachmentService(AttachmentService $attachmentService): void
	{
		$this->attachmentService = $attachmentService;
	}



	/**
	 * @param CriteriaRepository $criteraRepository
	 */
	public function injectCriteraRepository(CriteriaRepository $criteraRepository): void
	{
		$this->criteraRepository = $criteraRepository;
	}


	/**
	 * @param DatabaseConnection $databaseConnection
	 */
	public function injectDatabaseConnection(DatabaseConnection $databaseConnection): void
	{
		$this->databaseConnection = $databaseConnection;
	}


	/**
	 * @param ForumRepository $forumRepository
	 */
	public function injectForumRepository(ForumRepository $forumRepository): void
	{
		$this->forumRepository = $forumRepository;
	}


	/**
	 * @param PostFactory $postFactory
	 */
	public function injectPostFactory(PostFactory $postFactory): void
	{
		$this->postFactory = $postFactory;
	}



	/**
	 * @param PostRepository $postRepository
	 */
	public function injectPostRepository(PostRepository $postRepository): void
	{
		$this->postRepository = $postRepository;
	}


	/**
	 * @param SessionHandlingService $sessionHandling
	 */
	public function injectSessionHandling(SessionHandlingService $sessionHandling): void
	{
		$this->sessionHandling = $sessionHandling;
	}


	/**
	 * @param TagRepository $tagRepository
	 */
	public function injectTagRepository(TagRepository $tagRepository): void
	{
		$this->tagRepository = $tagRepository;
	}



	/**
	 * @param TagService $tagService
	 */
	public function injectTagService(TagService $tagService): void
	{
		$this->tagService = $tagService;
	}


	/**
	 * @param TopicFactory $topicFactory
	 */
	public function injectTopicFactory(TopicFactory $topicFactory): void
	{
		$this->topicFactory = $topicFactory;
	}


	/**
	 * @param TopicRepository $topicRepository
	 */
	public function injectTopicRepository(TopicRepository $topicRepository): void
	{
		$this->topicRepository = $topicRepository;
	}



	/**
	 *
	 */
	public function initializeObject() {
		$this->databaseConnection = $GLOBALS['TYPO3_DB'];
	}

    /**
     *  Listing Action.
     * @return void
     */
    public function listAction() {

        $showPaginate = FALSE;
        switch ($this->settings['listTopics']) {
            case '2':
                $dataset = $this->topicRepository->findQuestions();
                $showPaginate = TRUE;
                $partial = 'Topic/List';
                break;
            case '3':
                $dataset = $this->topicRepository->findQuestions(intval($this->settings['maxTopicItems']));
                $partial = 'Topic/QuestionBox';
                break;
            case '4':
                $dataset = $this->topicRepository->findPopularTopics(intval($this->settings['popularTopicTimeDiff']), intval($this->settings['maxTopicItems']));
                $partial = 'Topic/ListBox';
                break;
            default:
                $dataset      = $this->topicRepository->findAll();
                $partial      = 'Topic/List';
                $showPaginate = TRUE;
                break;
        }
        $this->view->assign('showPaginate', $showPaginate);
        $this->view->assign('partial', $partial);
        $this->view->assign('topics', $dataset);
    }

	/**
	 *  Listing Action.
	 */
	public function listLatestAction() {
		if (!empty($this->settings['countLatestPost'])) {
			$limit = (int)$this->settings['countLatestPost'];
		} else {
			$limit = 3;
		}

		$topics = $this->topicRepository->findLatest(0, $limit);
		$this->view->assign('topics', $topics);
	}

	/**
	 * Show action. Displays a single topic and all posts contained in this topic.
	 *
	 * @param Topic $topic The topic that is to be displayed.
	 * @param Post $quote An optional post that will be quoted within the bodytext of the new post.
	 * @param int $showForm ShowForm
	 */
	public function showAction(Topic $topic, Post $quote = NULL, $showForm = 0) {
		$posts = $this->postRepository->findForTopic($topic);

		if ($quote != FALSE) {
			$this->view->assign('quote', $this->postFactory->createPostWithQuote($quote));
		}
		// Set Title
		$GLOBALS['TSFE']->page['title'] = $topic->getTitle();

		$googlePlus = $topic->getAuthor()->getGoogle();
		if ($googlePlus) {
			$this->response->addAdditionalHeaderData('<link rel="author" href="' . $googlePlus . '"/>');
		}

		// send signal for simple read count
		$this->signalSlotDispatcher->dispatch(Topic::class, 'topicDisplayed', ['topic' => $topic]);

		$this->authenticationService->assertReadAuthorization($topic);
		$this->markTopicRead($topic);
		$this->view->assignMultiple([
			'posts' => $posts,
			'showForm' => $showForm,
			'topic' => $topic,
			'user' => $this->authenticationService->getUser(),
		]);
	}

	/**
	 * New action. Displays a form for creating a new topic.
	 *
	 * @param Forum $forum The forum in which the new topic is to be created.
	 * @param Post $post The first post of the new topic.
	 * @param string $subject The subject of the new topic
	 *
	 * @ignorevalidation $post
	 */
	public function newAction(Forum $forum, Post $post = NULL, $subject = NULL) {
		$this->authenticationService->assertNewTopicAuthorization($forum);

		$csrfToken = FormProtectionFactory::get('frontend')->generateToken('topic_new');

		$this->view->assignMultiple([
			'criteria' => $forum->getCriteria(),
			'currentUser' => $this->frontendUserRepository->findCurrent(),
			'forum' => $forum,
			'post' => $post,
			'subject' => $subject,
            'csrfToken' => $csrfToken,
		]);
	}

	/**
	 * Creates a new topic.
	 *
	 * @param Forum $forum The forum in which the new topic is to be created.
	 * @param Post $post The first post of the new topic.
	 * @param string $subject The subject of the new topic
     * @param string $csrfToken The CSRF token to make sure we are coming from newAction
	 * @param array $attachments File attachments for the post.
	 * @param string $question The flag if the new topic is declared as question
	 * @param array $criteria All submitted criteria with option.
	 * @param string $tags All defined tags for this topic
	 * @param string $subscribe The flag if the new topic is subscribed by author
	 *
	 * @validate $post \Mittwald\Typo3Forum\Domain\Validator\Forum\PostValidator
	 * @validate $attachments \Mittwald\Typo3Forum\Domain\Validator\Forum\AttachmentPlainValidator
	 * @validate $subject NotEmpty
     *
     * @throws \Exception if CSRF validation was not valid
	 */
	public function createAction(Forum $forum, Post $post, $subject, $csrfToken = '', $attachments = [], $question = '', $criteria = [], $tags = '', $subscribe = '') {

	    if (!FormProtectionFactory::get('frontend')->validateToken($csrfToken, 'topic_new')) {
	        throw new \Exception('CSRF validation not valid', 1502269952);
        }

		// Assert authorization
		$this->authenticationService->assertNewTopicAuthorization($forum);

		// Create the new post; add the new post to a new topic and add the new
		// topic to the forum. Then persist the forum object. Not as complicated
		// as is sounds, honestly!
		$this->postFactory->assignUserToPost($post);

		if (!empty($attachments)) {
			$attachments = $this->attachmentService->initAttachments($attachments);
			$post->setAttachments($attachments);
		}

		if ($tags) {
			$tags = $this->tagService->initTags($tags);
			foreach ($tags as $tag) {
				if ($tag->getUid === NULL) {
					$this->tagRepository->add($tag);
				}
			}
		} else {
			$tags = NULL;
		}

		$topic = $this->topicFactory->createTopic($forum, $post, $subject, (int)$question, $criteria, $tags, (int)$subscribe);

		// Notify potential listeners.
		$this->signalSlotDispatcher->dispatch(Topic::class, 'topicCreated', ['topic' => $topic]);
		$this->clearCacheForCurrentPage();

		if ($this->settings['purgeCache']) {
			$uriBuilder = $this->controllerContext->getUriBuilder();
			$uri = $uriBuilder->setTargetPageUid($this->settings['pids']['Forum'])->setArguments(['tx_typo3forum_pi1[forum]' => $forum->getUid(), 'tx_typo3forum_pi1[controller]' => 'Forum', 'tx_typo3forum_pi1[action]' => 'show'])->build();
			$this->purgeUrl('http://' . $_SERVER['HTTP_HOST'] . '/' . $uri);
		}

		// Redirect to single forum display view
		$this->redirect('show', 'Forum', NULL, ['forum' => $forum]);
	}

	/**
	 * Sets a post as solution
	 *
	 * @param Post $post The post to be marked as solution.
	 *
	 * @throws NoAccessException
	 */
	public function solutionAction(Post $post) {
		if (!$post->getTopic()->checkSolutionAccess($this->authenticationService->getUser())) {
			throw new NoAccessException('Not allowed to set solution by current user.');
		}
		$this->topicFactory->setPostAsSolution($post->getTopic(), $post);
		$this->redirect('show', 'Topic', NULL, ['topic' => $post->getTopic()]);
	}

	/**
	 * Marks a topic as read by the current user.
	 *
	 * @param Topic $topic The topic that is to be marked as read.
	 *
	 */
	protected function markTopicRead(Topic $topic) {
		$currentUser = $this->getCurrentUser();
		if ($currentUser === NULL || $currentUser->isAnonymous()) {
			return;
		} else {
			if ((false === $topic->hasBeenReadByUser($currentUser))) {
				$currentUser->addReadObject($topic);
				$this->frontendUserRepository->update($currentUser);
			}
		}
	}

}
